<?php

namespace App\Controllers;

use App\Tenancy\Tenant;
use Platform\Models\PackageModel;
use Platform\Models\TenantModel;
use Platform\Services\Billing;
use Platform\Services\TenantRegistry;
use Platform\Models\EventModel;

/**
 * The workspace's own subscription page (SaaS mode): plan, status, invoices,
 * change plan (upgrade now / downgrade at renewal), cancel or undo cancel.
 * Admins only. Reads and writes the platform tables directly.
 */
class Subscription extends BaseController
{
    private function guard(): ?array
    {
        if (! Tenant::active() || ! auth()->loggedIn() || ! auth()->user()->inGroup('admin', 'superadmin')) {
            return null;
        }

        return model(TenantModel::class)->find((int) Tenant::get()['id']);
    }

    public function index()
    {
        $t = $this->guard();
        if (! $t) {
            return redirect()->to(site_url('/'))->with('error', 'Only workspace administrators can manage the subscription.');
        }
        $packages = array_values(array_filter(model(PackageModel::class)->ordered(true), static fn ($p) => $p['is_public'] || (int) $p['id'] === (int) $t['package_id']));

        return view('saas/subscription', [
            't'        => $t,
            'pkg'      => $t['package_id'] ? model(PackageModel::class)->find($t['package_id']) : null,
            'nextPkg'  => $t['next_package_id'] ? model(PackageModel::class)->find($t['next_package_id']) : null,
            'packages' => $packages,
            'invoices' => Billing::invoicesFor((int) $t['id']),
            'catalog'  => config('SaasModules')->modules,
            'limitDef' => config('SaasModules')->limits,
        ]);
    }

    public function change()
    {
        $t = $this->guard();
        if (! $t) {
            return redirect()->to(site_url('/'));
        }
        $pkg   = model(PackageModel::class)->where('is_active', 1)->find((int) $this->request->getPost('package_id'));
        $cycle = $this->request->getPost('billing_cycle') === 'annual' ? 'annual' : 'monthly';
        if (! $pkg || (! $pkg['is_public'] && (int) $pkg['id'] !== (int) $t['package_id'])) {
            return redirect()->back()->with('error', 'Please choose a package.');
        }
        if ((int) $pkg['id'] === (int) $t['package_id'] && $cycle === $t['billing_cycle']) {
            return redirect()->back()->with('message', 'You are already on that plan.');
        }
        $res = Billing::changePlan($t, $pkg, $cycle);
        if ($res['type'] === 'invoice') {
            return redirect()->to(Billing::payUrl($res['invoice']));
        }

        return redirect()->to(site_url('subscription'))->with('message', $res['message']);
    }

    public function cancel()
    {
        $t = $this->guard();
        if (! $t) {
            return redirect()->to(site_url('/'));
        }
        $undo = (bool) $this->request->getPost('undo');
        if ($t['status'] === 'trial' && ! $undo) {
            model(TenantModel::class)->update($t['id'], ['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')]);
            TenantRegistry::write(model(TenantModel::class)->find($t['id']));
            EventModel::log((int) $t['id'], 'cancelled', 'Trial cancelled by the customer.', 'customer');

            return redirect()->to(site_url('subscription'));
        }
        model(TenantModel::class)->update($t['id'], ['cancel_at_period_end' => $undo ? 0 : 1]);
        EventModel::log((int) $t['id'], $undo ? 'uncancel' : 'cancel_requested', $undo ? 'Cancellation withdrawn by the customer.' : 'Customer cancelled — workspace stays active until ' . $t['current_period_end'] . '.', 'customer');

        return redirect()->to(site_url('subscription'))->with('message', $undo ? 'Great — your subscription will continue.' : 'Your subscription is cancelled. Everything keeps working until ' . date('d M Y', strtotime((string) $t['current_period_end'])) . '.');
    }
}
