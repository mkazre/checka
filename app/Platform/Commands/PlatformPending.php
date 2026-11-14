<?php

namespace Platform\Commands;

use CodeIgniter\CLI\BaseCommand;
use Platform\Models\TenantModel;

/**
 * Prints the slugs of workspaces waiting to be set up, one per line, for the
 * provisioning cron:
 *
 *   for s in $(php spark platform:pending --no-header); do CHECKA_TENANT=$s php spark tenant:provision --no-header; done
 */
class PlatformPending extends BaseCommand
{
    protected $group       = 'Platform';
    protected $name        = 'platform:pending';
    protected $description = 'List workspaces waiting to be provisioned.';

    public function run(array $params)
    {
        foreach (model(TenantModel::class)->where('status', 'provisioning')->where('provision_error', null)->findAll(20) as $t) {
            echo $t['slug'], PHP_EOL;
        }

        return EXIT_SUCCESS;
    }
}
