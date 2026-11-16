<?php

namespace Platform\Controllers;

use Platform\Models\TenantModel;
use Platform\Services\Accounts;
use Platform\Services\Billing;
use Platform\Services\PlatformMailer;
use Platform\Services\PlatformSettings;
use Platform\Services\TenantRegistry;
use Throwable;

/**
 * Central customer sign-in for the website's Log in page: email + password
 * (no workspace address needed), Google, forgot password and forgot email.
 * The JSON endpoints are called from checkaplus.co.za/login.
 */
class Account extends BaseController
{
    private function json(array $data, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', 'no-store')->setJSON($data);
    }

    private function siteUrl(string $path = ''): string
    {
        return rtrim(str_replace('://app.', '://', Billing::appUrl()), '/') . '/' . ltrim($path, '/');
    }

    private function throttle(string $what, int $max, int $seconds): bool
    {
        return service('throttler')->check($what . '-' . md5((string) $this->request->getIPAddress()), $max, $seconds);
    }

    /** What the login page needs to know (e.g. show the Google button?). */
    public function config()
    {
        return $this->json(['google' => self::googleReady()]);
    }

    // ---------------------------------------------------------------- email + password

    public function login()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $pass  = (string) $this->request->getPost('password');
        if (! $this->throttle('acctlogin', 10, 300)) {
            return $this->json(['error' => 'Too many attempts. Please wait a few minutes and try again.'], 429);
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
            return $this->json(['error' => 'Enter your email address and password.'], 422);
        }

        $ok = [];
        foreach (Accounts::find($email) as $m) {
            if ($m['hash'] !== '' && password_verify($pass, $m['hash'])) {
                $ok[] = $m;
            }
        }
        if (count($ok) === 1) {
            return $this->json(['redirect' => Accounts::ssoUrl($ok[0]['tenant'], $ok[0]['user_id'])]);
        }
        if (count($ok) > 1) {
            return $this->json(['choose' => array_map(static fn ($m) => [
                'name' => $m['tenant']['company_name'], 'address' => $m['tenant']['slug'] . '.' . PlatformSettings::value('general', 'rootDomain'),
                'url'  => Accounts::ssoUrl($m['tenant'], $m['user_id']),
            ], $ok)]);
        }

        // Signed up, but the workspace isn't built yet / awaiting payment.
        foreach (Accounts::pending($email) as $m) {
            if ($m['hash'] !== '' && password_verify($pass, $m['hash'])) {
                $t = $m['tenant'];
                if ($t['status'] === 'pending_payment') {
                    $inv = Billing::openInvoice((int) $t['id']);

                    return $this->json(['info' => 'Your workspace is reserved and will be ready as soon as your first payment is received.', 'redirect_label' => 'Complete payment', 'url' => $inv ? Billing::payUrl($inv) : null]);
                }

                return $this->json(['info' => 'Your workspace is being set up — it usually takes under a minute. Please try again shortly.']);
            }
        }

        usleep(random_int(200000, 400000));

