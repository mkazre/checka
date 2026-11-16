<?php

namespace Platform\Controllers;

use Platform\Models\EventModel;
use Platform\Models\PackageModel;
use Platform\Models\TenantModel;
use Platform\Services\Billing;
use Platform\Services\TenantRegistry;

/**
 * Public sign-up from the website. Free trial → workspace is built straight
 * away. Pay now → address is reserved, first invoice created, customer goes to
 * the pay page; the workspace is built as soon as the payment clears.
 */
class Signup extends BaseController
{
    public function redirectToSite()
    {
        return redirect()->to(rtrim(str_replace('://app.', '://', Billing::appUrl()), '/') . '/signup');
    }

    public function create()
    {
        $p = $this->request->getPost();
        if (! empty($p['website_url'])) { // honeypot
            return $this->fail(['Something went wrong. Please try again.']);
        }
        if (! service('throttler')->check('signup-' . md5((string) $this->request->getIPAddress()), 5, HOUR)) {
            return $this->fail(['Too many sign-ups from your network in the last hour. Please try again later or contact us.']);
        }

        $pkg     = model(PackageModel::class)->where('slug', (string) ($p['plan'] ?? ''))->where('is_active', 1)->where('is_public', 1)->first();
        $slug    = strtolower(trim((string) ($p['workspace'] ?? '')));
        $email   = strtolower(trim((string) ($p['email'] ?? '')));
        $pass    = (string) ($p['password'] ?? '');
        $company = trim((string) ($p['company_name'] ?? ''));
        $cycle   = ($p['billing_cycle'] ?? '') === 'annual' ? 'annual' : 'monthly';
        $option  = (string) ($p['billing_option'] ?? 'trial');

        $errors = [];
        if (! $pkg) {
            $errors[] = 'Please choose a package.';
        }
        if ($company === '') {
            $errors[] = 'Please enter your company name.';
        }
        if (trim((string) ($p['contact_name'] ?? '')) === '') {
            $errors[] = 'Please enter your name.';
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($pass) < 8) {
            $errors[] = 'Your password must be at least 8 characters.';
        }
        $avail = Api::slugStatus($slug);
        if (! $avail['available']) {
            $errors[] = 'Workspace address: ' . $avail['message'];
        }
        if ($errors) {
            return $this->fail($errors);
        }

        $price   = Billing::price($pkg, $cycle);
        $trial   = $option === 'trial' && (int) $pkg['trial_days'] > 0;
        $payNow  = ! $trial && $price > 0;
        $token   = bin2hex(random_bytes(16));
        $tm      = model(TenantModel::class);

        $id = $tm->insert([
            'slug'          => $slug,
            'company_name'  => mb_substr($company, 0, 160),
            'contact_name'  => mb_substr(trim((string) $p['contact_name']), 0, 120),
            'email'         => $email,
            'phone'         => mb_substr(trim((string) ($p['mobile'] ?? '')), 0, 40),
            'vat_number'    => mb_substr(trim((string) ($p['vat_number'] ?? '')), 0, 40),
            'package_id'    => $pkg['id'],
            'billing_cycle' => $cycle,
            'status'        => $payNow ? 'pending_payment' : 'provisioning',
            'trial_ends_at' => $trial ? date('Y-m-d 23:59:59', strtotime('+' . (int) $pkg['trial_days'] . ' days')) : null,
            'db_mode'       => 'prefix',
            'db_prefix'     => 'pending_' . $token,
            'pending_admin' => json_encode(['email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'status' => $trial ? 'trial' : 'active']),
            'signup_token'  => $token,
            'notes'         => 'Signed up on the website (' . ($trial ? 'free trial' : ($payNow ? 'pay now: ' . $option : 'free package')) . ').',
        ]);
        $tm->update($id, ['db_prefix' => 'w' . $id . '_']);
        $t = $tm->find($id);
        TenantRegistry::write($t);
        EventModel::log($id, 'signup', 'Signed up on the website for ' . $pkg['name'] . ' (' . ($trial ? 'trial' : ($payNow ? 'pay now' : 'free')) . ').', 'customer');
        db_connect('platform')->table('leads')->insert([
            'source' => 'signup', 'name' => $t['contact_name'], 'email' => $email, 'phone' => $t['phone'], 'company' => $company,
            'topic' => $pkg['name'], 'message' => 'Workspace ' . $slug . ($trial ? ' — trial' : ' — pay now'), 'handled' => 1,
            'ip' => $this->request->getIPAddress(), 'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->set('signup_token', $token); // lets this browser sign straight in once the workspace is ready

        if ($payNow) {
            $inv = Billing::createInvoice($t, 'first', $pkg['name'] . ' — ' . ($cycle === 'annual' ? 'annual' : 'monthly') . ' subscription', $price, [
                'package_id' => $pkg['id'], 'billing_cycle' => $cycle, 'period_start' => date('Y-m-d'), 'period_end' => Billing::addCycle(date('Y-m-d'), $cycle),
            ]);

            return redirect()->to(site_url('pay/' . $inv['uid']) . '?method=' . urlencode($option) . '&welcome=' . $token);
        }

        return redirect()->to(site_url('welcome/' . $token));
    }

    public function welcome(string $token)
    {
        $t = preg_match('/^[a-f0-9]{32}$/', $token) ? model(TenantModel::class)->where('signup_token', $token)->first() : null;
        if (! $t) {
            return redirect()->to(rtrim(str_replace('://app.', '://', Billing::appUrl()), '/') . '/');
        }

        return view('Platform\Views\public\welcome', ['t' => $t, 'token' => $token, 'login' => Billing::workspaceUrl($t['slug'], 'login')]);
    }

    /** "Go to my workspace" after sign-up: signs this browser straight in (same browser, within 24 hours). */
    public function enter(string $token)
    {
        $t = preg_match('/^[a-f0-9]{32}$/', $token) ? model(TenantModel::class)->where('signup_token', $token)->first() : null;
        if (! $t) {
            return redirect()->to(rtrim(str_replace('://app.', '://', Billing::appUrl()), '/') . '/login');
        }
        $email = (string) $t['email'];
        if (! $t['provisioned_at']) {
            return redirect()->to(site_url('welcome/' . $token));
        }
        if (hash_equals((string) session('signup_token'), $token) && strtotime((string) $t['created_at']) > time() - 86400) {
            foreach (\Platform\Services\Accounts::find($email) as $m) {
                if ((int) $m['tenant']['id'] === (int) $t['id']) {
                    return redirect()->to(\Platform\Services\Accounts::ssoUrl($t, $m['user_id'], 'sign-up'));
                }
            }
        }

        return redirect()->to(Billing::workspaceUrl($t['slug'], 'login') . '?email=' . rawurlencode($email));
    }

    private function fail(array $errors)
    {
        return $this->response->setStatusCode(422)->setBody(view('Platform\Views\public\message', [
            'title'  => 'Please check your details',
            'errors' => $errors,
            'body'   => 'Go back to fix this — everything you typed is still there.',
            'back'   => true,
        ]));
    }
}
