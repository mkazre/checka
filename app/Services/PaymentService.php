<?php

namespace App\Services;

use Config\Services;

/**
 * Online payments via Paystack (feature: payments). Dormant until the secret
 * key is set on the Settings page.
 */
class PaymentService
{
    public function configured(): bool
    {
        return trim((string) setting('Pay.paystackSecret')) !== '';
    }

    public function currency(): string
    {
        return (string) (setting('Pay.currency') ?: 'ZAR');
    }

    /**
     * Initialise a transaction. Returns the hosted checkout URL to redirect to.
     *
     * @return array{ok:bool,url?:string,reference?:string,error?:string}
     */
    public function initialize(float $amount, string $email, string $reference, string $callbackUrl): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'Payments are not configured.'];
        }
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Nothing to pay.'];
        }

        try {
            $res = Services::curlrequest([
                'timeout'     => 25,
                'http_errors' => false,
                'headers'     => [
                    'Authorization' => 'Bearer ' . trim((string) setting('Pay.paystackSecret')),
                    'Content-Type'  => 'application/json',
                ],
            ])->post('https://api.paystack.co/transaction/initialize', [
                'json' => [
                    'email'        => $email ?: 'client@checka.local',
                    'amount'       => (int) round($amount * 100), // kobo/cents
                    'currency'     => $this->currency(),
                    'reference'    => $reference,
                    'callback_url' => $callbackUrl,
                ],
            ]);

            $data = json_decode((string) $res->getBody(), true);
            if (($data['status'] ?? false) && ! empty($data['data']['authorization_url'])) {
                return ['ok' => true, 'url' => $data['data']['authorization_url'], 'reference' => $reference];
            }

            return ['ok' => false, 'error' => $data['message'] ?? 'Could not start payment.'];
        } catch (\Throwable $e) {
            log_message('error', 'Paystack init failed: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Payment service unavailable.'];
        }
    }

    /** Verify a completed transaction. @return array{ok:bool,paid:bool,amount?:float,error?:string} */
    public function verify(string $reference): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'paid' => false, 'error' => 'Payments not configured.'];
        }

        try {
            $res = Services::curlrequest([
                'timeout'     => 25,
                'http_errors' => false,
                'headers'     => ['Authorization' => 'Bearer ' . trim((string) setting('Pay.paystackSecret'))],
            ])->get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));

            $data = json_decode((string) $res->getBody(), true);
            $paid = ($data['data']['status'] ?? '') === 'success';

            return [
                'ok'     => (bool) ($data['status'] ?? false),
                'paid'   => $paid,
                'amount' => isset($data['data']['amount']) ? ((float) $data['data']['amount']) / 100 : null,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Paystack verify failed: ' . $e->getMessage());

            return ['ok' => false, 'paid' => false, 'error' => 'Verification failed.'];
        }
    }
}
