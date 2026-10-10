<?php

namespace App\Services\Vin;

use Config\Services;
use Throwable;

/**
 * Shared HTTP plumbing for the paid provider adapters: a timed, error-safe
 * request that always returns JSON (or null) and never throws — a bad key,
 * a timeout, or a vendor outage should degrade that one provider, not break
 * the whole combined VIN Check report.
 */
abstract class AbstractHttpProvider
{
    protected function configured(string $apiKey): bool
    {
        return trim($apiKey) !== '';
    }

    /**
     * @return array{status:int, json:mixed, body:string}|null null on a
     *         network-level failure (couldn't even reach the host)
     */
    protected function request(string $method, string $url, array $options = []): ?array
    {
        try {
            $client = Services::curlrequest([
                'timeout'         => 25,
                'http_errors'     => false,
                // CodeIgniter's CURLRequest does NOT follow redirects unless told to —
                // without this, a provider's 301/302 (e.g. canonicalizing http->https
                // or a trailing slash) comes straight back as an "error" instead of
                // being followed to the real response.
                'allow_redirects' => ['max' => 5, 'strict' => false, 'referer' => false, 'protocols' => ['http', 'https']],
                'headers'         => array_merge(['User-Agent' => 'CheckaInspections/1.0'], $options['headers'] ?? []),
            ]);

            $res = strtoupper($method) === 'POST'
                ? $client->post($url, array_filter(['query' => $options['query'] ?? null, 'json' => $options['json'] ?? null]))
                : $client->get($url, array_filter(['query' => $options['query'] ?? null]));

            $body = (string) $res->getBody();

            return ['status' => $res->getStatusCode(), 'json' => json_decode($body, true), 'body' => $body];
        } catch (Throwable $e) {
            log_message('error', static::class . ' request failed: ' . $e->getMessage());

            return null;
        }
    }
}
