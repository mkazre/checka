<?php

namespace Platform\Controllers;

use Platform\Models\EventModel;
use Platform\Models\TenantModel;
use Platform\Services\Billing;

/** All subscription invoices; manual invoices, mark paid (EFT), void, resend, charge saved card. */
class Invoices extends BaseController
{
    private function query()
    {
        $status = (string) $this->request->getGet('status');
        $q      = trim((string) $this->request->getGet('q'));
        $b      = Billing::db()->table('invoices i')->select('i.*, t.company_name, t.slug')->join('tenants t', 't.id = i.tenant_id', 'left')->orderBy('i.id', 'DESC');
        if ($status === 'overdue') {
            $b->where('i.status', 'unpaid')->where('i.due_date <', date('Y-m-d'));
        } elseif ($status !== '') {
            $b->where('i.status', $status);
        }
        if ($q !== '') {
            $b->groupStart()->like('i.number', $q)->orLike('t.company_name', $q)->orLike('t.slug', $q)->groupEnd();
        }

        return [$b, $status, $q];
    }

    public function index()
    {
        [$b, $status, $q] = $this->query();
        $db               = Billing::db();

        return $this->render('invoices/index', [
            'rows'        => $b->get(500)->getResultArray(),
            'status'      => $status,
            'q'           => $q,
            'outstanding' => (float) ($db->table('invoices')->selectSum('total', 's')->where('status', 'unpaid')->get()->getRowArray()['s'] ?? 0),
            'overdue'     => (float) ($db->table('invoices')->selectSum('total', 's')->where('status', 'unpaid')->where('due_date <', date('Y-m-d'))->get()->getRowArray()['s'] ?? 0),
            'collected'   => (float) ($db->table('payments')->selectSum('amount', 's')->where('created_at >=', date('Y-m-01 00:00:00'))->get()->getRowArray()['s'] ?? 0),
        ]);
    }

    public function export()
    {
        [$b] = $this->query();
        $rows = array_map(static fn ($r) => [$r['number'], $r['company_name'], $r['slug'], $r['kind'], $r['description'], $r['issue_date'], $r['due_date'],
            $r['subtotal'], $r['vat'], $r['total'], $r['status'], $r['paid_at']], $b->get()->getResultArray());

        return $this->csv('invoices-' . date('Y-m-d') . '.csv', ['Number', 'Company', 'Workspace', 'Type', 'Description', 'Issued', 'Due', 'Excl. VAT', 'VAT', 'Total', 'Status', 'Paid at'], $rows);
    }

    public function create(int $tenantId)
    {
        $t      = model(TenantModel::class)->find($tenantId);
        $amount = round((float) $this->request->getPost('amount'), 2);
        $desc   = trim((string) $this->request->getPost('description'));
        if (! $t || $amount <= 0 || $desc === '') {
            return redirect()->back()->with('error', 'Enter a description and an amount (excl. VAT).');
        }
        $inv = Billing::createInvoice($t, 'manual', $desc, $amount, ['due_date' => date('Y-m-d', strtotime('+' . max(0, (int) $this->request->getPost('due_days')) . ' days'))]);
        $msg = 'Invoice ' . $inv['number'] . ' created.';
        if ($this->request->getPost('send')) {
            $msg .= Billing::sendInvoice($t, $inv) ? ' Emailed to ' . $t['email'] . '.' : ' Email failed: ' . \Platform\Services\PlatformMailer::$lastError;
        }

        return redirect()->to(site_url('tenants/' . $tenantId))->with('success', $msg);
    }

    public function markPaid(int $id)
    {
        $inv = Billing::invoice($id);
        if (! $inv || $inv['status'] !== 'unpaid') {
            return redirect()->back()->with('error', 'That invoice is not open.');
        }
        $ref = trim((string) $this->request->getPost('reference')) ?: 'manual-' . $inv['number'] . '-' . date('YmdHis');
        $ok  = Billing::applyPayment($inv, (string) ($this->request->getPost('method') ?: 'eft'), $ref, (float) $inv['total'], ['note' => (string) $this->request->getPost('note')], (string) session('pf_admin_name'));

        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Invoice ' . $inv['number'] . ' marked as paid — subscription updated and receipt sent.' : 'Could not record that payment (reference already used?).');
    }

    public function void(int $id)
    {
        $inv = Billing::invoice($id);
        if ($inv && $inv['status'] === 'unpaid') {
            Billing::db()->table('invoices')->where('id', $id)->update(['status' => 'void', 'updated_at' => date('Y-m-d H:i:s')]);
            EventModel::log((int) $inv['tenant_id'], 'invoice_void', 'Invoice ' . $inv['number'] . ' cancelled.');
        }

        return redirect()->back()->with('success', 'Invoice cancelled.');
    }

    public function resend(int $id)
    {
        $inv = Billing::invoice($id);
        $t   = $inv ? model(TenantModel::class)->find($inv['tenant_id']) : null;
        if (! $inv || ! $t) {
            return redirect()->back();
        }
        $ok = Billing::sendInvoice($t, $inv);

        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Invoice emailed to ' . $t['email'] . '.' : 'Email failed: ' . \Platform\Services\PlatformMailer::$lastError);
    }

    public function charge(int $id)
    {
        $inv = Billing::invoice($id);
        $t   = $inv ? model(TenantModel::class)->find($inv['tenant_id']) : null;
        if (! $inv || ! $t || $inv['status'] !== 'unpaid' || ! $t['paystack_auth']) {
            return redirect()->back()->with('error', 'No saved card to charge.');
        }
        $ok = Billing::chargeSavedCard($t, $inv);

        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Card charged — invoice paid.' : 'The card charge failed. See the workspace history.');
    }
}
