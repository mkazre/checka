<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\InspectionModel;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Services\BillingMailer;
use App\Services\BillingPdfService;
use App\Services\BillingService;
use App\Services\StatementRunner;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Invoices, payments and billing settings (feature: billing).
 */
class Invoices extends BaseController
{
    private const SETTINGS_TEXT = [
        'companyName', 'companyAddress', 'companyPhone', 'companyEmail', 'companyRegNo', 'companyVatNo',
        'invoicePrefix', 'defaultChargeDescription',
        'bankName', 'bankAccountName', 'bankAccountNumber', 'bankBranchCode', 'bankAccountType', 'bankSwift',
        'paymentInstructions', 'invoiceTerms',
    ];
    private const SETTINGS_BOOL = ['vatRegistered', 'payOnlineEnabled', 'payEftEnabled', 'monthlyStatementsEnabled'];

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        if (! feature('billing')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    public function index()
    {
        $status = (string) $this->request->getGet('status');
        $q      = trim((string) $this->request->getGet('q'));
        $model  = model(InvoiceModel::class);

        $open        = $model->whereIn('status', InvoiceModel::OPEN)->findAll();
        $outstanding = 0.0;
        $overdue     = 0.0;
        foreach ($open as $i) {
            $outstanding += InvoiceModel::balance($i);
            if (InvoiceModel::isOverdue($i)) {
                $overdue += InvoiceModel::balance($i);
            }
        }
        $paidThisMonth = (float) (model(PaymentModel::class)->selectSum('amount')->where('paid_at >=', date('Y-m-01'))->first()['amount'] ?? 0);
        $drafts        = $model->where('status', 'draft')->countAllResults();

        return view('invoices/index', [
            'title'    => 'Invoices',
            'invoices' => $model->listing($status, $q),
            'status'   => $status,
            'q'        => $q,
            'kpi'      => compact('outstanding', 'overdue', 'paidThisMonth', 'drafts'),
        ]);
    }

    public function new()
    {
        $client = null;
        if ($cu = (string) $this->request->getGet('client')) {
            $client = model(ClientModel::class)->findByUid($cu);
        }

        return view('invoices/form', [
            'title'   => 'New Invoice',
            'invoice' => null,
            'items'   => [['description' => (string) setting('Billing.defaultChargeDescription'), 'qty' => 1, 'unit_price' => (float) setting('Billing.defaultInspectionFee')]],
            'clients' => model(ClientModel::class)->search('', 2000),
            'clientId' => $client['id'] ?? null,
        ]);
    }

    public function create()
    {
        $clientId = (int) $this->request->getPost('client_id');
        if (! model(ClientModel::class)->find($clientId)) {
            return redirect()->back()->withInput()->with('error', 'Choose a client for this invoice.');
        }
        $items = $this->postedItems();
        if ($items === []) {
            return redirect()->back()->withInput()->with('error', 'Add at least one line item.');
        }

        $svc = new BillingService();
        $id  = $svc->createInvoice($clientId, $items, [
            'issue_date' => $this->date('issue_date') ?? date('Y-m-d'),
            'due_date'   => $this->date('due_date'),
            'notes'      => trim((string) $this->request->getPost('notes')) ?: null,
        ]);
        $inv = model(InvoiceModel::class)->find($id);

        if ($this->request->getPost('issue')) {
            $svc->markIssued($id);
        }

        return redirect()->to('invoices/' . $inv['uid'])->with('message', 'Invoice ' . $inv['invoice_no'] . ' created.');
    }

    public function show(string $uid)
    {
        $inv    = $this->findOr404($uid);
        $bundle = (new BillingService())->invoiceBundle($inv);

        return view('invoices/show', [
            'title' => 'Invoice ' . $inv['invoice_no'],
            'b'     => $bundle,
        ]);
    }

    public function edit(string $uid)
    {
        $inv = $this->findOr404($uid);
        if ($inv['status'] === 'void') {
            return redirect()->to('invoices/' . $uid)->with('error', 'A void invoice can\'t be edited.');
        }

        return view('invoices/form', [
            'title'    => 'Edit ' . $inv['invoice_no'],
            'invoice'  => $inv,
            'items'    => model(\App\Models\InvoiceItemModel::class)->forInvoice((int) $inv['id']),
            'clients'  => model(ClientModel::class)->search('', 2000),
            'clientId' => $inv['client_id'],
        ]);
    }

    public function update(string $uid)
    {
        $inv = $this->findOr404($uid);
        if ($inv['status'] === 'void') {
            return redirect()->to('invoices/' . $uid)->with('error', 'A void invoice can\'t be edited.');
        }
        $items = $this->postedItems();
        if ($items === []) {
            return redirect()->back()->withInput()->with('error', 'Add at least one line item.');
        }

        $data = [
            'issue_date' => $this->date('issue_date') ?? $inv['issue_date'],
            'due_date'   => $this->date('due_date') ?? $inv['due_date'],
            'notes'      => trim((string) $this->request->getPost('notes')) ?: null,
        ];
        // The client can only change while nothing has been paid against it.
        $clientId = (int) $this->request->getPost('client_id');
        if ($clientId && $clientId !== (int) $inv['client_id'] && (float) $inv['amount_paid'] == 0.0 && model(ClientModel::class)->find($clientId)) {
            $data['client_id'] = $clientId;
        }

        $svc = new BillingService();
        db_connect()->transStart();
        model(InvoiceModel::class)->update($inv['id'], $data);
        $svc->replaceItems((int) $inv['id'], $items);
        $svc->recalc((int) $inv['id']);
        db_connect()->transComplete();

        if ($this->request->getPost('issue')) {
            $svc->markIssued((int) $inv['id']);
        }

        return redirect()->to('invoices/' . $uid)->with('message', 'Invoice updated.');
    }

    public function pdf(string $uid)
    {
        $pdf = (new BillingPdfService())->invoice($this->findOr404($uid));

        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['bytes']);
    }

