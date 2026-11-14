<?php

namespace Platform\Controllers;

use Platform\Models\PackageModel;
use Platform\Models\TenantModel;
use Platform\Services\Billing;
use Platform\Services\Gateways\Ozow;
use Platform\Services\Gateways\PayFast;
use Platform\Services\Gateways\Paystack;
use Platform\Services\PlatformMailer;
use Platform\Services\PlatformSettings;

/** Public endpoints used by the marketing website. Everything comes from the admin panel. */
class Api extends BaseController
{
    private function json($data, int $code = 200)
    {
        return $this->response->setStatusCode($code)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($data);
    }

    public function preflight()
    {
        return $this->response->setStatusCode(204)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type')
            ->setHeader('Access-Control-Max-Age', '86400');
    }

    /** Pricing page + sign-up package picker. Same shape as the site's original plans.json. */
    public function plans()
    {
        $catalog = config('SaasModules')->modules;
        $packs   = array_values(array_filter(model(PackageModel::class)->ordered(true), static fn ($p) => (int) $p['is_public'] === 1));
        $web     = PlatformSettings::get('website');
        $num     = static fn ($v) => $v === null || $v === '' ? 'Unlimited' : (int) $v;

        $plans = [];
        $prev  = null;
        foreach ($packs as $p) {
            $mods = array_values(array_filter(\Platform\Models\PackageModel::modules($p), static fn ($m) => isset($catalog[$m])));
            $lim  = \Platform\Models\PackageModel::limits($p);
            $list = [];
            if ($prev) {
                $new  = array_diff($mods, \Platform\Models\PackageModel::modules($prev));
                $list = ['Everything in ' . $prev['name']];
                $mods = $new;
            }
            foreach ($mods as $m) {
                $list[] = $catalog[$m]['name'] . ($catalog[$m]['status'] === 'roadmap' ? ' (coming soon)' : '');
            }
            $plans[] = [
                'id' => $p['slug'], 'name' => $p['name'], 'tagline' => (string) $p['tagline'], 'description' => (string) $p['description'],
                'highlight' => (bool) $p['highlight'], 'badge' => $p['highlight'] ? 'Most popular' : null,
                'monthlyPrice' => (float) $p['price_monthly'], 'annualPrice' => (float) $p['price_annual'], 'trialDays' => (int) $p['trial_days'],
                'users' => $num($lim['users'] ?? null), 'inspectionsPerMonth' => $num($lim['inspections_month'] ?? null),
                'limits' => array_map($num, $lim), 'modules' => $list,
            ];
            $prev = $p;
        }

        // Comparison table: every module any public package includes, grouped by category, plus the limits.
        $cats = [];
        foreach ($catalog as $key => $m) {
            $row = ['name' => $m['name'] . ($m['status'] === 'roadmap' ? ' (coming soon)' : '')];
            $any = false;
            foreach ($packs as $p) {
                $on            = in_array($key, \Platform\Models\PackageModel::modules($p), true) || ! empty($m['core']);
                $row[$p['slug']] = $on;
                $any           = $any || $on;
            }
            if ($any) {
                $cats[$m['category']][] = $row;
            }
        }
        $limitRows = [];
        foreach (config('SaasModules')->limits as $k => $d) {
            $row = ['name' => $d['name']];
            $set = false;
            foreach ($packs as $p) {
                $v               = \Platform\Models\PackageModel::limits($p)[$k] ?? null;
                $row[$p['slug']] = $v === null || $v === '' ? 'Unlimited' : number_format((int) $v);
                $set             = $set || ($v !== null && $v !== '');
            }
            if ($set) {
                $limitRows[] = $row;
            }
        }
        $comparison = [];
        foreach ($cats as $c => $items) {
            $comparison[] = ['category' => $c, 'items' => $items];
        }
        if ($limitRows) {
            $comparison[] = ['category' => 'Limits', 'items' => $limitRows];
        }

        $addOns = [];
        foreach (preg_split('/\R/', (string) $web['addOns']) as $line) {
            if (trim($line) !== '') {
                [$n, $pr] = array_map('trim', explode('|', $line, 2) + [1 => '']);
                $addOns[] = ['name' => $n, 'price' => $pr];
            }
        }
        $faq = [];
        foreach (preg_split('/\R\s*\R/', trim((string) $web['faq'])) as $block) {
            $lines = preg_split('/\R/', trim($block), 2);
            if (trim($lines[0] ?? '') !== '') {
                $faq[] = ['q' => trim($lines[0]), 'a' => trim($lines[1] ?? '')];
            }
        }

        return $this->json([
            'currency'    => PlatformSettings::value('general', 'currency', 'ZAR'),
            'billingNote' => $web['billingNote'],
            'annualNote'  => PlatformSettings::value('billing', 'annualDiscountNote', ''),
            'vatRate'     => (float) PlatformSettings::value('general', 'vatRate', 15),
            'plans'       => $plans,
            'enterprise'  => ['name' => $web['enterpriseName'], 'tagline' => $web['enterpriseTagline'], 'cta' => $web['enterpriseCta']],
            'addOns'      => $addOns,
            'comparisonCategories' => $comparison,
            'faq'         => $faq,
            'payments'    => [
                'card'       => Paystack::enabled() || PayFast::enabled(),
                'instantEft' => Ozow::enabled() || Paystack::enabled() || PayFast::enabled(),
            ],
        ]);
    }

