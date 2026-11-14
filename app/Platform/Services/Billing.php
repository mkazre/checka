<?php

namespace Platform\Services;

use App\Tenancy\Tenant;
use Platform\Models\EventModel;
use Platform\Models\PackageModel;
use Platform\Models\TenantModel;
use Platform\Services\Gateways\Paystack;

/**
 * The subscription engine's core: invoices, applying payments (from any
 * gateway or marked manually), plan changes with proration, and charging
 * saved cards. Everything it does is driven by the admin-panel settings.
 */
class Billing
{
    // ------------------------------------------------------------ URLs

    /** Absolute URL on the platform host (app.<root>), from any context (platform, workspace, CLI). */
    public static function appUrl(string $path = ''): string
    {
        $base   = config('App')->baseURL;
        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $port   = parse_url($base, PHP_URL_PORT);
        $root   = (string) (Tenant::platformConfig()['rootDomain'] ?? PlatformSettings::value('general', 'rootDomain'));

        return $scheme . '://app.' . $root . ($port ? ':' . $port : '') . '/' . ltrim($path, '/');
    }

    public static function workspaceUrl(string $slug, string $path = ''): string
    {
        $base   = config('App')->baseURL;
        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $port   = parse_url($base, PHP_URL_PORT);
        $root   = (string) (Tenant::platformConfig()['rootDomain'] ?? PlatformSettings::value('general', 'rootDomain'));

        return $scheme . '://' . $slug . '.' . $root . ($port ? ':' . $port : '') . '/' . ltrim($path, '/');
    }

    public static function payUrl(array $invoice): string
    {
        return self::appUrl('pay/' . $invoice['uid']);
    }

    // ------------------------------------------------------------ Money