    public function send(string $uid)
    {
        $inv   = $this->findOr404($uid);
        $to    = trim((string) $this->request->getPost('email'));
        $cc    = trim((string) $this->request->getPost('cc')) ?: null;
        $withR = (bool) $this->request->getPost('with_report');

        $res = (new BillingMailer())->sendInvoice($inv, $to, $withR, $cc);

        return redirect()->to('invoices/' . $uid)->with($res['ok'] ? 'message' : 'error', $res['ok'] ? 'Invoice emailed to ' . $to . '.' : $res['error']);
    }

    public function issue(string $uid)
    {
        $inv = $this->findOr404($uid);
        (new BillingService())->markIssued((int) $inv['id']);

        return redirect()->to('invoices/' . $uid)->with('message', 'Invoice marked as issued — it now counts on the client\'s statement.');
    }

    public function void(string $uid)
    {
        $inv = $this->findOr404($uid);
        if ((float) $inv['amount_paid'] > 0) {
            return redirect()->to('invoices/' . $uid)->with('error', 'This invoice has payments against it. Remove the payments first, or issue a credit instead.');
        }
        model(InvoiceModel::class)->update($inv['id'], ['status' => 'void']);

        return redirect()->to('invoices/' . $uid)->with('message', 'Invoice voided.');
    }

    public function delete(string $uid)
    {
        $inv = $this->findOr404($uid);
        if ($inv['status'] !== 'draft') {
            return redirect()->to('invoices/' . $uid)->with('error', 'Only draft invoices can be deleted. Void an issued invoice instead.');
        }
        model(InvoiceModel::class)->delete($inv['id']);

        return redirect()->to('invoices')->with('message', 'Draft invoice deleted.');
    }

