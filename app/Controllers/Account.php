<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserModel;

/** "My account" for every workspace user: display name, sign-in email and password. */
class Account extends BaseController
{
    public function index()
    {
        $u = auth()->user();

        return view('account/index', [
            'user'  => $u,
            'name'  => user_display_name($u),
            'title' => 'My account',
        ]);
    }

    public function save()
    {
        $u       = auth()->user();
        $users   = model(UserModel::class);
        $name    = trim((string) $this->request->getPost('name'));
        $email   = strtolower(trim((string) $this->request->getPost('email')));
        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('password');
        $confirm = (string) $this->request->getPost('password_confirm');

        $emailChanged = $email !== '' && $email !== strtolower((string) $u->email);
        if (($emailChanged || $new !== '') && ! $this->checkPassword($u, $current)) {
            return redirect()->back()->withInput()->with('error', 'Enter your current password to change your email or password.');
        }
        if ($emailChanged) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->back()->withInput()->with('error', 'That email address isn\'t valid.');
            }
            if ($users->findByCredentials(['email' => $email]) !== null) {
                return redirect()->back()->withInput()->with('error', 'Another user in this workspace already uses that email.');
            }
        }
        if ($new !== '') {
            if (strlen($new) < 8) {
                return redirect()->back()->withInput()->with('error', 'Your new password must be at least 8 characters.');
            }
            if ($new !== $confirm) {
                return redirect()->back()->withInput()->with('error', 'The two new passwords don\'t match.');
            }
        }

        if ($name !== '') {
            setting()->set('Profile.name', mb_substr($name, 0, 80), 'user:' . $u->id);
        }
        if ($emailChanged || $new !== '') {
            if ($emailChanged) {
                $u->email = $email;
            }
            if ($new !== '') {
                $u->password = $new;
            }
            if (! $users->save($u)) {
                return redirect()->back()->withInput()->with('errors', $users->errors());
            }
        }

        return redirect()->to(site_url('account'))->with('message', 'Your account has been updated.'
            . ($emailChanged ? ' Sign in with ' . $email . ' from now on.' : ''));
    }

    private function checkPassword($user, string $password): bool
    {
        if ($password === '') {
            return false;
        }
        $identity = $user->getEmailIdentity();

        return $identity !== null && password_verify($password, (string) $identity->secret2);
    }
}
