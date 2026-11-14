<?php

namespace Platform\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Platform\Services\TenantRegistry;

/** Rewrite platform.json and every workspace file from the database (after a restore, or to be safe). */
class PlatformSync extends BaseCommand
{
    protected $group       = 'Platform';
    protected $name        = 'platform:sync';
    protected $description = 'Rewrite the workspace registry files from the platform database.';

    public function run(array $params)
    {
        TenantRegistry::writePlatform();
        CLI::write('Synced ' . TenantRegistry::writeAll() . ' workspace(s).', 'green');

        return EXIT_SUCCESS;
    }
}
