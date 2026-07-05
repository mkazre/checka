<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Creates (or promotes) a Checka administrator.
 *
 *   php spark checka:admin
 *   php spark checka:admin --email you@wheelsturn.co.za --username admin --password 'Secret123'
 */
class CheckaAdmin extends BaseCommand
{
    protected $group       = 'Checka';
    protected $name        = 'checka:admin';
    protected $description = 'Create an administrator account for Checka (or promote an existing user to admin).';
    protected $usage       = 'checka:admin [--email] [--username] [--password]';
    protected $options     = [
        '--email'    => 'Admin email address',
        '--username' => 'Admin username',
        '--password' => 'Admin password (min 8 chars)',
    ];

    public function run(array $params): int
    {
        $email    = $params['email']    ?? CLI::prompt('Email', null, 'required|valid_email');
        $username = $params['username'] ?? CLI::prompt('Username', 'admin', 'required');
        $password = $params['password'] ?? CLI::prompt('Password', null, 'required|min_length[8]');

        /** @var UserModel $users */
        $users = model(UserModel::class);

        $existing = $users->findByCredentials(['email' => $email]);
        if ($existing !== null) {
            $existing->addGroup('admin');
            CLI::write(CLI::color("User {$email} already existed — promoted to admin.", 'yellow'));

            return EXIT_SUCCESS;
        }

        $user = new User([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
        ]);

        $users->save($user);
        $user = $users->findById($users->getInsertID());
        $user->addGroup('admin');

        // Mark email as verified/active so they can log in immediately.
        $identity = $user->getEmailIdentity();
        if ($identity !== null) {
            $identity->force_reset = 0;
        }
        $user->active = 1;
        $users->save($user);

        CLI::write(CLI::color("Admin created: {$email} (username: {$username}).", 'green'));

        return EXIT_SUCCESS;
    }
}
