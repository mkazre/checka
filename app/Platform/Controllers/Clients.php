<?php

namespace Platform\Controllers;

use Platform\Models\TenantModel;
use Platform\Services\TenantData;

/** Every workspace's clients in one list, with CSV export. */
class Clients extends BaseController
{
    private const COLS = ['Workspace', 'Company (customer)', 'Client', 'Type', 'Contact person', 'Email', 'Billing email', 'Phone', 'Address', 'VAT no.', 'Reg. no.', 'Added'];

    private function collect(string $q, ?int $tenantId, int $perTenant): array
    {
        $m = model(TenantModel::class)->where('provisioned_at IS NOT NULL');
        if ($tenantId) {
            $m->where('id', $tenantId);
        }
        $rows = [];
        foreach ($m->orderBy('company_name')->findAll() as $t) {
            foreach (TenantData::clients($t, $q, $perTenant) as $c) {
                $rows[] = ['_tid' => $t['id'], 'workspace' => $t['slug'], 'tenant' => $t['company_name']] + $c;
            }
        }

        return $rows;
    }

    public function index()
    {
        $q   = trim((string) $this->request->getGet('q'));
        $tid = (int) $this->request->getGet('tenant') ?: null;

        return $this->render('clients/index', [
            'rows'    => $this->collect($q, $tid, 500),
            'tenants' => model(TenantModel::class)->where('provisioned_at IS NOT NULL')->orderBy('company_name')->findAll(),
            'q'       => $q,
            'tid'     => $tid,
        ]);
    }

    public function export()
    {
        $rows = $this->collect(trim((string) $this->request->getGet('q')), (int) $this->request->getGet('tenant') ?: null, 100000);

        return $this->csv('all-clients-' . date('Y-m-d') . '.csv', self::COLS, array_map(static function ($r) {
            unset($r['_tid']);

            return $r;
        }, $rows));
    }

    public function exportTenant(int $id)
    {
        $t = model(TenantModel::class)->find($id);
        if (! $t) {
            return redirect()->to(site_url('tenants'));
        }
        $rows = array_map(static function ($r) {
            unset($r['_tid']);

            return $r;
        }, $this->collect('', $id, 100000));

        return $this->csv($t['slug'] . '-clients-' . date('Y-m-d') . '.csv', self::COLS, $rows);
    }
}
