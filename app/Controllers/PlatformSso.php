<?php

namespace App\Controllers;

use App\Tenancy\Tenant;

/**
 * "Log in as admin" from the platform panel. The platform writes a one-time
 * token file (valid 60 seconds) into the shared registry; this workspace
 * consumes it and signs in as its first administrator.
 */
class PlatformSso extends BaseController
{
    public function consume(string $token)
    {
        if (! Tenant::active() || ! preg_match('/^[a-f0-9]{48}$/', $token)) {
            return redirect()->to(site_url('login'));
        }
        $file = Tenant::baseWritable() . '/platform/sso/' . $token . '.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        @unlink($file);

        if (! is_array($data) || ($data['slug'] ?? '') !== Tenant::slug() || ($data['expires'] ?? 0) < time()) {
            return redirect()->to(site_url('login'))->with('error', 'That sign-in link has expired — please sign in below.');
        }

        // A specific user (central sign-in / Google), otherwise the first administrator ("log in as").
        $row = ! empty($data['user_id'])
            ? db_connect()->table('users')->select('id AS user_id')->where('id', (int) $data['user_id'])->where('deleted_at', null)->get(1)->getRowArray()
            : db_connect()->table('auth_groups_users')->select('user_id')->whereIn('group', ['superadmin', 'admin'])->orderBy('user_id', 'ASC')->get(1)->getRowArray();
        if (! $row) {
            return redirect()->to(site_url('login'))->with('error', 'This workspace has no administrator yet.');
        }

        $auth = auth('session');
        if ($auth->loggedIn()) {
            $auth->logout();
        }
        $auth->loginById((int) $row['user_id']);
        log_message('notice', 'Platform admin {admin} signed in to workspace {slug} via SSO.', ['admin' => $data['admin'] ?? '?', 'slug' => Tenant::slug()]);

        return redirect()->to(site_url('/'));
    }
}
