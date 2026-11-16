<?php

namespace Platform\Controllers;

use Platform\Models\EventModel;
use Platform\Services\PlatformMailer;
use Platform\Services\PlatformSettings;
use Platform\Services\TenantRegistry;

/** General, billing rules (the subscription engine), email templates, payment gateways and email transport. */
class Settings extends BaseController
{
    public const TABS = [
        'general'   => 'General',
        'billing'   => 'Billing rules',
        'templates' => 'Email templates',
        'gateways'  => 'Payment gateways',
        'mail'      => 'Email sending',
        'website'   => 'Website',
        'auth'      => 'Customer sign-in',
    ];

    /** Fields never sent back to the browser; leaving them empty keeps the saved value. */
    private const SECRETS = ['secretKey', 'merchantKey', 'passphrase', 'privateKey', 'apiKey', 'smtpPass', 'brevoKey', 'googleClientSecret'];

    public function index(string $tab = 'general')
    {
        if (! isset(self::TABS[$tab])) {
            return redirect()->to(site_url('settings'));
        }
        $data = PlatformSettings::get($tab);

        return $this->render('settings/' . $tab, [
            'tab'      => $tab,
            'tabs'     => self::TABS,
            's'        => $this->mask($data),
            'secretSet' => $this->secretFlags($data),
        ]);
    }

    public function save(string $tab)
    {
        if (! isset(self::TABS[$tab]) || (pf_admin()['role'] ?? '') !== 'owner' && in_array($tab, ['gateways', 'mail', 'general', 'auth'], true)) {
            return redirect()->back()->with('error', 'Only the owner can change these settings.');
        }
        $defaults = PlatformSettings::DEFAULTS[$tab];
        $current  = PlatformSettings::get($tab);
        $post     = (array) $this->request->getPost('s');
        $clean    = $this->clean($defaults, $post, $current);

        if ($tab === 'general') {
            $clean['rootDomain'] = strtolower(trim((string) $clean['rootDomain']));
            if (! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $clean['rootDomain'])) {
                return redirect()->back()->with('error', 'Enter a valid root domain, e.g. checkaplus.co.za');
            }
        }
        if ($tab === 'billing') {
            foreach (['remindBefore', 'remindAfter', 'trialRemindBefore'] as $k) {
                $clean[$k] = implode(',', PlatformSettings::days((string) $clean[$k]));
            }
            $clean['trialEndAction'] = in_array($clean['trialEndAction'], ['suspend', 'downgrade'], true) ? $clean['trialEndAction'] : 'suspend';
        }
        if ($tab === 'mail') {
            $clean['transport'] = in_array($clean['transport'], ['mail', 'smtp', 'brevo'], true) ? $clean['transport'] : 'mail';
        }

        PlatformSettings::set($tab, $clean);
        if ($tab === 'general') {
            TenantRegistry::writePlatform();
        }
        EventModel::log(null, 'settings', self::TABS[$tab] . ' settings updated.');

        return redirect()->to(site_url('settings/' . $tab))->with('success', self::TABS[$tab] . ' saved.');
    }

    public function testEmail()
    {
        $to  = (string) $this->request->getPost('to') ?: (pf_admin()['email'] ?? '');
        $key = (string) $this->request->getPost('template');
        $vars = [
            'company' => 'Sample Motors', 'contact' => 'Thandi', 'email' => $to, 'package' => 'Workshop Pro',
            'workspace_url' => pf_workspace_url('sample'), 'amount' => pf_money(499), 'invoice_no' => 'CP-000123',
            'due_date' => date('d M Y', strtotime('+7 days')), 'pay_url' => site_url('pay/sample'), 'trial_end' => date('d M Y', strtotime('+3 days')),
            'days_overdue' => 3, 'suspend_date' => date('d M Y', strtotime('+4 days')), 'suspended_date' => date('d M Y'), 'delete_date' => date('d M Y', strtotime('+7 days')),
        ];
        $ok = isset(PlatformSettings::TEMPLATE_LABELS[$key])
            ? PlatformMailer::template($key, $to, $vars)
            : PlatformMailer::send($to, 'Test email from your platform', "This is a test.\n\nIf you can read this, email sending works.");

        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Test email sent to ' . $to . '.' : 'Sending failed: ' . PlatformMailer::$lastError);
    }

    /** Cast posted values to the defaults' types, recursively; keep saved secrets when left blank. */
    private function clean(array $defaults, array $post, array $current): array
    {
        $out = [];
        foreach ($defaults as $k => $def) {
            if (is_array($def)) {
                $out[$k] = $this->clean($def, (array) ($post[$k] ?? []), (array) ($current[$k] ?? []));
                continue;
            }
            $v = $post[$k] ?? null;
            if (in_array($k, self::SECRETS, true) && trim((string) $v) === '') {
                $out[$k] = $current[$k] ?? $def;
                continue;
            }
            if (is_int($def) && in_array($k, ['enabled', 'sandbox', 'remindOnDue', 'autoSuspend', 'autoUnsuspend', 'googleEnabled'], true)) {
                $out[$k] = ! empty($v) ? 1 : 0;
            } elseif (is_int($def)) {
                $out[$k] = max(0, (int) $v);
            } else {
                $out[$k] = trim(str_replace("\r\n", "\n", (string) $v));
            }
        }

        return $out;
    }

    private function mask(array $d): array
    {
        foreach ($d as $k => $v) {
            $d[$k] = is_array($v) ? $this->mask($v) : (in_array($k, self::SECRETS, true) ? '' : $v);
        }

        return $d;
    }

    private function secretFlags(array $d, string $prefix = ''): array
    {
        $out = [];
        foreach ($d as $k => $v) {
            if (is_array($v)) {
                $out += $this->secretFlags($v, $prefix . $k . '.');
            } elseif (in_array($k, self::SECRETS, true)) {
                $out[$prefix . $k] = (string) $v !== '';
            }
        }

        return $out;
    }
}