    public static function money($amount): string
    {
        $cur = PlatformSettings::value('general', 'currency', 'ZAR');
        $sym = ['ZAR' => 'R', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'][$cur] ?? $cur;

        return $sym . ' ' . number_format((float) $amount, 2, '.', ' ');
    }

    public static function price(array $pkg, string $cycle): float
    {
        return (float) ($cycle === 'annual' ? $pkg['price_annual'] : $pkg['price_monthly']);
    }

    public static function addCycle(string $date, string $cycle): string
    {
        return date('Y-m-d', strtotime($date . ($cycle === 'annual' ? ' +1 year' : ' +1 month')));
    }

    // ------------------------------------------------------------ Invoices

    public static function db()
    {
        return db_connect('platform');
    }

    /** @param array{package_id?:int,billing_cycle?:string,period_start?:string,period_end?:string,due_date?:string} $opt */
    public static function createInvoice(array $t, string $kind, string $description, float $subtotal, array $opt = []): array
    {
        $rate  = (float) PlatformSettings::value('general', 'vatRate', 15);
        $sub   = round($subtotal, 2);
        $vat   = round($sub * $rate / 100, 2);
        $today = date('Y-m-d');
        $db    = self::db();
        $db->table('invoices')->insert([
            'uid'           => bin2hex(random_bytes(12)),
            'tenant_id'     => $t['id'],
            'kind'          => $kind,
            'description'   => mb_substr($description, 0, 255),
            'package_id'    => $opt['package_id'] ?? null,
            'billing_cycle' => $opt['billing_cycle'] ?? null,
            'period_start'  => $opt['period_start'] ?? null,
            'period_end'    => $opt['period_end'] ?? null,
            'subtotal'      => $sub,
            'vat_rate'      => $rate,
            'vat'           => $vat,
            'total'         => $sub + $vat,
            'status'        => 'unpaid',
            'issue_date'    => $today,
            'due_date'      => $opt['due_date'] ?? $today,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        $id     = (int) $db->insertID();
        $number = PlatformSettings::value('general', 'invoicePrefix', 'CP-') . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        $db->table('invoices')->where('id', $id)->update(['number' => $number]);
        EventModel::log((int) $t['id'], 'invoice', 'Invoice ' . $number . ' issued: ' . $description . ' — ' . self::money($sub + $vat) . '.');

        return self::invoice($id);
    }

    public static function invoice(int $id): ?array
    {
        return self::db()->table('invoices')->where('id', $id)->get()->getRowArray() ?: null;
    }

    public static function invoiceByUid(string $uid): ?array
    {
        return preg_match('/^[a-f0-9]{24}$/', $uid) ? (self::db()->table('invoices')->where('uid', $uid)->get()->getRowArray() ?: null) : null;
    }

    /** @return list<array<string,mixed>> */
    public static function invoicesFor(int $tenantId): array
    {
        return self::db()->table('invoices')->where('tenant_id', $tenantId)->orderBy('id', 'DESC')->get()->getResultArray();
    }

    public static function openInvoice(int $tenantId, ?string $kind = null): ?array
    {
        $b = self::db()->table('invoices')->where('tenant_id', $tenantId)->where('status', 'unpaid');
        if ($kind) {
            $b->where('kind', $kind);
        }

        return $b->orderBy('id', 'ASC')->get(1)->getRowArray() ?: null;
    }

    public static function sendInvoice(array $t, array $inv): bool
    {
        return PlatformMailer::template('invoice', (string) $t['email'], self::vars($t, $inv));
    }

    // ------------------------------------------------------------ Payments

    /**
     * Record a payment and apply what it pays for. Idempotent per gateway reference.
     * Returns false if the reference was already used or the invoice isn't payable.
     */
    public static function applyPayment(array $inv, string $gateway, string $reference, float $amount, array $details = [], ?string $actor = null): bool
    {
        $db = self::db();
        if ($db->table('payments')->where('gateway', $gateway)->where('reference', $reference)->countAllResults() > 0) {
            return false;
        }
        $inv = self::invoice((int) $inv['id']);
        if (! $inv || $inv['status'] !== 'unpaid') {
            return false;
        }
        if (round($amount, 2) + 0.01 < (float) $inv['total']) {
            EventModel::log((int) $inv['tenant_id'], 'payment_short', 'Payment ' . $reference . ' of ' . self::money($amount) . ' is less than invoice ' . $inv['number'] . ' (' . self::money($inv['total']) . '). Not applied.', 'system');

            return false;
        }

        $db->table('payments')->insert([
            'invoice_id' => $inv['id'], 'tenant_id' => $inv['tenant_id'], 'gateway' => $gateway, 'reference' => mb_substr($reference, 0, 120),
            'amount' => round($amount, 2), 'details' => json_encode($details), 'actor' => $actor ?? (is_cli() ? 'system' : (session('pf_admin_name') ?: 'customer')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('invoices')->where('id', $inv['id'])->update(['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);

        $tm = model(TenantModel::class);
        $t  = $tm->find($inv['tenant_id']);
        if (! $t) {
            return true;
        }
        $wasStatus = $t['status'];
        $data      = [];
        $before    = TenantRegistry::effective($t)['modules'];

        switch ($inv['kind']) {
            case 'first':
            case 'renewal':
                $data['current_period_end'] = $inv['period_end'] ?: self::addCycle(date('Y-m-d'), $t['billing_cycle']);
                if ($inv['package_id']) {
                    $data['package_id'] = $inv['package_id'];
                }
                if ($inv['billing_cycle']) {
                    $data['billing_cycle'] = $inv['billing_cycle'];
                }
                $data['next_package_id'] = null;
                $data['next_cycle']      = null;
                $data['trial_ends_at']   = null;
                break;

            case 'upgrade':
                $data['package_id'] = $inv['package_id'];
                if ($inv['billing_cycle']) {
                    $data['billing_cycle'] = $inv['billing_cycle'];
                }
                $data['next_package_id'] = null;
                break;
        }

        if ($wasStatus === 'pending_payment') {
            // Pay-now sign-up: build the workspace now, live as "active".
            $pending           = (array) json_decode((string) $t['pending_admin'], true);
            $pending['status'] = 'active';
            $data['pending_admin'] = json_encode($pending);
            $data['status']        = 'provisioning';
        } elseif (in_array($wasStatus, ['trial', 'past_due'], true) || ($wasStatus === 'suspended' && PlatformSettings::value('billing', 'autoUnsuspend', 1))) {
            if (self::openOverdue((int) $t['id']) === null) {
                $data['status']       = 'active';
                $data['suspended_at'] = null;
            }
        }

        if ($data) {
            $tm->update($t['id'], $data);
        }
        $t = $tm->find($t['id']);
        TenantRegistry::write($t);
        $added = array_diff(TenantRegistry::effective($t)['modules'], $before);
        if ($added && $t['provisioned_at']) {
            TenantData::enableModules($t, $added);
        }

        EventModel::log((int) $t['id'], 'payment', 'Payment of ' . self::money($amount) . ' received for ' . $inv['number'] . ' via ' . $gateway . '.', $actor ?? 'system');
        $vars = self::vars($t, self::invoice((int) $inv['id']));
        PlatformMailer::template('receipt', (string) $t['email'], $vars);
        if ($wasStatus === 'suspended' && $t['status'] === 'active') {
            EventModel::log((int) $t['id'], 'unsuspended', 'Workspace reactivated after payment.', 'system');
            PlatformMailer::template('unsuspended', (string) $t['email'], $vars);
        }

        return true;
    }

    /** The oldest unpaid invoice that is already past its due date. */
    public static function openOverdue(int $tenantId): ?array
    {
        return self::db()->table('invoices')->where('tenant_id', $tenantId)->where('status', 'unpaid')
            ->where('due_date <', date('Y-m-d'))->orderBy('due_date', 'ASC')->get(1)->getRowArray() ?: null;
    }

    /** Try the card saved with Paystack. */
    public static function chargeSavedCard(array $t, array $inv): bool
    {
        if (! $t['paystack_auth'] || ! Paystack::enabled()) {
            return false;
        }
        $ref  = $inv['uid'] . '-r' . time();
        $data = Paystack::chargeAuthorization($t['paystack_auth'], (string) ($t['paystack_email'] ?: $t['email']), (float) $inv['total'], $ref, $inv['uid']);
        if (($data['status'] ?? '') === 'success') {
            return self::applyPayment($inv, 'paystack', (string) $data['reference'], ((int) $data['amount']) / 100, ['auto_charge' => true], 'system');
        }
        EventModel::log((int) $t['id'], 'charge_failed', 'Automatic card charge for ' . $inv['number'] . ' failed: ' . ($data['gateway_response'] ?? Paystack::$lastError ?: 'declined') . '.', 'system');

        return false;
    }

    /** Remember a reusable Paystack card for renewals. */
    public static function saveCard(int $tenantId, array $paystackData): void
    {
        $auth = $paystackData['authorization'] ?? [];
        if (empty($auth['reusable']) || empty($auth['authorization_code'])) {
            return;
        }
        model(TenantModel::class)->update($tenantId, [
            'paystack_auth'  => $auth['authorization_code'],
            'paystack_email' => $paystackData['customer']['email'] ?? null,
            'card_label'     => trim(ucfirst((string) ($auth['card_type'] ?? 'Card')) . ' •••• ' . ($auth['last4'] ?? '') . ' ' . ($auth['exp_month'] ?? '') . '/' . substr((string) ($auth['exp_year'] ?? ''), -2)),
        ]);
    }

    // ------------------------------------------------------------ Plan changes

    /**
     * Change package/cycle from the workspace's subscription page.
     * Trial: switch now. Paid upgrade: prorated invoice (switches when paid).
     * Downgrade / cycle change: scheduled for the next renewal.
     *
     * @return array{type:string,invoice?:array,message:string}
     */
    public static function changePlan(array $t, array $pkg, string $cycle): array
    {
        $tm  = model(TenantModel::class);
        $cur = $t['package_id'] ? model(PackageModel::class)->find($t['package_id']) : null;

        if ($t['status'] === 'trial' || ! $cur || ! $t['current_period_end'] || self::price($cur, $t['billing_cycle']) <= 0) {
            $before = TenantRegistry::effective($t)['modules'];
            $tm->update($t['id'], ['package_id' => $pkg['id'], 'billing_cycle' => $cycle, 'next_package_id' => null, 'next_cycle' => null]);
            $t = $tm->find($t['id']);
            TenantRegistry::write($t);
            TenantData::enableModules($t, array_diff(TenantRegistry::effective($t)['modules'], $before));
            EventModel::log((int) $t['id'], 'package_changed', 'Switched to ' . $pkg['name'] . ' (' . $cycle . ').', 'customer');

            // Free package → paid package outside a trial: bill straight away.
            if ($t['status'] !== 'trial' && self::price($pkg, $cycle) > 0) {
                $inv = self::createInvoice($t, 'renewal', $pkg['name'] . ' — ' . ($cycle === 'annual' ? 'annual' : 'monthly') . ' subscription', self::price($pkg, $cycle), [
                    'package_id' => $pkg['id'], 'billing_cycle' => $cycle, 'period_start' => date('Y-m-d'), 'period_end' => self::addCycle(date('Y-m-d'), $cycle),
                ]);

                return ['type' => 'invoice', 'invoice' => $inv, 'message' => 'Switched to ' . $pkg['name'] . '. Please pay the invoice to keep it.'];
            }

            return ['type' => 'now', 'message' => 'You are now on ' . $pkg['name'] . '.'];
        }

        $oldDaily  = self::price($cur, $t['billing_cycle']) / ($t['billing_cycle'] === 'annual' ? 365 : 30);
        $newDaily  = self::price($pkg, $t['billing_cycle']) / ($t['billing_cycle'] === 'annual' ? 365 : 30);
        $remaining = max(0, (int) ceil((strtotime($t['current_period_end']) - strtotime(date('Y-m-d'))) / 86400));
        $diff      = round(($newDaily - $oldDaily) * $remaining, 2);

        if ($cycle === $t['billing_cycle'] && $diff > 0) {
            // Upgrade now, pay the difference for the rest of this period.
            self::db()->table('invoices')->where('tenant_id', $t['id'])->where('kind', 'upgrade')->where('status', 'unpaid')->update(['status' => 'void']);
            $inv = self::createInvoice($t, 'upgrade', 'Upgrade to ' . $pkg['name'] . ' — ' . $remaining . ' day(s) remaining this period', $diff, [
                'package_id' => $pkg['id'], 'billing_cycle' => $cycle, 'period_start' => date('Y-m-d'), 'period_end' => $t['current_period_end'],
            ]);

            return ['type' => 'invoice', 'invoice' => $inv, 'message' => 'Pay the prorated difference to switch to ' . $pkg['name'] . ' straight away.'];
        }

        $tm->update($t['id'], ['next_package_id' => $pkg['id'], 'next_cycle' => $cycle]);
        TenantRegistry::write($tm->find($t['id']));
        EventModel::log((int) $t['id'], 'package_scheduled', 'Change to ' . $pkg['name'] . ' (' . $cycle . ') scheduled for ' . $t['current_period_end'] . '.', 'customer');

        return ['type' => 'scheduled', 'message' => 'Your plan changes to ' . $pkg['name'] . ' (' . $cycle . ') on ' . date('d M Y', strtotime($t['current_period_end'])) . '.'];
    }

    // ------------------------------------------------------------ Email placeholders

    public static function vars(array $t, ?array $inv = null): array
    {
        $pkg   = $t['package_id'] ? model(PackageModel::class)->find($t['package_id']) : null;
        $grace = (int) PlatformSettings::value('billing', 'graceDays', 7);
        $since = $t['suspended_at'] ?: $t['cancelled_at'];
        $ret   = (int) PlatformSettings::value('billing', 'retentionDays', 90);

        return [
            'platform'       => PlatformSettings::value('general', 'platformName', 'CheckA+'),
            'company'        => $t['company_name'],
            'contact'        => $t['contact_name'] ?: $t['company_name'],
            'email'          => $t['email'],
            'package'        => $pkg['name'] ?? '',
            'workspace_url'  => self::workspaceUrl($t['slug']),
            'amount'         => $inv ? self::money($inv['total']) : '',
            'invoice_no'     => $inv['number'] ?? '',
            'due_date'       => $inv ? date('d M Y', strtotime($inv['due_date'])) : '',
            'pay_url'        => $inv ? self::payUrl($inv) : self::workspaceUrl($t['slug'], 'subscription'),
            'trial_end'      => $t['trial_ends_at'] ? date('d M Y', strtotime($t['trial_ends_at'])) : '',
            'days_overdue'   => $inv ? max(0, (int) floor((time() - strtotime($inv['due_date'])) / 86400)) : 0,
            'suspend_date'   => $inv ? date('d M Y', strtotime($inv['due_date'] . ' +' . $grace . ' days')) : '',
            'suspended_date' => $since ? date('d M Y', strtotime($since)) : '',
            'delete_date'    => $since && $ret ? date('d M Y', strtotime($since . ' +' . $ret . ' days')) : '',
        ];
    }
}
