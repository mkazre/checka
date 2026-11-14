<?php

namespace Platform\Controllers;

use Platform\Services\Billing;
use Platform\Services\Gateways\Ozow;
use Platform\Services\Gateways\PayFast;
use Platform\Services\Gateways\Paystack;

/** Server-to-server payment notifications. Each one is verified before anything is applied. */
class Webhooks extends BaseController
{
    public function paystack()
    {
        $raw = (string) $this->request->getBody();
        if (! Paystack::signatureValid($raw, (string) $this->request->getHeaderLine('X-Paystack-Signature'))) {
            return $this->response->setStatusCode(401);
        }
        $event = json_decode($raw, true);
        if (($event['event'] ?? '') === 'charge.success') {
            $data = Paystack::verify((string) ($event['data']['reference'] ?? '')); // never trust the body alone
            $inv  = $data ? Billing::invoiceByUid(Paystack::invoiceUid($data)) : null;
            if ($inv && ($data['status'] ?? '') === 'success') {
                Billing::saveCard((int) $inv['tenant_id'], $data);
                Billing::applyPayment($inv, 'paystack', (string) $data['reference'], ((int) $data['amount']) / 100, ['webhook' => true]);
            }
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }

    public function payfast()
    {
        $post = $this->request->getPost() ?? [];
        $inv  = Billing::invoiceByUid((string) ($post['m_payment_id'] ?? ''));
        if (! $inv || ! PayFast::validItn($post)) {
            return $this->response->setStatusCode(400);
        }
        if (($post['payment_status'] ?? '') === 'COMPLETE' && abs((float) $post['amount_gross'] - (float) $inv['total']) < 0.01) {
            Billing::applyPayment($inv, 'payfast', (string) $post['pf_payment_id'], (float) $post['amount_gross']);
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }

    public function ozow()
    {
        $p   = $this->request->getPost() ?? [];
        $inv = Billing::invoiceByUid((string) strtok((string) ($p['TransactionReference'] ?? ''), '-'));
        if (! $inv || ! Ozow::validResponse($p)) {
            return $this->response->setStatusCode(400);
        }
        if (strcasecmp((string) ($p['Status'] ?? ''), 'Complete') === 0 && Ozow::confirmed((string) $p['TransactionReference']) !== false) {
            Billing::applyPayment($inv, 'ozow', (string) ($p['TransactionId'] ?? $p['TransactionReference']), (float) $p['Amount']);
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }
}
