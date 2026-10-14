<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\InvoiceModel;
use App\Services\BillingPdfService;
use App\Services\BillingService;
use App\Services\PaymentService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Public "Pay Now" pages linked from invoice/statement PDFs and emails —
 * no login. One page per invoice (pay/{invoiceUid}) and one for a client's
 * whole outstanding balance (pay/account/{clientUid}). Online payment goes
 * through Paystack; EFT details are shown alongside.
 *
 * Paystack references are bound to their target ("I<invoiceId>-…" /
 * "A<clientId>-…") and recorded once, so a paid reference can't be replayed
 * against another invoice or credited twice.
 */
class Pay extends BaseController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        if (! feature('billing')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    // ----------------------------- invoice -----------------------------

    public function invoice(string $uid)
    {
        $inv    = $this->invoiceOr404($uid);
        $bundle = (new BillingService())->invoiceBundle($inv);

        return view('pay/show', [
            'mode'      => 'invoice',
            'title'     => 'Invoice ' . $inv['invoice_no'],
            'client'    => $bundle['client'],
            'amountDue' => $bundle['balance'],
            'reference' => $inv['invoice_no'],
            'invoice'   => $inv,
            'items'     => $bundle['items'],
            'open'      => [],
            'cardUrl'   => site_url('pay/' . $uid . '/card'),
            'pdfUrl'    => site_url('pay/' . $uid . '/pdf'),
            'online'    => $this->onlineReady(),
        ]);
    }

    public function invoicePdf(string $uid)
    {
        $pdf = (new BillingPdfService())->invoice($this->invoiceOr404($uid));

        return $this->pdfResponse($pdf);
    }

    public function invoiceCard(string $uid)
    {
        $inv    = $this->invoiceOr404($uid);
        $amount = InvoiceModel::balance($inv);
        $client = model(ClientModel::class)->withDeleted()->find($inv['client_id']);

        return $this->startCheckout($amount, $client, 'I' . $inv['id'], site_url('pay/' . $uid . '/done'), 'pay/' . $uid);
    }

    public function invoiceDone(string $uid)
    {
        $inv = $this->invoiceOr404($uid);

        return $this->finishCheckout('I' . $inv['id'], (int) $inv['client_id'], (int) $inv['id'], 'pay/' . $uid);
    }

    // ----------------------------- account -----------------------------

    public function account(string $clientUid)
    {
        $client = $this->clientOr404($clientUid);
        $svc    = new BillingService();

        return view('pay/show', [
            'mode'      => 'account',
            'title'     => 'Account — ' . $client['name'],
            'client'    => $client,
            'amountDue' => $svc->balance((int) $client['id']),
            'reference' => strtoupper(substr((string) $client['uid'], 0, 8)),
            'invoice'   => null,
            'items'     => [],
            'open'      => $svc->openInvoices((int) $client['id']),
            'cardUrl'   => site_url('pay/account/' . $clientUid . '/card'),
            'pdfUrl'    => site_url('pay/account/' . $clientUid . '/pdf'),
            'online'    => $this->onlineReady(),
        ]);
    }

    public function accountPdf(string $clientUid)
    {
        $client = $this->clientOr404($clientUid);
        $pdf    = (new BillingPdfService())->statement($client, date('Y-m-01', strtotime('-2 months')), date('Y-m-d'), true);

        return $this->pdfResponse($pdf);
    }

    public function accountCard(string $clientUid)
    {
        $client = $this->clientOr404($clientUid);
        $amount = (new BillingService())->balance((int) $client['id']);

        return $this->startCheckout($amount, $client, 'A' . $client['id'], site_url('pay/account/' . $clientUid . '/done'), 'pay/account/' . $clientUid);
    }

    public function accountDone(string $clientUid)
    {
        $client = $this->clientOr404($clientUid);

        return $this->finishCheckout('A' . $client['id'], (int) $client['id'], null, 'pay/account/' . $clientUid);
    }

    // ------------------------------------------------------------------

    private function onlineReady(): bool
    {
        return (bool) setting('Billing.payOnlineEnabled') && (new PaymentService())->configured();
    }

    private function startCheckout(float $amount, ?array $client, string $prefix, string $callback, string $back)
    {
        if (! $this->onlineReady()) {
            return redirect()->to($back)->with('error', 'Online payment isn\'t available — please pay by EFT using the details below.');
        }
        if ($amount <= 0) {
            return redirect()->to($back)->with('message', 'Nothing is owing — thank you!');
        }

        $ref = $prefix . '-' . strtoupper(bin2hex(random_bytes(5)));
        $res = (new PaymentService())->initialize($amount, $client ? ClientModel::invoiceEmail($client) : '', $ref, $callback);
        if (! ($res['ok'] ?? false)) {
            return redirect()->to($back)->with('error', $res['error'] ?? 'Could not start the payment.');
        }

        return redirect()->to($res['url']);
    }

    private function finishCheckout(string $prefix, int $clientId, ?int $invoiceId, string $back)
    {
        $ref = (string) ($this->request->getGet('reference') ?: $this->request->getGet('trxref'));
        if ($ref === '' || ! str_starts_with($ref, $prefix . '-')) {
            return redirect()->to($back)->with('error', 'That payment reference doesn\'t belong to this page.');
        }

        // Serialise callbacks for the same reference (a double refresh must not credit twice).
        $db   = db_connect();
        $lock = 'checka_pay_' . $ref;
        $db->query('SELECT GET_LOCK(?, 15)', [$lock]);

        try {
            $svc = new BillingService();
            if ($svc->gatewayRefUsed($ref)) {
                return redirect()->to($back)->with('message', 'This payment has already been received — thank you!');
            }

            $v = (new PaymentService())->verify($ref);
            if (! ($v['paid'] ?? false) || (float) ($v['amount'] ?? 0) <= 0) {
                return redirect()->to($back)->with('error', 'The payment was not completed. You can try again, or pay by EFT.');
            }

            $svc->recordPayment($clientId, (float) $v['amount'], [
                'method'      => 'paystack',
                'reference'   => $ref,
                'gateway_ref' => $ref,
                'notes'       => 'Paid online',
            ], $invoiceId);
        } finally {
            $db->query('SELECT RELEASE_LOCK(?)', [$lock]);
        }

        return redirect()->to($back)->with('message', 'Payment of ' . money($v['amount']) . ' received — thank you!');
    }

    private function invoiceOr404(string $uid): array
    {
        $inv = model(InvoiceModel::class)->findByUid($uid);
        if ($inv === null || $inv['status'] === 'draft') {
            throw PageNotFoundException::forPageNotFound();
        }

        return $inv;
    }

    private function clientOr404(string $uid): array
    {
        $c = model(ClientModel::class)->findByUid($uid);
        if ($c === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $c;
    }

    private function pdfResponse(array $pdf)
    {
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['bytes']);
    }
}
