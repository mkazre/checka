<?php

namespace App\Commands;

use App\Services\StatementRunner;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Emails last month's statement + open invoices to every opted-in client.
 * Safe to schedule daily: each client gets one statement per month.
 *
 *   php spark checka:statements                 # normal run (cron)
 *   php spark checka:statements --dry-run       # show who would get one
 *   php spark checka:statements --force         # resend even if already sent
 *   php spark checka:statements --client=12     # one client only
 */
class CheckaStatements extends BaseCommand
{
    protected $group       = 'Checka';
    protected $name        = 'checka:statements';
    protected $description = 'Email last month\'s statement + open invoices to clients.';
    protected $options     = [
        '--dry-run' => 'List what would be sent without sending.',
        '--force'   => 'Send even if this period was already sent.',
        '--client'  => 'Only this client id.',
    ];

    public function run(array $params): int
    {
        helper(['setting', 'url']);

        if (! feature('billing') || ! setting('Billing.monthlyStatementsEnabled')) {
            CLI::write('Monthly statements are switched off (Settings → Billing feature, and Invoices → Billing Settings).', 'yellow');

            return EXIT_SUCCESS;
        }

        $dry    = CLI::getOption('dry-run') !== null || array_key_exists('dry-run', $params);
        $force  = CLI::getOption('force') !== null || array_key_exists('force', $params);
        $client = CLI::getOption('client');

        $res = (new StatementRunner())->run($force, $client !== null ? (int) $client : null, $dry);

        CLI::write('Statement period: ' . $res['period'] . ($dry ? ' (dry run)' : ''), 'green');
        foreach ($res['sent'] as $s) {
            CLI::write('  sent    ' . $s);
        }
        foreach ($res['skipped'] as $s) {
            CLI::write('  skipped ' . $s, 'light_gray');
        }
        foreach ($res['failed'] as $s) {
            CLI::error('  FAILED  ' . $s);
        }

        return $res['failed'] === [] ? EXIT_SUCCESS : EXIT_ERROR;
    }
}
