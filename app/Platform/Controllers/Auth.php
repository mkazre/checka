<?php

namespace Platform\Controllers;

use Platform\Models\AdminModel;

/** Sign-in for platform admins (separate from workspace logins). */
class Auth extends BaseController
{
    public function login()
    {
        if (pf_admin()) {
            return redirect()->to(site_url('/'));
        }

        return $this->render('login');
    }

    public function attempt()
    {
        $throttler = service('throttler');
        $key       = 'pflogin-' . md5((string) $this->request->getIPAddress());
        if (! $throttler->check($key, 6, MINUTE * 5)) {
            return redirect()->back()->withInput()->with('error', 'Too many attempts. Try again in a few minutes.');
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $pass  = (string) $this->request->getPost('password');
        $admin = model(AdminModel::class)->where('email', $email)->first();

        if (! $admin || ! $admin['active'] || ! password_verify($pass, (string) $admin['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Wrong email or password.');
        }

        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            model(AdminModel::class)->update($admin['id'], ['password_hash' => password_hash($pass, PASSWORD_DEFAULT)]);
        }
        model(AdminModel::class)->update($admin['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        session()->regenerate(true);
        session()->set(['pf_admin_id' => (int) $admin['id'], 'pf_admin_name' => $admin['name']]);
        $return = (string) session('pf_return');
        session()->remove('pf_return');

        return redirect()->to($return && str_starts_with($return, base_url()) ? $return : site_url('/'));
    }

    public function logout()
    {
        session()->remove(['pf_admin_id', 'pf_admin_name']);
        session()->regenerate(true);

        return redirect()->to(site_url('login'))->with('success', 'Signed out.');
    }
}
