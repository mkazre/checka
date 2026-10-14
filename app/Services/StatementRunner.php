<?php

namespace App\Services;

use App\Models\ClientModel;

/**
 * Monthly statement run: emails every opted-in client their statement for
 * last month plus their open invoices. Each client is sent at most once per
 * period (clients.last_statement_period), so the cron can safely fire more
 * than once — e.g. daily — without double-sending.
 *
 * Clients are skipped when they have no email, or when there was no
 * activity in the period AND nothing is owed (an all-zero statement is noise).
 */
class StatementRunner
{
    /**
     * @return array{period:string, sent:list<string>, skipped:list<string>, failed:list<string>}
     */
    public function run(bool $force = false, ?int $onlyClientId = null, bool $dryRun = false): array
    {
        [$from, $to, $period] = BillingMailer::lastMonth();
        $out = ['period' => $period, 'sent' => [], 'skipped' => [], 'failed' => []];

        $clients = model(ClientModel::class)->where('monthly_statements', 1);
        if ($onlyClientId !== null) {
            $clients->where('id', $onlyClientId);
        }

        $billing = new BillingService();
        $mailer  = new BillingMailer();

        foreach ($clients->findAll() as $c) {
            $label = $c['name'];
            if (! $force && $c['last_statement_period'] === $period) {
                $out['skipped'][] = $label . ' (already sent for ' . $period . ')';

                continue;
            }
            if (ClientModel::invoiceEmail($c) === '') {
                $out['skipped'][] = $label . ' (no email)';

                continue;
            }
            $st = $billing->statement((int) $c['id'], $from, $to);
            if ($st['lines'] === [] && abs($st['closing']) < 0.005) {
                $out['skipped'][] = $label . ' (no activity, nothing owed)';

                continue;
            }
            if ($dryRun) {
                $out['sent'][] = $label . ' — would send, balance ' . money($st['closing']);

                continue;
            }

            $res = $mailer->sendStatement($c, $from, $to);
            if ($res['ok']) {
                model(ClientModel::class)->update($c['id'], ['last_statement_period' => $period]);
                $out['sent'][] = $label . ' — balance ' . money($st['closing']);
            } else {
                $out['failed'][] = $label . ': ' . ($res['error'] ?? 'send failed');
            }
        }

        return $out;
    }
}
