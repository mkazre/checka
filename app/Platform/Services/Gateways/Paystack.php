<?php

namespace Platform\Services\Gateways;

use Platform\Services\PlatformSettings;
use Throwable;

/** Paystack: hosted checkout (card / EFT), verification, saved-card charges, webhook signature. */
class Paystack
{
    private const API = 'https://api.paystack.co';

    public static string $lastError = '';

    public static function cfg(): array
    {
        return PlatformSettings::get('gateways')['paystack'];
    }

    public static function enabled(): bool
    {
        $c = self::cfg();

        return ! empty($c['enabled']) && $c['secretKey'] !== '';
    }

    private static function call(string $method, string $path, ?array $body = null): ?array
    {
        self::$lastError = '';
        try {
            $opts = ['http_errors' => false, 'timeout' => 30, 'headers' => ['Authorization' => 'Bearer ' . self::cfg()['secretKey'], 'Accept' => 'application/json']];
            if ($body !== null) {
                $opts['json'] = $body;
            }
            $res  = \Config\Services::curlrequest([], null, null, false)->request($method, self::API . $path, $opts);
            $json = json_decode($res->getBody(), true);
            if (! is_array($json) || empty($json['status'])) {
                self::$lastError = (string) ($json['message'] ?? ('HTTP ' . $res->getStatusCode()));

                return null;
            }

            return (array) ($json['data'] ?? []);
        } catch (Throwable $e) {
            self::$lastError = $e->getMessage();

            return null;
        }
    }

    /** Start a hosted checkout. Returns the URL to send the customer to, or null. */
    public static function initialize(array $inv, string $email, string $callbackUrl, array $channels = []): ?string
    {
        $body = [
            'email'        => $email,
            'amount'       => (int) round((float) $inv['total'] * 100),
            'currency'     => PlatformSettings::value('general', 'currency', 'ZAR'),
            'reference'    => $inv['uid'] . '-' . base_convert((string) time(), 10, 36) . bin2hex(random_bytes(2)),
            'callback_url' => $callbackUrl,
            'metadata'     => ['invoice_uid' => $inv['uid'], 'invoice_no' => $inv['number']],
        ];
        if ($channels) {
            $body['channels'] = $channels;
        }
        $data = self::call('POST', '/transaction/initialize', $body);

        return $data['authorization_url'] ?? null;
    }

    public static function verify(string $reference): ?array
    {
        return preg_match('/^[A-Za-z0-9._=-]{6,100}$/', $reference) ? self::call('GET', '/transaction/verify/' . rawurlencode($reference)) : null;
    }

    public static function chargeAuthorization(string $auth, string $email, float $amount, string $reference, string $invoiceUid): ?array
    {
        return self::call('POST', '/transaction/charge_authorization', [
            'authorization_code' => $auth,
            'email'              => $email,
            'amount'             => (int) round($amount * 100),
            'currency'           => PlatformSettings::value('general', 'currency', 'ZAR'),
            'reference'          => $reference,
            'metadata'           => ['invoice_uid' => $invoiceUid],
        ]);
    }

    public static function signatureValid(string $raw, string $signature): bool
    {
        $secret = self::cfg()['secretKey'];

        return $secret !== '' && $signature !== '' && hash_equals(hash_hmac('sha512', $raw, $secret), $signature);
    }

    /** Invoice uid a Paystack transaction belongs to. */
    public static function invoiceUid(array $data): string
    {
        $uid = (string) ($data['metadata']['invoice_uid'] ?? '');

        return $uid !== '' ? $uid : (string) strtok((string) ($data['reference'] ?? ''), '-');
    }
}
