<?php

namespace Platform\Services;

use Platform\Models\EventModel;
use Platform\Models\TenantModel;

/**
 * Customer accounts across all workspaces: find which workspace(s) an email
 * belongs to, check passwords, issue one-time sign-in links into a workspace,
 * and reset passwords. Lets customers sign in from one central page without
 * typing their workspace address first.
 */
class Accounts
{
    /** Workspaces whose users can sign in (built and not closed). */
    private static function liveTenants(): array
    {
        return model(TenantModel::class)->where('provisioned_at IS NOT NULL')
            ->whereIn('status', ['trial', 'active', 'past_due', 'suspended'])->findAll();
    }

    /**
     * Every workspace login with this email.
     *
     * @return list<array{tenant:array,user_id:int,hash:string}>
     */
    public static function find(string $email): array
    {
        $email = strtolower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }
        $out = [];
        foreach (self::liveTenants() as $t) {
            try {
                if (! TenantData::tableExists($t, 'auth_identities')) {
                    continue;
                }
                $row = TenantData::conn($t)->query(
                    'SELECT ai.user_id, ai.secret2 FROM ' . TenantData::table($t, 'auth_identities') . ' ai JOIN ' . TenantData::table($t, 'users') . ' u ON u.id = ai.user_id'
                    . " WHERE ai.type = 'email_password' AND LOWER(ai.secret) = ? AND u.deleted_at IS NULL AND (u.status IS NULL OR u.status <> 'banned') LIMIT 1",
                    [$email]
                )->getRowArray();
                if ($row) {
                    $out[] = ['tenant' => $t, 'user_id' => (int) $row['user_id'], 'hash' => (string) $row['secret2']];
                }
            } catch (\Throwable $e) {
                log_message('error', 'Accounts::find ' . $t['slug'] . ': ' . $e->getMessage());
            }
        }

        return $out;
    }

    /** Sign-ups still being built or waiting for payment (the login doesn't exist in the workspace yet). */
    public static function pending(string $email): array
    {
        $email = strtolower(trim($email));
        $out   = [];
        foreach (model(TenantModel::class)->whereIn('status', ['provisioning', 'pending_payment'])->findAll() as $t) {
            $p = (array) json_decode((string) $t['pending_admin'], true);
            if (strtolower((string) ($p['email'] ?? '')) === $email) {
                $out[] = ['tenant' => $t, 'hash' => (string) ($p['password_hash'] ?? '')];
            }
        }

        return $out;
    }

    /** One-time (60 s) link that signs a user straight into their workspace. */
    public static function ssoUrl(array $t, ?int $userId = null, string $via = 'central sign-in'): string
    {
        $token = bin2hex(random_bytes(24));
        $dir   = TenantRegistry::dir() . '/sso';
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        foreach (glob($dir . '/*.json') ?: [] as $old) {
            if (filemtime($old) < time() - 300) {
                @unlink($old);
            }
        }
        file_put_contents($dir . '/' . $token . '.json', json_encode([
            'slug' => $t['slug'], 'expires' => time() + 60, 'user_id' => $userId, 'admin' => $via,
        ]));

        return Billing::workspaceUrl($t['slug'], 'platform-sso/' . $token);
    }

    /** Set a new password for this email in every workspace (and any sign-up still being built). */
    public static function setPassword(string $email, string $password): int
    {
        $email = strtolower(trim($email));
        $hash  = password_hash($password, PASSWORD_DEFAULT);
        $n     = 0;
        foreach (self::find($email) as $m) {
            TenantData::conn($m['tenant'])->query('UPDATE ' . TenantData::table($m['tenant'], 'auth_identities')
                . " SET secret2 = ? WHERE type = 'email_password' AND LOWER(secret) = ?", [$hash, $email]);
            EventModel::log((int) $m['tenant']['id'], 'password_reset', 'Password reset by email for ' . $email . '.', 'customer');
            $n++;
        }
        foreach (self::pending($email) as $m) {
            $p                  = (array) json_decode((string) $m['tenant']['pending_admin'], true);
            $p['password_hash'] = $hash;
            model(TenantModel::class)->update($m['tenant']['id'], ['pending_admin' => json_encode($p)]);
            $n++;
        }

        return $n;
    }

    // ------------------------------------------------------------ reset tokens

    public static function issueReset(string $email, string $ip): string
    {
        $token = bin2hex(random_bytes(32));
        db_connect('platform')->table('account_tokens')->insert([
            'email' => strtolower(trim($email)), 'token_hash' => hash('sha256', $token), 'purpose' => 'reset',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600), 'ip' => $ip, 'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /** The email a valid, unused reset token belongs to. */
    public static function resetEmail(string $token): ?string
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = db_connect('platform')->table('account_tokens')->where('token_hash', hash('sha256', $token))
            ->where('purpose', 'reset')->where('used_at', null)->where('expires_at >', date('Y-m-d H:i:s'))->get()->getRowArray();

        return $row['email'] ?? null;
    }

    public static function useReset(string $token): void
    {
        db_connect('platform')->table('account_tokens')->where('token_hash', hash('sha256', $token))->update(['used_at' => date('Y-m-d H:i:s')]);
    }

    /** "i***@s***.co.za" */
    public static function mask(string $email): string
    {
        [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $parts           = explode('.', $domain, 2);

        return mb_substr($user, 0, 1) . '***@' . mb_substr($parts[0], 0, 1) . '***' . (isset($parts[1]) ? '.' . $parts[1] : '');
    }
}
