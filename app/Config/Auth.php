<?php
declare(strict_types=1);
namespace Config;
use CodeIgniter\Shield\Config\Auth as ShieldAuth;
class Auth extends ShieldAuth
{
    public bool $allowRegistration = false;
    public array $redirects = [
        'register'          => 'dashboard',
        'login'             => 'dashboard',
        'logout'            => 'login',
        'force_reset'       => 'dashboard',
        'permission_denied' => 'dashboard',
        'group_denied'      => 'dashboard',
    ];
}
