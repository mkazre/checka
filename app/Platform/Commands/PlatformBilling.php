<?php

namespace Platform\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Platform\Models\EventModel;
use Platform\Models\PackageModel;
use Platform\Models\TenantModel;
use Platform\Services\Billing;
use Platform\Services\PlatformMailer;
use Platform\Services\PlatformSettings;
use Platform\Services\Purge;
use Platform\Services\TenantRegistry;
use Throwable;

/**
 * The subscription robot. Run it from cron (hourly is fine — every step is
 * idempotent; each email/notice is sent once):
 *
 *   php spark platform:billing
 *
 * Uses the rules under Settings → Billing rules: trial reminders and trial
 * end, renewal invoices, saved-card charges, payment reminders, past-due and
 * auto-pause, cancellations at period end, abandoned sign-ups, retention
 * warnings and permanent deletion.
 */
class PlatformBilling extends BaseCommand
{
    protected $group       = 'Platform';
    protected $name        = 'platform:billing';
    protected $description = 'Run subscription billing: invoices, reminders, charges, suspensions, retention.';
    protected $usage       = 'platform:billing [--date YYYY-MM-DD] [--dry]';

    private array $rules;
    private string $today;
    private array $log = [];

    public function run(array $params)
    {
        $this->rules = PlatformSettings::get('billing');
        $date        = PlatformInstall::opt('date');
        $this->today = $date && strtotime($date) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');

        $tm = model(TenantModel::class);
        foreach ($tm->findAll() as $t) {
            try {
                $this->tenant($t);
            } catch (Throwable $e) {
                $this->note($t, 'ERROR ' . $e->getMessage());
                log_message('error', 'platform:billing ' . $t['slug'] . ': ' . $e->getMessage());
            }
        }
        foreach ($this->log as $line) {
            CLI::write($line);
        }
        CLI::write('Billing run for ' . $this->today . ' done (' . count($this->log) . ' action(s)).', 'green');

        return EXIT_SUCCESS;
    }

    private function tenant(array $t): void
    {
        $id = (int) $t['id'];

        switch ($t['status']) {
            case 'pending_payment':
                if ($this->daysSince($t['created_at']) >= (int) $this->rules['abandonDays']) {
                    Billing::db()->table('invoices')->where('tenant_id', $id)->where('status', 'unpaid')->update(['status' => 'void']);
                    TenantRegistry::remove($t['slug']);
                    model(TenantModel::class)->delete($id, true);
                    $this->note($t, 'unpaid sign-up removed, address freed');
                }

                return;

            case 'trial':
                $this->trial($t);
                break;

            case 'active':
            case 'past_due':
                $this->paid($t);
                break;

            case 'suspended':
            case 'cancelled':
                $this->retention($t);

                return;

            default:
                return;
        }

        $t = model(TenantModel::class)->find($id);
        if ($t && in_array($t['status'], ['active', 'past_due', 'trial'], true)) {
            $this->reminders($t);
        }
    }

    // ---------------------------------------------------------------- trials

    private function trial(array $t): void
    {
        if (! $t['trial_ends_at']) {
            return;
        }
        $pkg   = $t['package_id'] ? model(PackageModel::class)->find($t['package_id']) : null;
        $price = $pkg ? Billing::price($pkg, $t['billing_cycle']) : 0;
        $end   = date('Y-m-d', strtotime($t['trial_ends_at']));
        $left  = $this->daysUntil($end);

        if ($price <= 0) {
            if ($left < 0) {
                $this->setStatus($t, 'active', 'Free package — trial ended, now active.');
            }

            return;
        }

        // Reminders before the trial ends.
        $d = $this->pick(PlatformSettings::days((string) $this->rules['trialRemindBefore']), $left, 'trial-' . $end, $t);
        if ($d !== null) {
            $inv = Billing::openInvoice((int) $t['id'], 'first');
            $this->mail($t, 'trial_ending', $inv, 'trial ending reminder (' . $d . ' day(s))');
        }

        // Invoice for the first paid period, ahead of the trial end.
        if ($left <= (int) $this->rules['invoiceLeadDays'] && ! Billing::db()->table('invoices')->where('tenant_id', $t['id'])->where('kind', 'first')->whereIn('status', ['unpaid', 'paid'])->countAllResults()) {
            $start = max($end, $this->today);
            $inv   = Billing::createInvoice($t, 'first', $pkg['name'] . ' — ' . ($t['billing_cycle'] === 'annual' ? 'annual' : 'monthly') . ' subscription', $price, [
                'package_id' => $pkg['id'], 'billing_cycle' => $t['billing_cycle'], 'period_start' => $start, 'period_end' => Billing::addCycle($start, $t['billing_cycle']), 'due_date' => $end,
            ]);
            Billing::sendInvoice($t, $inv);
            $this->note($t, 'first invoice ' . $inv['number'] . ' issued');
        }

        // Trial over and still unpaid.
        if ($left < 0) {
            $inv = Billing::openInvoice((int) $t['id'], 'first');
            if ($inv && $t['paystack_auth'] && $this->once($t, 'charge-' . $inv['id'] . '-' . $this->today) && Billing::chargeSavedCard($t, $inv)) {
                $this->note($t, 'saved card charged for ' . $inv['number']);

                return;
            }
            if ($this->rules['trialEndAction'] === 'downgrade' && ($free = $this->freePackage())) {
                if ($inv) {
                    Billing::db()->table('invoices')->where('id', $inv['id'])->update(['status' => 'void']);
                }
                $before = TenantRegistry::effective($t)['modules'];
                model(TenantModel::class)->update($t['id'], ['package_id' => $free['id'], 'status' => 'active', 'trial_ends_at' => null]);
                TenantRegistry::write(model(TenantModel::class)->find($t['id']));
                EventModel::log((int) $t['id'], 'downgraded', 'Trial ended unpaid — moved to ' . $free['name'] . '.', 'system');
                $this->note($t, 'trial ended — moved to free package ' . $free['name']);

                return;
            }
            if ($this->rules['autoSuspend']) {
                $this->suspend($t, $inv, 'Trial ended without payment.');
            }
        }
    }

