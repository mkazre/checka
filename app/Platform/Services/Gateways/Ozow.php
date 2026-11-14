<?php

namespace Platform\Services\Gateways;

use Platform\Services\PlatformSettings;
use Throwable;

/** Ozow instant EFT: signed redirect form + notification hash check (+ API double-check when an API key is set). */
class Ozow
{
    public static function cfg(): array
    {
        return PlatformSettings::get('gateways')['ozow'];
    }

    public static function enabled(): bool
    {
        $c = self::cfg();

        return ! empty($c['enabled']) && $c['siteCode'] !== '' && $c['privateKey'] !== '';
    }

    private static function hash(array $values): string
    {
        return hash('sha512', strtolower(implode('', $values) . self::cfg()['privateKey']));
    }

    /** @return array{action:string,fields:array<string,string>} */
    public static function form(array $inv, string $successUrl, string $cancelUrl, string $errorUrl, string $notifyUrl): array
    {
        $c = self::cfg();
        $f = [
            'SiteCode'             => $c['siteCode'],
            'CountryCode'          => 'ZA',
            'CurrencyCode'         => 'ZAR',
            'Amount'               => number_format((float) $inv['total'], 2, '.', ''),
            'TransactionReference' => $inv['uid'] . '-' . base_convert((string) time(), 10, 36),
            'BankReference'        => mb_substr((string) $inv['number'], 0, 20),
            'CancelUrl'            => $cancelUrl,
            'ErrorUrl'             => $errorUrl,
            'SuccessUrl'           => $successUrl,
            'NotifyUrl'            => $notifyUrl,
            'IsTest'               => ! empty($c['sandbox']) ? 'true' : 'false',
        ];
        $f['HashCheck'] = self::hash(array_values($f));

        return ['action' => 'https://pay.ozow.com', 'fields' => $f];
    }

    /** Check the Hash on a notification/redirect from Ozow. */
    public static function validResponse(array $p): bool
    {
        $keys = ['SiteCode', 'TransactionId', 'TransactionReference', 'Amount', 'Status', 'Optional1', 'Optional2', 'Optional3', 'Optional4', 'Optional5', 'CurrencyCode', 'IsTest', 'StatusMessage'];
        $vals = [];
        foreach ($keys as $k) {
            $vals[] = (string) ($p[$k] ?? '');
        }
        $hash = strtolower((string) ($p['Hash'] ?? ''));

        return $hash !== '' && ($p['SiteCode'] ?? '') === self::cfg()['siteCode'] && hash_equals(self::hash($vals), $hash);
    }

    /** Ask Ozow directly whether the transaction completed (only when an API key is configured). */
    public static function confirmed(string $reference): ?bool
    {
        $c = self::cfg();
        if ($c['apiKey'] === '') {
            return null;
        }
        try {
            $res  = \Config\Services::curlrequest([], null, null, false)->get('https://api.ozow.com/GetTransactionByReference', [
                'http_errors' => false, 'timeout' => 20,
                'headers'     => ['ApiKey' => $c['apiKey'], 'Accept' => 'application/json'],
                'query'       => ['siteCode' => $c['siteCode'], 'transactionReference' => $reference, 'isTest' => ! empty($c['sandbox']) ? 'true' : 'false'],
            ]);
            foreach ((array) json_decode($res->getBody(), true) as $tx) {
                if (strcasecmp((string) ($tx['status'] ?? ''), 'Complete') === 0) {
                    return true;
                }
            }

            return false;
        } catch (Throwable) {
            return null;
        }
    }
}
