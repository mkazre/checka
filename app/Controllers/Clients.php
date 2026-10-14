<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\InspectionModel;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Services\BillingMailer;
use App\Services\BillingPdfService;
use App\Services\BillingService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Client register. Every inspection is linked to a client, and invoices,
 * payments and statements hang off the client.
 */
class Clients extends BaseController
{
    private const FIELDS = [
        'type', 'name', 'contact_person', 'phone', 'email', 'billing_email', 'address',
        'vat_number', 'registration_number', 'payment_terms_days', 'notes',
    ];

    public function index()
    {
        $q       = trim((string) $this->request->getGet('q'));
        $clients = model(ClientModel::class)->search($q, 500);

        $balances = [];
        if (feature('billing')) {
            $svc = new BillingService();
            foreach ($clients as $c) {
                $balances[$c['id']] = $svc->balance((int) $c['id']);
            }
        }

        return view('clients/index', [
            'title'    => 'Clients',
            'clients'  => $clients,
            'q'        => $q,
            'balances' => $balances,
        ]);
    }

    public function new()
    {
        return view('clients/form', [
            'title'  => 'New Client',
            'client' => ['type' => 'business', 'monthly_statements' => 1],
            'return' => $this->safeReturn((string) $this->request->getGet('return')),
        ]);
    }

    public function create()
    {
        $data               = $this->collect();
        $data['created_by'] = auth()->id();

        $model = model(ClientModel::class);
        $id    = $model->insert($data, true);
        if ($id === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        $client = $model->find($id);
        $return = $this->safeReturn((string) $this->request->getPost('return'));
        if ($return !== '') {
            $sep = str_contains($return, '?') ? '&' : '?';

            return redirect()->to($return . $sep . 'client=' . $client['uid'])->with('message', 'Client "' . $client['name'] . '" added.');
        }

        return redirect()->to('clients/' . $client['uid'])->with('message', 'Client added.');
    }

    public function show(string $uid)
    {
        $client = $this->findOr404($uid);
        $id     = (int) $client['id'];

        $inspections = model(InspectionModel::class)
            ->select('inspections.*, vehicles.make, vehicles.model, vehicles.year, vehicles.plate')
            ->join('vehicles', 'vehicles.id = inspections.vehicle_id', 'left')
            ->where('inspections.client_id', $id)
            ->orderBy('inspections.inspection_date', 'DESC')
            ->findAll(100);

        $billing = null;
        if (feature('billing')) {
            $svc  = new BillingService();
            $from = (string) ($this->request->getGet('from') ?: date('Y-m-01', strtotime('-2 months')));
            $to   = (string) ($this->request->getGet('to') ?: date('Y-m-d'));
            $billing = [
                'balance'   => $svc->balance($id),
                'ageing'    => $svc->ageing($id),
                'invoices'  => model(InvoiceModel::class)->listing('', '', $id, 200),
                'payments'  => model(PaymentModel::class)->listing($id, 200),
                'statement' => $svc->statement($id, $from, $to),
                'from'      => $from,
                'to'        => $to,
            ];
        }

        return view('clients/show', [
            'title'       => $client['name'],
            'client'      => $client,
            'inspections' => $inspections,
            'billing'     => $billing,
        ]);
    }

    public function edit(string $uid)
    {
        return view('clients/form', [
            'title'  => 'Edit Client',
            'client' => $this->findOr404($uid),
            'return' => '',
        ]);
    }

    public function update(string $uid)
    {
        $client = $this->findOr404($uid);
        $model  = model(ClientModel::class);
        if (! $model->update($client['id'], $this->collect())) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('clients/' . $uid)->with('message', 'Client updated.');
    }

    public function delete(string $uid)
    {
        $client = $this->findOr404($uid);
        $hasInvoices = model(InvoiceModel::class)->withDeleted()->where('client_id', $client['id'])->countAllResults() > 0;
        if ($hasInvoices) {
            return redirect()->back()->with('error', 'This client has invoices on record, so it can\'t be deleted — that would break their statement history. Edit it instead.');
        }
        model(ClientModel::class)->delete($client['id']);

        return redirect()->to('clients')->with('message', 'Client deleted.');
    }

    /** Statement PDF (with open invoices attached) for ?from=&to=. */
    public function statementPdf(string $uid)
    {
        $this->billingOr404();
        $client = $this->findOr404($uid);
        [$from, $to] = $this->period();
        $pdf = (new BillingPdfService())->statement($client, $from, $to, (bool) $this->request->getGet('invoices'));

        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['bytes']);
    }

    public function statementSend(string $uid)
    {
        $this->billingOr404();
        $client = $this->findOr404($uid);
        [$from, $to] = $this->period('post');
        $email = trim((string) $this->request->getPost('email')) ?: ClientModel::invoiceEmail($client);

        $res = (new BillingMailer())->sendStatement($client, $from, $to, $email);

        return redirect()->to('clients/' . $uid . '?from=' . $from . '&to=' . $to)
            ->with($res['ok'] ? 'message' : 'error', $res['ok'] ? 'Statement emailed to ' . $email . '.' : $res['error']);
    }

    // ------------------------------------------------------------------

    private function collect(): array
    {
        $data = [];
        foreach (self::FIELDS as $f) {
            $v        = trim((string) $this->request->getPost($f));
            $data[$f] = $v === '' ? null : $v;
        }
        $data['type']               = $data['type'] === 'individual' ? 'individual' : 'business';
        $data['name']               = (string) $data['name'];
        $data['payment_terms_days'] = $data['payment_terms_days'] !== null ? max(0, (int) $data['payment_terms_days']) : null;
        $data['monthly_statements'] = $this->request->getPost('monthly_statements') ? 1 : 0;

        return $data;
    }

    private function findOr404(string $uid): array
    {
        $c = model(ClientModel::class)->findByUid($uid);
        if ($c === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $c;
    }

    private function billingOr404(): void
    {
        if (! feature('billing')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /** @return array{0:string,1:string} validated Y-m-d range */
    private function period(string $src = 'get'): array
    {
        $get  = fn ($k) => (string) ($src === 'post' ? $this->request->getPost($k) : $this->request->getGet($k));
        $from = $get('from');
        $to   = $get('to');
        $ok   = static fn ($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);

        return [
            $ok($from) ? $from : date('Y-m-01', strtotime('-2 months')),
            $ok($to) ? $to : date('Y-m-d'),
        ];
    }

    /** Only same-site relative paths, e.g. "inspections/new". */
    private function safeReturn(string $r): string
    {
        $r = ltrim(trim($r), '/');

        return preg_match('#^[a-z0-9][a-z0-9/_-]*$#i', $r) ? $r : '';
    }
}
