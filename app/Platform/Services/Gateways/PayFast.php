<?php

namespace Platform\Services\Gateways;

use Platform\Services\PlatformSettings;
use Throwable;

/** PayFast: signed redirect form + ITN (instant transaction notification) validation. */
class PayFast
{
    public static function cfg(): array
    {
        return PlatformSettings::get('gateways')['payfast'];
    }

    public static function enabled(): bool
    {
        $c = self::cfg();

        return ! empty($c['enabled']) && $c['merchantId'] !== '' && $c['merchantKey'] !== '';
    }

    public static function host(): string
    {
        return ! empty(self::cfg()['sandbox']) ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
    }

    /** PayFast's signature: fields in the given order, url-encoded, plus passphrase, md5. */
    public static function signature(array $fields, ?string $passphrase): string
    {
        $pairs = [];
        foreach ($fields as $k => $v) {
            if ($k === 'signature' || $v === '' || $v === null) {
                continue;
            }
            $pairs[] = $k . '=' . urlencode(trim((string) $v));
        }
        $str = implode('&', $pairs);
        if ($passphrase !== null && $passphrase !== '') {
            $str .= '&passphrase=' . urlencode(trim($passphrase));
        }

        return md5($str);
    }

    /** @return array{action:string,fields:array<string,string>} */
    public static function form(array $inv, array $t, string $returnUrl, string $cancelUrl, string $notifyUrl): array
    {
        $c     = self::cfg();
        $names = preg_split('/\s+/', trim((string) ($t['contact_name'] ?: $t['company_name'])), 2);
        $f     = [
            'merchant_id'      => $c['merchantId'],
            'merchant_key'     => $c['merchantKey'],
            'return_url'       => $returnUrl,
            'cancel_url'       => $cancelUrl,
            'notify_url'       => $notifyUrl,
            'name_first'       => mb_substr($names[0] ?? '', 0, 100),
            'name_last'        => mb_substr($names[1] ?? '', 0, 100),
            'email_address'    => (string) $t['email'],
            'm_payment_id'     => $inv['uid'],
            'amount'           => number_format((float) $inv['total'], 2, '.', ''),
            'item_name'        => mb_substr('Invoice ' . $inv['number'], 0, 100),
            'item_description' => mb_substr($inv['description'], 0, 255),
        ];
        $f['signature'] = self::signature($f, $c['passphrase']);

        return ['action' => 'https://' . self::host() . '/eng/process', 'fields' => $f];
    }

    /** Validate an ITN post: signature, then confirmation with PayFast's server. */
    public static function validItn(array $post): bool
    {
        $sig = (string) ($post['signature'] ?? '');
        if ($sig === '' || ! hash_equals(self::signature($post, self::cfg()['passphrase']), $sig)) {
            return false;
        }
        $pairs = [];
        foreach ($post as $k => $v) {
            if ($k !== 'signature') {
                $pairs[] = $k . '=' . urlencode(trim((string) $v));
            }
        }
        try {
            $res = \Config\Services::curlrequest([], null, null, false)->post('https://' . self::host() . '/eng/query/validate', [
                'http_errors' => false, 'timeout' => 20,
                'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body'        => implode('&', $pairs),
            ]);

            return trim($res->getBody()) === 'VALID';
        } catch (Throwable) {
            return false;
        }
    }
}
