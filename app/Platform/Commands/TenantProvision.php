<?php

namespace Platform\Commands;

use App\Tenancy\Tenant;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Platform\Services\Provisioner;

/**
 * Builds a new workspace (the cron safety net — workspaces are normally built
 * straight after sign-up). Must run INSIDE the workspace's context:
 *
 *   CHECKA_TENANT=acme php spark tenant:provision
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
        $res = Provisioner::run();
        foreach (Provisioner::$log as $line) {
            CLI::write($line);
        }
        $res['ok'] ? CLI::write(date('Y-m-d H:i:s') . ' ' . $res['message'], 'green') : CLI::error(date('Y-m-d H:i:s') . ' ' . $res['message']);

        return $res['ok'] ? EXIT_SUCCESS : EXIT_ERROR;
    }
}
