<?php

namespace App\Filters;

use App\Tenancy\Tenant;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Runs on every request inside a SaaS workspace (does nothing otherwise):
 *  - suspended / cancelled workspaces see a status page instead of the app
 *    (login, logout and the subscription page stay reachable so they can pay);
 *  - routes belonging to a module the package doesn't include show an
 *    "upgrade" page instead.
 */
class TenantGuard implements FilterInterface
{
    /** Always reachable, even when suspended (so the customer can sign in and pay). */
    private const OPEN = ['login', 'logout', 'register', 'auth', 'subscription', 'pay', 'verify', 'portal', 'assets', 'csrf', 'manifest', 'sw.js', 'platform-sso'];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! Tenant::active()) {
            return null;
        }

        $path  = trim(method_exists($request, 'getPath') ? $request->getPath() : $request->getUri()->getPath(), '/');
        $first = strtolower(explode('/', $path)[0] ?? '');
        $t     = Tenant::get();

        if (Tenant::status() === 'pending_payment') {
            $inv = db_connect('platform')->table('invoices')->where('tenant_id', (int) ($t['id'] ?? 0))->where('status', 'unpaid')->orderBy('id')->get(1)->getRowArray();

            return service('response')->setStatusCode(402)->setBody(view('saas/awaiting', ['tenant' => $t, 'payUrl' => $inv ? \Platform\Services\Billing::payUrl($inv) : null]));
        }

        if (Tenant::status() === 'provisioning') {
            return service('response')->setStatusCode(503)->setHeader('Retry-After', '15')->setBody(view('saas/provisioning', ['tenant' => $t]));
        }

        if (in_array(Tenant::status(), ['suspended', 'cancelled', 'closed'], true) && ! in_array($first, self::OPEN, true)) {
            return service('response')->setStatusCode(402)->setBody(view('saas/status', ['tenant' => $t, 'status' => Tenant::status()]));
        }

        $module = config('SaasModules')->moduleForPath($path);
        if ($module !== null && ! module_enabled($module)) {
            return service('response')->setStatusCode(403)->setBody(view('saas/upgrade', [
                'tenant' => $t,
                'module' => config('SaasModules')->modules[$module],
            ]));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