    public function availability()
    {
        $slug = strtolower(trim((string) ($this->request->getGet('workspace') ?? $this->request->getGet('slug'))));

        return $this->json(self::slugStatus($slug));
    }

    /** @return array{available:bool,message:string} */
    public static function slugStatus(string $slug): array
    {
        if (in_array($slug, TenantModel::RESERVED, true)) {
            return ['available' => false, 'message' => 'That address is taken — try another.'];
        }
        if (! TenantModel::slugValid($slug)) {
            return ['available' => false, 'message' => strlen($slug) < 3 ? 'Use at least 3 letters or numbers.' : 'Use lowercase letters, numbers and dashes (not at the start or end).'];
        }
        if (in_array($slug, TenantModel::RESERVED, true) || model(TenantModel::class)->withDeleted()->where('slug', $slug)->countAllResults() > 0) {
            return ['available' => false, 'message' => 'That address is taken — try another.'];
        }

        return ['available' => true, 'message' => 'Available'];
    }

    public function signupStatus(string $token)
    {
        $t = preg_match('/^[a-f0-9]{32}$/', $token) ? model(TenantModel::class)->where('signup_token', $token)->first() : null;
        if (! $t) {
            return $this->json(['status' => 'unknown'], 404);
        }

        return $this->json([
            'status' => $t['status'],
            'ready'  => (bool) $t['provisioned_at'],
            'error'  => (bool) $t['provision_error'],
            'url'    => Billing::workspaceUrl($t['slug'], 'login'),
        ]);
    }

    /** Contact form (form-encoded POST from the website). */
    public function contact()
    {
        $p = $this->request->getPost();
        if (! empty($p['website_url'])) { // honeypot
            return $this->json(['ok' => true]);
        }
        if (! service('throttler')->check('contact-' . md5((string) $this->request->getIPAddress()), 5, HOUR)) {
            return $this->json(['ok' => false, 'error' => 'Too many messages — please try again later.'], 429);
        }
        $email = trim((string) ($p['email'] ?? ''));
        $name  = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?: trim((string) ($p['name'] ?? ''));
        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['ok' => false, 'error' => 'Please give your name and a valid email address.'], 422);
        }
        $lead = [
            'source' => 'contact', 'name' => mb_substr($name, 0, 160), 'email' => mb_substr($email, 0, 160), 'phone' => mb_substr(trim((string) ($p['phone'] ?? '')), 0, 40),
            'company' => mb_substr(trim((string) ($p['company'] ?? '')), 0, 160), 'topic' => mb_substr(trim((string) ($p['business_type'] ?? '')), 0, 80),
            'message' => mb_substr(trim((string) ($p['message'] ?? '')), 0, 5000), 'ip' => $this->request->getIPAddress(), 'created_at' => date('Y-m-d H:i:s'),
        ];
        db_connect('platform')->table('leads')->insert($lead);

        $to = PlatformSettings::value('website', 'leadEmail') ?: PlatformSettings::value('general', 'supportEmail');
        if ($to) {
            PlatformMailer::send((string) $to, 'New website enquiry from ' . $name, "Name: {$name}\nEmail: {$email}\nPhone: {$lead['phone']}\nBusiness type: {$lead['topic']}\n\n{$lead['message']}\n\nSee all enquiries: " . Billing::appUrl('leads'));
        }

        return $this->json(['ok' => true]);
    }

    /** "Forgot your workspace?" — always shows the same message (doesn't reveal whether the email exists). */
    public function findWorkspace()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && service('throttler')->check('find-' . md5((string) $this->request->getIPAddress()), 5, HOUR)) {
            $list = model(TenantModel::class)->where('email', $email)->whereIn('status', ['trial', 'active', 'past_due', 'suspended'])->findAll();
            if ($list) {
                $lines = implode("\n", array_map(static fn ($t) => $t['company_name'] . ': ' . Billing::workspaceUrl($t['slug'], 'login'), $list));
                PlatformMailer::template('find_workspace', $email, ['workspace_list' => $lines, 'email' => $email]);
            }
        }

        return view('Platform\Views\public\message', [
            'title' => 'Check your email',
            'body'  => 'If ' . esc($email) . ' belongs to a workspace, we\'ve emailed you a sign-in link. It can take a minute to arrive — check your spam folder too.',
            'link'  => [rtrim(str_replace('://app.', '://', Billing::appUrl()), '/') . '/login', 'Back to sign in'],
        ]);
    }
}
