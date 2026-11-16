<?php

namespace Platform\Commands;

use App\Tenancy\Tenant;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use Platform\Models\EventModel;
use Platform\Models\TenantModel;
use Platform\Services\TenantData;
use Platform\Services\TenantRegistry;
use Throwable;

/**
 * Builds a new workspace. Must run INSIDE the workspace's context so every
 * table lands under its prefix:
 *
 *   CHECKA_TENANT=acme php spark tenant:provision
 *
 * Creates all app tables, default checklist template, the first admin
 * login, switches on the package's modules, fills in the company details,
 * then marks the workspace live (trial or active). Safe to re-run.
 */
class TenantProvision extends BaseCommand
{
    protected $group       = 'Platform';
    protected $name        = 'tenant:provision';
    protected $description = 'Provision the current workspace (run with CHECKA_TENANT=<slug>).';

    public function run(array $params)
    {
        if (! Tenant::active()) {
            CLI::error('Run with CHECKA_TENANT=<slug>.');

            return EXIT_ERROR;
        }
        $tenants = model(TenantModel::class);
        $t       = $tenants->findBySlug((string) Tenant::slug());
        if ($t === null) {
            CLI::error('No platform record for ' . Tenant::slug());

            return EXIT_ERROR;
        }

        try {
            // 1. Tables
            $runner = service('migrations');
            foreach (['CodeIgniter\Shield', 'CodeIgniter\Settings', 'App'] as $ns) {
                $runner->setNamespace($ns)->latest();
            }
            CLI::write('Tables ready.');

            // 2. Default checklist (only if none yet)
            if (model(\App\Models\TemplateModel::class)->countAllResults() === 0) {
                (new \App\Database\Seeds\DefaultTemplateSeeder(config('Database')))->run();
                CLI::write('Default checklist installed.');
            }

            // 3. First admin
            $pending = (array) json_decode((string) ($t['pending_admin'] ?? ''), true);
            if (! empty($pending['email'])) {
                $users = model(UserModel::class);
                $user  = $users->findByCredentials(['email' => $pending['email']]);
                if ($user === null) {
                    $username = substr(preg_replace('/[^a-z0-9]/', '', strtolower(strstr($pending['email'], '@', true))) ?: 'admin', 0, 25);
                    $user = new User(['username' => $username . random_int(10, 99), 'email' => $pending['email'], 'password' => bin2hex(random_bytes(16))]);
                    $users->save($user);
                    $user = $users->findById($users->getInsertID());
                }
                $user->addGroup('admin');
                if (! empty($t['contact_name'])) {
                    setting()->set('Profile.name', $t['contact_name'], 'user:' . $user->id);
                }
                $user->activate();
                // Use the password chosen at sign-up (stored only as a hash).
                if (! empty($pending['password_hash'])) {
                    db_connect()->table('auth_identities')->where('user_id', $user->id)->where('type', 'email_password')
                        ->update(['secret2' => $pending['password_hash']]);
                }
                CLI::write('Admin login: ' . $pending['email']);
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
            CLI::write('Workspace ' . $t['slug'] . ' is live (' . $target . ').', 'green');

            // 6. Welcome email (first setup only)
            if (empty($t['provisioned_at']) && $t['email']) {
                $ok = \Platform\Services\PlatformMailer::template('welcome', (string) $t['email'], \Platform\Services\Billing::vars($tenants->find($t['id'])));
                EventModel::log((int) $t['id'], 'email', $ok ? 'Welcome email sent to ' . $t['email'] . '.' : 'Welcome email FAILED: ' . \Platform\Services\PlatformMailer::$lastError, 'system');
            }

            return EXIT_SUCCESS;
        } catch (Throwable $e) {
            $tenants->update($t['id'], ['provision_error' => mb_substr($e->getMessage(), 0, 2000)]);
            EventModel::log((int) $t['id'], 'provision_failed', $e->getMessage(), 'system');
            CLI::error('Provisioning failed: ' . $e->getMessage());

            return EXIT_ERROR;
        }
    }
}