    private function freePackage(): ?array
    {
        foreach (model(PackageModel::class)->ordered(true) as $p) {
            if ((float) $p['price_monthly'] <= 0 && (float) $p['price_annual'] <= 0) {
                return $p;
            }
        }

        return null;
    }

    // ---------------------------------------------------------------- paid subscriptions

    private function paid(array $t): void
    {
        if (! $t['current_period_end']) {
            return; // free / manual workspace
        }
        $end  = $t['current_period_end'];
        $left = $this->daysUntil($end);

        if ($t['cancel_at_period_end'] && $left <= 0) {
            Billing::db()->table('invoices')->where('tenant_id', $t['id'])->where('status', 'unpaid')->update(['status' => 'void']);
            model(TenantModel::class)->update($t['id'], ['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')]);
            TenantRegistry::write(model(TenantModel::class)->find($t['id']));
            EventModel::log((int) $t['id'], 'cancelled', 'Subscription ended (cancelled at period end).', 'system');
            $this->note($t, 'cancelled at period end');

            return;
        }
        if ($t['cancel_at_period_end']) {
            return;
        }

        // Renewal invoice ahead of the period end.
        if ($left <= (int) $this->rules['invoiceLeadDays']
            && ! Billing::db()->table('invoices')->where('tenant_id', $t['id'])->where('kind', 'renewal')->where('period_start', $end)->whereIn('status', ['unpaid', 'paid'])->countAllResults()) {
            $pkgId = $t['next_package_id'] ?: $t['package_id'];
            $cycle = $t['next_cycle'] ?: $t['billing_cycle'];
            $pkg   = $pkgId ? model(PackageModel::class)->find($pkgId) : null;
            if ($pkg && Billing::price($pkg, $cycle) > 0) {
                $inv = Billing::createInvoice($t, 'renewal', $pkg['name'] . ' — ' . ($cycle === 'annual' ? 'annual' : 'monthly') . ' subscription', Billing::price($pkg, $cycle), [
                    'package_id' => $pkg['id'], 'billing_cycle' => $cycle, 'period_start' => $end, 'period_end' => Billing::addCycle($end, $cycle), 'due_date' => $end,
                ]);
                Billing::sendInvoice($t, $inv);
                $this->note($t, 'renewal invoice ' . $inv['number'] . ' issued');
            }
        }

