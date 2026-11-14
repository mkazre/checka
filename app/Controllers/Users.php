<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * User management. Admin-only (group:admin filter in Routes).
 */
class Users extends BaseController
{
    public function index()
    {
        $users = model(UserModel::class)->orderBy('id', 'ASC')->findAll();

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'groups'   => $user->getGroups(),
                'active'   => $user->active,
            ];
        }

        return view('users/index', [
            'title' => 'Users',
            'users' => $rows,
        ]);
    }

    public function create()
    {
        $users = model(UserModel::class);

        if (limit_reached('users', $users->countAllResults())) {
            return redirect()->back()->withInput()->with('error', 'Your package allows ' . plan_limit('users') . ' users. Upgrade your subscription to add more.');
        }

        $user = new User([
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ]);

        if (! $users->save($user)) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        $user  = $users->findById($users->getInsertID());
        $group = $this->request->getPost('group') === 'admin' ? 'admin' : 'mechanic';
        $user->addGroup($group);
        $user->active = 1;
        $users->save($user);

        return redirect()->to('users')->with('message', 'User created.');
    }

    public function setRole(int $id)
    {
        $users = model(UserModel::class);
        $user  = $users->findById($id);
        if ($user === null) {
            return redirect()->to('users')->with('error', 'User not found.');
        }

        $group = $this->request->getPost('group') === 'admin' ? 'admin' : 'mechanic';
        foreach (['admin', 'mechanic'] as $g) {
            if ($user->inGroup($g)) {
                $user->removeGroup($g);
            }
        }
        $user->addGroup($group);

        return redirect()->to('users')->with('message', 'Role updated.');
    }

    public function delete(int $id)
    {
        if ((int) auth()->id() === $id) {
            return redirect()->to('users')->with('error', 'You cannot delete your own account.');
        }
        model(UserModel::class)->deleteById($id);

        return redirect()->to('users')->with('message', 'User deleted.');
    }
}
