<?php

namespace Platform\Controllers;

use Platform\Models\AdminModel;
use Platform\Models\EventModel;

/** Platform team (owner only) and your own account. */
class Admins extends BaseController
{
    public function index()
    {
        return $this->render('admins/index', ['admins' => model(AdminModel::class)->orderBy('name')->findAll()]);
    }

    public function store()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $name  = trim((string) $this->request->getPost('name'));
        $pass  = (string) $this->request->getPost('password');
        $role  = $this->request->getPost('role') === 'owner' ? 'owner' : 'staff';
        $m     = model(AdminModel::class);

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 10) {
            return redirect()->back()->withInput()->with('error', 'Name, a valid email and a password of at least 10 characters are required.');
        }
        if ($m->where('email', $email)->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'That email already has a login.');
        }
        $m->insert(['name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => $role, 'active' => 1]);
        EventModel::log(null, 'admin_added', 'Platform login added for ' . $email . ' (' . $role . ').');

        return redirect()->to(site_url('admins'))->with('success', 'Login created for ' . $email . '.');
    }

    public function toggle(int $id)
    {
        $m = model(AdminModel::class);
        $a = $m->find($id);
        if (! $a || (int) $id === (int) pf_admin()['id']) {
            return redirect()->back()->with('error', "You can't disable your own login.");
        }
        $m->update($id, ['active' => $a['active'] ? 0 : 1]);
        EventModel::log(null, 'admin_toggled', 'Platform login ' . $a['email'] . ($a['active'] ? ' disabled.' : ' enabled.'));

        return redirect()->back()->with('success', 'Updated.');
    }

    public function account()
    {
        return $this->render('admins/account', ['me' => pf_admin()]);
    }

    public function saveAccount()
    {
        $me   = pf_admin();
        $name = trim((string) $this->request->getPost('name'));
        $cur  = (string) $this->request->getPost('current');
        $new  = (string) $this->request->getPost('password');
        $data = ['name' => $name !== '' ? $name : $me['name']];

        if ($new !== '') {
            if (! password_verify($cur, $me['password_hash'])) {
                return redirect()->back()->with('error', 'Your current password is wrong.');
            }
            if (strlen($new) < 10) {
                return redirect()->back()->with('error', 'New password must be at least 10 characters.');
            }
            $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        }
        model(AdminModel::class)->update($me['id'], $data);
        session()->set('pf_admin_name', $data['name']);

        return redirect()->to(site_url('account'))->with('success', 'Account saved.');
    }
}
