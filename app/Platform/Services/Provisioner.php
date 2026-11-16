<?php

namespace Platform\Services;

use App\Tenancy\Tenant;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use Platform\Models\EventModel;
use Platform\Models\TenantModel;
use Throwable;

/**
 * Builds a workspace: its tables, default checklist, first admin login,
 * package modules and company details. Must run in the workspace's own
 * context (CHECKA_TENANT=<slug> for CLI, or a request to <slug>.<root>).
 *
 * Started two ways, whichever comes first:
 *  - immediately after sign-up/payment, via kick() (a signed request to the
 *    workspace address, so nobody waits for cron);
 *  - the per-minute cron job, as a safety net.
 * A lock file stops both from building the same workspace at once.
 */
class Provisioner
{
    /** @var list<string> */
    public static array $log = [];

    private static function say(string $line): void
    {
        self::$log[] = $line;
    }

    /** @return array{ok:bool,status:string,message:string} */
    public static function run(): array
    {
        self::$log = [];
        if (! Tenant::active()) {
            return ['ok' => false, 'status' => 'error', 'message' => 'Not in a workspace context.'];
        }
        $tenants = model(TenantModel::class);
        $t       = $tenants->findBySlug((string) Tenant::slug());
        if ($t === null) {
            return ['ok' => false, 'status' => 'error', 'message' => 'No platform record for ' . Tenant::slug()];
        }
        if ($t['status'] !== 'provisioning') {
            return ['ok' => true, 'status' => 'done', 'message' => 'Already set up (' . $t['status'] . ').'];
        }

        $lockDir = Tenant::baseWritable() . '/platform/locks';
        if (! is_dir($lockDir)) {
            @mkdir($lockDir, 0755, true);
        }
        $lock = fopen($lockDir . '/' . $t['slug'] . '.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return ['ok' => true, 'status' => 'busy', 'message' => 'Setup is already running.'];
        }

        try {
            @set_time_limit(300);
            $t = $tenants->find($t['id']); // fresh, now that we hold the lock
            if ($t['status'] !== 'provisioning') {
                return ['ok' => true, 'status' => 'done', 'message' => 'Already set up.'];
            }

            // 1. Tables
            $runner = service('migrations');
            foreach (['CodeIgniter\Shield', 'CodeIgniter\Settings', 'App'] as $ns) {
                $runner->setNamespace($ns)->latest();
            }
            self::say('Tables ready.');

            // 2. Default checklist (only if none yet)
            if (model(\App\Models\TemplateModel::class)->countAllResults() === 0) {
                (new \App\Database\Seeds\DefaultTemplateSeeder(config('Database')))->run();
                self::say('Default checklist installed.');
            }

            // 3. First admin, with the password chosen at sign-up
            $pending = (array) json_decode((string) ($t['pending_admin'] ?? ''), true);
            if (! empty($pending['email'])) {
                $users = model(UserModel::class);
                $user  = $users->findByCredentials(['email' => $pending['email']]);
                if ($user === null) {
                    $username = substr(preg_replace('/[^a-z0-9]/', '', strtolower(strstr($pending['email'], '@', true))) ?: 'admin', 0, 25);
                    $user     = new User(['username' => $username . random_int(10, 99), 'email' => $pending['email'], 'password' => bin2hex(random_bytes(16))]);
                    $users->save($user);
                    $user = $users->findById($users->getInsertID());
                }
                $user->addGroup('admin');
                if (! empty($t['contact_name'])) {
                    setting()->set('Profile.name', $t['contact_name'], 'user:' . $user->id);
                }
                $user->activate();
                if (! empty($pending['password_hash'])) {
                    db_connect()->table('auth_identities')->where('user_id', $user->id)->where('type', 'email_password')
                        ->update(['secret2' => $pending['password_hash']]);
                }
                self::say('Admin login: ' . $pending['email']);
            }

            // 4. Modules from the package, company details for branding/invoices
            TenantData::enableModules($t, TenantRegistry::effective($t)['modules']);
            setting()->set('Brand.brandName', $t['company_name']);
            setting()->set('Billing.companyName', $t['company_name']);
            if ($t['email']) {
                setting()->set('Billing.companyEmail', $t['email']);
            }
            if ($t['phone']) {
                setting()->set('Billing.companyPhone', $t['phone']);
            }
            if ($t['vat_number']) {
                setting()->set('Billing.companyVatNo', $t['vat_number']);
            }
            setting()->set('Notify.fromName', $t['company_name']);

            // 5. Live
            $target = $pending['status'] ?? ($t['trial_ends_at'] ? 'trial' : 'active');
            $tenants->update($t['id'], ['status' => $target, 'provisioned_at' => date('Y-m-d H:i:s'), 'pending_admin' => null, 'provision_error' => null]);
            TenantRegistry::write($tenants->find($t['id']));
            EventModel::log((int) $t['id'], 'provisioned', 'Workspace ready (' . $target . ').', 'system');
            self::say('Workspace ' . $t['slug'] . ' is live (' . $target . ').');

            // 6. Welcome email (first setup only)
            if (empty($t['provisioned_at']) && $t['email']) {
                $ok = PlatformMailer::template('welcome', (string) $t['email'], Billing::vars($tenants->find($t['id'])));
                EventModel::log((int) $t['id'], 'email', $ok ? 'Welcome email sent to ' . $t['email'] . '.' : 'Welcome email FAILED: ' . PlatformMailer::$lastError, 'system');
            }

            return ['ok' => true, 'status' => 'live', 'message' => 'Workspace ' . $t['slug'] . ' is live (' . $target . ').'];
        } catch (Throwable $e) {
            $tenants->update($t['id'], ['provision_error' => mb_substr($e->getMessage(), 0, 2000)]);
            EventModel::log((int) $t['id'], 'provision_failed', $e->getMessage(), 'system');

            return ['ok' => false, 'status' => 'error', 'message' => 'Provisioning failed: ' . $e->getMessage()];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    // ------------------------------------------------------------ immediate start

    /** Signature that proves a provisioning request came from this platform. */
    public static function signature(string $slug): string
    {
        $key = (string) (config('Encryption')->key ?? '');

        return hash_hmac('sha256', 'provision|' . $slug, $key !== '' ? $key : (string) config('App')->baseURL);
    }

    /**
     * Ask the workspace address to build itself right now. Returns quickly (the build
     * carries on server-side after we hang up); the cron job remains the safety net.
     */
    public static function kick(array $t): void
    {
        if (($t['status'] ?? '') !== 'provisioning') {
            return;
        }
        $url = Billing::workspaceUrl($t['slug'], 'platform-provision/' . self::signature($t['slug']));
        try {
            \Config\Services::curlrequest([], null, null, false)->get($url, [
                'http_errors' => false, 'timeout' => 3, 'connect_timeout' => 3, 'verify' => false,
            ]);
        } catch (Throwable $e) {
            // A timeout is expected: the workspace keeps building after we hang up.
        }
    }
}
