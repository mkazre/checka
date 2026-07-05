<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\Auth as ShieldAuth;

/**
 * Checka authentication configuration.
 *
 * Inherits all of Shield's defaults; we only turn off self-registration
 * (admins provision mechanics) and point every redirect at the dashboard.
 */
class Auth extends ShieldAuth
{
    /**
     * Views used by Shield — only the login view is customised (branded).
     *
     * @var array<string, string>
     */
    public array $views = [
        'login'                       => 'auth/login',
        'register'                    => '\CodeIgniter\Shield\Views\register',
        'layout'                      => '\CodeIgniter\Shield\Views\layout',
        'action_email_2fa'            => '\CodeIgniter\Shield\Views\email_2fa_show',
        'action_email_2fa_verify'     => '\CodeIgniter\Shield\Views\email_2fa_verify',
        'action_email_2fa_email'      => '\CodeIgniter\Shield\Views\Email\email_2fa_email',
        'action_email_activate_show'  => '\CodeIgniter\Shield\Views\email_activate_show',
        'action_email_activate_email' => '\CodeIgniter\Shield\Views\Email\email_activate_email',
        'magic-link-login'            => '\CodeIgniter\Shield\Views\magic_link_form',
        'magic-link-message'          => '\CodeIgniter\Shield\Views\magic_link_message',
        'magic-link-email'            => '\CodeIgniter\Shield\Views\Email\magic_link_email',
    ];

    /**
     * Allow user registration through the web UI.
     */
    public bool $allowRegistration = false;

    /**
     * The URL paths Shield redirects to after each auth event.
     *
     * @var array<string, string>
     */
    public array $redirects = [
        'register'          => 'dashboard',
        'login'             => 'dashboard',
        'logout'            => 'login',
        'force_reset'       => 'dashboard',
        'permission_denied' => 'dashboard',
        'group_denied'      => 'dashboard',
    ];
}