        // Saved card: charge on/after the due date (once a day).
        $inv = Billing::openInvoice((int) $t['id']);
        if ($inv && $t['paystack_auth'] && $inv['due_date'] <= $this->today && $this->once($t, 'charge-' . $inv['id'] . '-' . $this->today)) {
            if (Billing::chargeSavedCard($t, $inv)) {
                $this->note($t, 'saved card charged for ' . $inv['number']);
            }
        }
    }

    // ---------------------------------------------------------------- reminders, past due, pause

    private function reminders(array $t): void
    {
        foreach (Billing::db()->table('invoices')->where('tenant_id', $t['id'])->where('status', 'unpaid')->get()->getResultArray() as $inv) {
            if ($t['status'] === 'trial' && $inv['kind'] === 'first' && $inv['due_date'] >= $this->today) {
                continue; // covered by the trial reminders
            }
            $toDue = $this->daysUntil($inv['due_date']);

            if ($toDue > 0) {
                $d = $this->pick(PlatformSettings::days((string) $this->rules['remindBefore']), $toDue, 'before-' . $inv['id'], $t);
                if ($d !== null) {
                    $this->mail($t, 'reminder_before', $inv, 'reminder ' . $d . ' day(s) before due');
                }
            } elseif ($toDue === 0) {
                if ($this->rules['remindOnDue'] && $this->once($t, 'due-' . $inv['id'])) {
                    $this->mail($t, 'reminder_due', $inv, 'due-today reminder');
                }
            } else {
                $over = -$toDue;
                $d    = $this->pickAfter(PlatformSettings::days((string) $this->rules['remindAfter']), $over, 'after-' . $inv['id'], $t);
                if ($d !== null) {
                    $this->mail($t, 'reminder_after', $inv, 'overdue reminder (' . $over . ' day(s))');
                }
                if ($t['status'] === 'active') {
                    $this->setStatus($t, 'past_due', 'Invoice ' . $inv['number'] . ' is overdue.');
                    $t['status'] = 'past_due';
                }
                if ($over >= (int) $this->rules['graceDays'] && $this->rules['autoSuspend'] && $t['status'] !== 'suspended') {
                    $this->suspend($t, $inv, 'Invoice ' . $inv['number'] . ' is ' . $over . ' day(s) overdue.');

                    return;
                }
            }
        }
    }

    private function suspend(array $t, ?array $inv, string $why): void
    {
        model(TenantModel::class)->update($t['id'], ['status' => 'suspended', 'suspended_at' => date('Y-m-d H:i:s')]);
        $t = model(TenantModel::class)->find($t['id']);
        TenantRegistry::write($t);
        EventModel::log((int) $t['id'], 'suspended', 'Workspace paused automatically. ' . $why, 'system');
        $this->mail($t, 'suspended', $inv, 'workspace paused');
    }

    // ---------------------------------------------------------------- retention

    private function retention(array $t): void
    {
        $days = (int) $this->rules['retentionDays'];
        $since = $t['status'] === 'suspended' ? $t['suspended_at'] : $t['cancelled_at'];
        if ($days <= 0 || ! $since) {
            return;
        }
        $age = $this->daysSince($since);
        if ($age >= $days - (int) $this->rules['finalWarningDays'] && $this->once($t, 'final-' . substr($since, 0, 10))) {
            $this->mail($t, 'final_warning', Billing::openInvoice((int) $t['id']), 'final warning before deletion');
        }
        if ($age >= $days) {
            if (Purge::tenant($t)) {
                $this->note($t, 'data permanently deleted (retention ' . $days . ' days)');
            } elseif ($this->once($t, 'purge-skip-' . substr($since, 0, 10))) {
                $this->note($t, 'retention over — attached tables, delete manually');
            }
        }
    }

    // ---------------------------------------------------------------- helpers

    /**
     * From a list of "days before" (e.g. [7,3]) choose the reminder that is due
     * now ($left days remain). Each fires once; if the job missed a day, only
     * the most relevant one is sent and earlier ones are marked as done.
     */
    private function pick(array $days, int $left, string $key, array $t): ?int
    {
        sort($days); // ascending: 1,3,7
        foreach ($days as $d) {
            if ($left <= $d && $left >= 0) {
                $send = $this->once($t, $key . '-' . $d);
                foreach ($days as $o) {
                    if ($o > $d) {
                        $this->once($t, $key . '-' . $o);
                    }
                }

                return $send ? $d : null;
            }
        }

        return null;
    }

    /** Same for "days after" lists: the largest threshold already passed. */
    private function pickAfter(array $days, int $over, string $key, array $t): ?int
    {
        rsort($days);
        foreach ($days as $d) {
            if ($over >= $d) {
                $send = $this->once($t, $key . '-' . $d);
                foreach ($days as $o) {
                    if ($o < $d) {
                        $this->once($t, $key . '-' . $o);
                    }
                }

                return $send ? $d : null;
            }
        }

        return null;
    }

    /** True the first time a notice key is seen for a tenant (and records it). */
    private function once(array $t, string $key): bool
    {
        $db = Billing::db();
        if ($db->table('notices')->where('tenant_id', $t['id'])->where('notice_key', $key)->countAllResults() > 0) {
            return false;
        }
        $db->table('notices')->insert(['tenant_id' => $t['id'], 'notice_key' => mb_substr($key, 0, 120), 'created_at' => date('Y-m-d H:i:s')]);

        return true;
    }

    private function mail(array $t, string $template, ?array $inv, string $what): void
    {
        $ok = PlatformMailer::template($template, (string) $t['email'], Billing::vars($t, $inv));
        EventModel::log((int) $t['id'], 'email', ($ok ? 'Sent ' : 'FAILED to send ') . $what . ' to ' . $t['email'] . ($ok ? '.' : ': ' . PlatformMailer::$lastError), 'system');
        $this->note($t, ($ok ? 'emailed ' : 'email FAILED: ') . $what);
    }

    private function setStatus(array $t, string $status, string $why): void
    {
        model(TenantModel::class)->update($t['id'], ['status' => $status]);
        TenantRegistry::write(model(TenantModel::class)->find($t['id']));
        EventModel::log((int) $t['id'], $status, $why, 'system');
        $this->note($t, 'status → ' . $status);
    }

    private function daysUntil(string $date): int
    {
        return (int) round((strtotime(substr($date, 0, 10)) - strtotime($this->today)) / 86400);
    }

    private function daysSince(string $datetime): int
    {
        return (int) floor((strtotime($this->today) - strtotime(substr($datetime, 0, 10))) / 86400);
    }

    private function note(array $t, string $msg): void
    {
        $this->log[] = '  ' . $t['slug'] . ': ' . $msg;
    }
}