    /** Create (or open) the invoice for an inspection — from the report page. */
    public function fromInspection(string $reportUid)
    {
        $ins = model(InspectionModel::class)->findByUid($reportUid);
        if ($ins === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        // Let the charge be adjusted on the way in.
        $amount = $this->request->getPost('charge_amount');
        if ($amount !== null && $amount !== '') {
            $upd = ['charge_amount' => round((float) $amount, 2)];
            if (($d = trim((string) $this->request->getPost('charge_description'))) !== '') {
                $upd['charge_description'] = $d;
            }
            model(InspectionModel::class)->update($ins['id'], $upd);
            $ins = array_merge($ins, $upd);
        }

        try {
            $id = (new BillingService())->fromInspection($ins);
        } catch (\RuntimeException $e) {
            return redirect()->to('reports/' . $reportUid)->with('error', $e->getMessage());
        }
        $inv = model(InvoiceModel::class)->find($id);

        return redirect()->to('invoices/' . $inv['uid'])->with('message', 'Invoice ' . $inv['invoice_no'] . ' is ready — review it, then issue or email it.');
    }

    // ------------------------------------------------------------------
    // Payments
    // ------------------------------------------------------------------

    public function payments()
    {
        $client = null;
        if ($cu = (string) $this->request->getGet('client')) {
            $client = model(ClientModel::class)->findByUid($cu);
        }

        return view('invoices/payments', [
            'title'    => 'Payments',
            'payments' => model(PaymentModel::class)->listing(null, 300),
            'clients'  => model(ClientModel::class)->search('', 2000),
            'open'     => model(InvoiceModel::class)->listing('', '', null, 2000),
            'clientId' => $client['id'] ?? null,
            'invoiceId' => (int) $this->request->getGet('invoice') ?: null,
        ]);
    }

    public function recordPayment()
    {
        $clientId  = (int) $this->request->getPost('client_id');
        $invoiceId = (int) $this->request->getPost('invoice_id') ?: null;
        $amount    = (float) $this->request->getPost('amount');
        $back      = (string) $this->request->getPost('back');

        if (! model(ClientModel::class)->find($clientId)) {
            return redirect()->back()->withInput()->with('error', 'Choose the client who paid.');
        }

        try {
            (new BillingService())->recordPayment($clientId, $amount, [
                'method'    => (string) $this->request->getPost('method'),
                'reference' => trim((string) $this->request->getPost('reference')) ?: null,
                'paid_at'   => $this->date('paid_at') ?? date('Y-m-d'),
                'notes'     => trim((string) $this->request->getPost('notes')) ?: null,
            ], $invoiceId);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $msg = 'Payment of ' . money($amount) . ' recorded.';
        if (preg_match('#^(invoices|clients)/[a-f0-9]+$#', $back)) {
            return redirect()->to($back)->with('message', $msg);
        }

        return redirect()->to('invoices/payments')->with('message', $msg);
    }

    public function deletePayment(int $id)
    {
        (new BillingService())->deletePayment($id);

        return redirect()->back()->with('message', 'Payment removed.');
    }

    // ------------------------------------------------------------------
    // Settings + statement run (admin)
    // ------------------------------------------------------------------

    public function settings()
    {
        $s = [];
        foreach (array_merge(self::SETTINGS_TEXT, self::SETTINGS_BOOL, ['vatRate', 'nextInvoiceNumber', 'paymentTermsDays', 'defaultInspectionFee']) as $k) {
            $s[$k] = setting('Billing.' . $k);
        }
        [, , $period] = BillingMailer::lastMonth();

        return view('invoices/settings', [
            'title'            => 'Billing Settings',
            's'                => $s,
            'paystackReady'    => trim((string) setting('Pay.paystackSecret')) !== '',
            'emailReady'       => trim((string) setting('Notify.fromEmail')) !== '',
            'lastPeriod'       => $period,
        ]);
    }

    public function saveSettings()
    {
        foreach (self::SETTINGS_TEXT as $k) {
            setting()->set('Billing.' . $k, trim((string) $this->request->getPost($k)));
        }
        foreach (self::SETTINGS_BOOL as $k) {
            setting()->set('Billing.' . $k, (bool) $this->request->getPost($k));
        }
        setting()->set('Billing.vatRate', max(0, min(100, (float) $this->request->getPost('vatRate'))));
        setting()->set('Billing.paymentTermsDays', max(0, (int) $this->request->getPost('paymentTermsDays')));
        setting()->set('Billing.defaultInspectionFee', max(0, round((float) $this->request->getPost('defaultInspectionFee'), 2)));
        if (($n = (int) $this->request->getPost('nextInvoiceNumber')) > 0) {
            setting()->set('Billing.nextInvoiceNumber', $n);
        }
        if (trim((string) setting('Billing.invoicePrefix')) === '') {
            setting()->set('Billing.invoicePrefix', 'INV-');
        }

        return redirect()->to('invoices/settings')->with('message', 'Billing settings saved.');
    }

    public function runStatements()
    {
        $dry = (bool) $this->request->getPost('dry_run');
        $res = (new StatementRunner())->run((bool) $this->request->getPost('force'), null, $dry);

        $msg = ($dry ? 'Dry run for ' : 'Statements for ') . $res['period'] . ': '
            . count($res['sent']) . ($dry ? ' would be sent' : ' sent') . ', '
            . count($res['skipped']) . ' skipped, ' . count($res['failed']) . ' failed.';

        return redirect()->to('invoices/settings')
            ->with('message', $msg)
            ->with('runDetail', $res);
    }

    // ------------------------------------------------------------------

    private function findOr404(string $uid): array
    {
        $inv = model(InvoiceModel::class)->findByUid($uid);
        if ($inv === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $inv;
    }

    /** @return list<array{description:string,qty:float,unit_price:float}> */
    private function postedItems(): array
    {
        $d = (array) $this->request->getPost('item_description');
        $q = (array) $this->request->getPost('item_qty');
        $u = (array) $this->request->getPost('item_price');

        $items = [];
        foreach ($d as $i => $desc) {
            $desc = trim((string) $desc);
            if ($desc === '') {
                continue;
            }
            $items[] = ['description' => $desc, 'qty' => (float) ($q[$i] ?? 1), 'unit_price' => (float) ($u[$i] ?? 0)];
        }

        return $items;
    }

    private function date(string $field): ?string
    {
        $v = (string) $this->request->getPost($field);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }
}
