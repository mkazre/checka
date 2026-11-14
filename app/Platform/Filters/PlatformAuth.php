<?php

namespace Platform\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Platform admin panel: requires a signed-in, active platform admin ('owner' argument = owner role only). */
class PlatformAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('platform');
        $admin = pf_admin();
        if ($admin === null || ! $admin['active']) {
            session()->remove(['pf_admin_id', 'pf_admin_name']);
            if ($request->getMethod() === 'GET') {
                session()->set('pf_return', current_url());
            }

            return redirect()->to(site_url('login'));
        }
        if (in_array('owner', (array) $arguments, true) && $admin['role'] !== 'owner') {
            return redirect()->to(site_url('/'))->with('error', 'Only the owner can do that.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