        return $this->json(['error' => 'That email and password don\'t match an account. Check them, or use "Forgot password?".'], 401);
    }

    // ---------------------------------------------------------------- forgot password / reset

    public function forgotPassword()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $this->throttle('acctforgot', 5, 3600) && (Accounts::find($email) || Accounts::pending($email))) {
            $token = Accounts::issueReset($email, (string) $this->request->getIPAddress());
            $name  = PlatformSettings::value('general', 'platformName', 'CheckA+');
            PlatformMailer::send($email, 'Reset your ' . $name . ' password',
                "Hi,\n\nSomeone (hopefully you) asked to reset the password for {$email}.\n\nChoose a new password here (the link works for 1 hour):\n"
                . site_url('account/reset/' . $token) . "\n\nIf you didn't ask for this, you can ignore this email — your password stays the same.");
        }

        return $this->json(['ok' => true, 'message' => 'If that email has an account, a password reset link is on its way. Check your inbox (and spam folder).']);
    }

    public function resetForm(string $token)
    {
        $email = Accounts::resetEmail($token);

        return view('Platform\Views\public\reset', ['token' => $token, 'email' => $email, 'error' => session()->getFlashdata('error'), 'done' => false]);
    }

    public function reset(string $token)
    {
        $email = Accounts::resetEmail($token);
        if (! $email) {
            return redirect()->to(site_url('account/reset/' . $token));
        }
        $p1 = (string) $this->request->getPost('password');
        $p2 = (string) $this->request->getPost('password_confirm');
        if (strlen($p1) < 8) {
            return redirect()->back()->with('error', 'Use at least 8 characters.');
        }
        if ($p1 !== $p2) {
            return redirect()->back()->with('error', 'The two passwords don\'t match.');
        }
        Accounts::setPassword($email, $p1);
        Accounts::useReset($token);

        // Sign straight in if there's exactly one workspace.
        $found = Accounts::find($email);
        $next  = count($found) === 1 ? Accounts::ssoUrl($found[0]['tenant'], $found[0]['user_id'], 'password reset') : $this->siteUrl('login');

        return view('Platform\Views\public\reset', ['token' => $token, 'email' => $email, 'error' => null, 'done' => true, 'next' => $next]);
    }

    // ---------------------------------------------------------------- forgot email

    public function forgotEmail()
    {
        $q = strtolower(trim((string) $this->request->getPost('lookup')));
        if ($q === '' || ! $this->throttle('acctforgotemail', 5, 3600)) {
            return $this->json(['error' => 'Enter your workspace address, company name or phone number.'], 422);
        }
        $slug = preg_replace('/\.' . preg_quote((string) PlatformSettings::value('general', 'rootDomain'), '/') . '.*$/', '', preg_replace('#^https?://#', '', $q));
        $tm   = model(TenantModel::class);
        $t    = $tm->where('slug', $slug)->first();
        if (! $t) {
            $digits = preg_replace('/\D/', '', $q);
            foreach ($tm->whereIn('status', ['trial', 'active', 'past_due', 'suspended', 'provisioning', 'pending_payment'])->findAll() as $row) {
                if (strtolower($row['company_name']) === $q || ($digits !== '' && strlen($digits) >= 9 && preg_replace('/\D/', '', (string) $row['phone']) === $digits)) {
                    $t = $row;
                    break;
                }
            }
        }
        if (! $t || in_array($t['status'], ['cancelled'], true)) {
            return $this->json(['error' => 'We couldn\'t find a workspace for that. Try your workspace address (e.g. yourcompany), exact company name or the phone number you signed up with.'], 404);
        }

        $name = PlatformSettings::value('general', 'platformName', 'CheckA+');
        PlatformMailer::send((string) $t['email'], 'Your ' . $name . ' sign-in email',
            "Hi,\n\nSomeone asked which email signs in to the {$t['company_name']} workspace.\n\nThe account owner's sign-in email is this one: {$t['email']}\nSign in at: " . $this->siteUrl('login')
            . "\n\nTeam members sign in with the email their administrator added them with — ask your administrator if you're unsure.\n\nForgot your password too? Use \"Forgot password?\" on the sign-in page.");

        return $this->json(['ok' => true, 'message' => 'The sign-in email for ' . $t['company_name'] . ' is ' . Accounts::mask((string) $t['email']) . ' — we\'ve sent a reminder to that inbox. Team members: ask your workspace administrator which email they added you with.']);
    }

    // ---------------------------------------------------------------- Google

    public static function googleReady(): bool
    {
        $a = PlatformSettings::get('auth');

        return ! empty($a['googleEnabled']) && $a['googleClientId'] !== '' && $a['googleClientSecret'] !== '';
    }

    public function google()
    {
        if (! self::googleReady()) {
            return redirect()->to($this->siteUrl('login'));
        }
        $state = bin2hex(random_bytes(16));
        session()->set('g_state', $state);

        return redirect()->to('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => PlatformSettings::value('auth', 'googleClientId'), 'redirect_uri' => site_url('account/google/callback'),
            'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state, 'prompt' => 'select_account',
        ]));
    }

    public function googleCallback()
    {
        $fail = fn (string $msg) => view('Platform\Views\public\message', ['title' => 'Google sign-in', 'body' => esc($msg), 'link' => [$this->siteUrl('login'), 'Back to sign in']]);
        $state = (string) $this->request->getGet('state');
        if (! self::googleReady() || $state === '' || ! hash_equals((string) session('g_state'), $state) || ! $this->request->getGet('code')) {
            return $fail('That Google sign-in didn\'t complete. Please try again.');
        }
        session()->remove('g_state');

        try {
            $res  = \Config\Services::curlrequest(['http_errors' => false, 'timeout' => 20], null, null, false)->post('https://oauth2.googleapis.com/token', ['form_params' => [
                'code' => $this->request->getGet('code'), 'client_id' => PlatformSettings::value('auth', 'googleClientId'),
                'client_secret' => PlatformSettings::value('auth', 'googleClientSecret'), 'redirect_uri' => site_url('account/google/callback'), 'grant_type' => 'authorization_code',
            ]]);
            $tok = json_decode($res->getBody(), true);
            // The ID token comes straight from Google's token endpoint over TLS, so its claims can be trusted (OpenID Connect §3.1.3.7).
            $claims = isset($tok['id_token']) ? json_decode(base64_decode(strtr(explode('.', $tok['id_token'])[1] ?? '', '-_', '+/')), true) : null;
        } catch (Throwable $e) {
            log_message('error', 'Google sign-in: ' . $e->getMessage());

            return $fail('We couldn\'t reach Google right now. Please try again, or sign in with your email and password.');
        }
        if (! is_array($claims) || ($claims['aud'] ?? '') !== PlatformSettings::value('auth', 'googleClientId')
            || ! in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
            || empty($claims['email_verified']) || ($claims['exp'] ?? 0) < time()) {
            return $fail('Google didn\'t confirm that email address. Please sign in with your email and password.');
        }

        $email = strtolower((string) $claims['email']);
        $found = Accounts::find($email);
        if (count($found) === 1) {
            return redirect()->to(Accounts::ssoUrl($found[0]['tenant'], $found[0]['user_id'], 'Google'));
        }
        if ($found) {
            return view('Platform\Views\public\choose', ['email' => $email, 'items' => array_map(static fn ($m) => [
                'name' => $m['tenant']['company_name'], 'address' => $m['tenant']['slug'] . '.' . PlatformSettings::value('general', 'rootDomain'),
                'url'  => Accounts::ssoUrl($m['tenant'], $m['user_id'], 'Google'),
            ], $found)]);
        }
        if ($pending = Accounts::pending($email)) {
            return $fail('Your workspace for ' . $email . ' is still being set up or waiting for payment. Please try again in a minute.');
        }

        return view('Platform\Views\public\message', [
            'title' => 'No workspace for ' . esc($email),
            'body'  => 'There\'s no CheckA+ workspace that uses this Google account\'s email. If your workspace uses a different email, sign in with that — or start a free trial.',
            'link'  => [$this->siteUrl('signup'), 'Start a free trial'],
        ]);
    }
}
