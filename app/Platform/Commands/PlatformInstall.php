<?php

namespace Platform\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Platform\Models\AdminModel;
use Platform\Services\PlatformSettings;
use Platform\Services\TenantRegistry;

/**
 * One-time SaaS setup (safe to re-run): creates the platform tables, sets
 * the root domain, writes platform.json (which switches the install into
 * multi-workspace mode) and creates/updates the owner admin login.
 *
 *   php spark platform:install --domain checkaplus.co.za --email you@x.co.za --password 'Secret123!' --name "Your Name"
 */
class PlatformInstall extends BaseCommand
{
    protected $group       = 'Platform';
    protected $name        = 'platform:install';
    protected $description = 'Set up the SaaS platform (tables, root domain, owner login).';

    public function run(array $params)
    {
        $opt = static fn (string $k) => self::opt($k);
        $domain = strtolower(trim((string) ($opt('domain') ?: CLI::prompt('Root domain', 'checkaplus.co.za'))));
        $email  = trim((string) ($opt('email') ?: CLI::prompt('Owner email')));
        $pass   = (string) ($opt('password') ?: CLI::prompt('Owner password (min 10 chars)'));
        $name   = trim((string) ($opt('name') ?: 'Owner'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 10 || ! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
            CLI::error('Need a valid domain, email and a password of at least 10 characters.');

            return EXIT_ERROR;
        }

        $runner = service('migrations');
        $runner->setNamespace('Platform')->latest();
        CLI::write('Platform tables ready.', 'green');

        PlatformSettings::set('general', ['rootDomain' => $domain]);
        TenantRegistry::writePlatform();
        CLI::write('Root domain set to ' . $domain . ' (multi-workspace mode on).', 'green');

        $admins = model(AdminModel::class);
        $row    = $admins->where('email', $email)->first();
        $data   = ['name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'owner', 'active' => 1];
        $row ? $admins->update($row['id'], $data) : $admins->insert($data);
        CLI::write(($row ? 'Updated' : 'Created') . ' owner login ' . $email . ' — sign in at https://app.' . $domain . '/', 'green');

        return EXIT_SUCCESS;
    }

    public static function opt(string $name): ?string
    {
        $v = CLI::getOption($name);
        if (is_string($v) && $v !== '') {
            return $v;
        }
        $argv = $_SERVER['argv'] ?? [];
        foreach ($argv as $i => $a) {
            if (str_starts_with($a, '--' . $name . '=')) {
                return substr($a, strlen($name) + 3);
            }
            if ($a === '--' . $name && isset($argv[$i + 1]) && ! str_starts_with($argv[$i + 1], '--')) {
                return $argv[$i + 1];
            }
        }

        return null;
    }
}
