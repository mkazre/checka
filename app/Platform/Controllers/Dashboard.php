<?php

namespace Platform\Controllers;

use Platform\Models\PackageModel;
use Platform\Models\TenantModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $tenants  = model(TenantModel::class)->listing();
        $packages = array_column(model(PackageModel::class)->findAll(), null, 'id');

        $byStatus = array_fill_keys(array_keys(TenantModel::STATUSES), 0);
        $mrr      = 0.0;
        $perPkg   = [];
        $soon     = [];
        $failed   = [];
        $new30    = 0;
        $cut      = strtotime('-30 days');
        foreach ($tenants as $t) {
            $byStatus[$t['status']] = ($byStatus[$t['status']] ?? 0) + 1;
            $p = $packages[$t['package_id']] ?? null;
            if ($p && in_array($t['status'], ['active', 'past_due'], true)) {
                $mrr += $t['billing_cycle'] === 'annual' ? (float) $p['price_annual'] / 12 : (float) $p['price_monthly'];
            }
            if (! in_array($t['status'], ['cancelled'], true)) {
                $perPkg[$p['name'] ?? 'No package'] = ($perPkg[$p['name'] ?? 'No package'] ?? 0) + 1;
            }
            if ($t['status'] === 'trial' && $t['trial_ends_at'] && strtotime($t['trial_ends_at']) <= strtotime('+7 days')) {
                $soon[] = $t;
            }
            if ($t['provision_error']) {
                $failed[] = $t;
            }
            if (strtotime((string) $t['created_at']) >= $cut) {
                $new30++;
            }
        }
        arsort($perPkg);
        usort($soon, static fn ($a, $b) => strcmp($a['trial_ends_at'], $b['trial_ends_at']));

        $events = db_connect('platform')->table('events e')
            ->select('e.*, t.company_name, t.slug')
            ->join('tenants t', 't.id = e.tenant_id', 'left')
            ->orderBy('e.id', 'DESC')->limit(15)->get()->getResultArray();

        return $this->render('dashboard', [
            'total'    => count($tenants),
            'byStatus' => $byStatus,
            'mrr'      => $mrr,
            'perPkg'   => $perPkg,
            'soon'     => $soon,
            'failed'   => $failed,
            'new30'    => $new30,
            'events'   => $events,
            'pending'  => array_filter($tenants, static fn ($t) => $t['status'] === 'provisioning' && ! $t['provision_error']),
            'hasPkgs'  => $packages !== [],
        ]);
    }
}
