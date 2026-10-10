<?php

namespace App\Services\Vin;

/**
 * Paid slot 2 — CarsXE (carsxe.com). Vehicle history + specs API: title
 * checks, theft records, event history. Self-serve API key from their site.
 *
 * As with VinAuditProvider, the exact field names below are a best-effort
 * mapping of their documented history endpoint (key + vin query params).
 * Verify against CarsXE's current docs when you wire up a real key; the raw
 * response is always kept on the report even if a field name has moved.
 */
class CarsXeProvider extends AbstractHttpProvider implements VinProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $label = 'CarsXE',
    ) {
    }

    public function label(): string
    {
        return $this->label;
    }

    public function capabilities(): array
    {
        return ['accidentHistory', 'stolenStatus', 'specs'];
    }

    public function fetch(string $vin): array
    {
        if (! $this->configured($this->apiKey)) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'No API key configured.'];
        }

        $res = $this->request('GET', $this->baseUrl, [
            'query' => ['key' => $this->apiKey, 'vin' => $vin],
        ]);

        if ($res === null) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'CarsXE is unreachable right now.'];
        }
        if ($res['status'] >= 300 || ! is_array($res['json'])) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'CarsXE returned an error (HTTP ' . $res['status'] . ').', 'raw' => $res['body']];
        }

        return [
            'ok'       => true,
            'provider' => $this->label(),
            'fields'   => $this->extractFields($res['json']),
            'raw'      => $res['json'],
        ];
    }

    private function extractFields(array $data): array
    {
        $out  = [];
        $body = $data['data'] ?? $data['result'] ?? $data;

        $events = $body['history'] ?? $body['events'] ?? [];
        if (! empty($events)) {
            $out['accidentHistory'] = $events;
        }

        if (array_key_exists('theft', $body) || array_key_exists('stolen', $body)) {
            $flag                = $body['theft'] ?? $body['stolen'];
            $out['stolenStatus'] = (is_array($flag) ? ! empty($flag) : (bool) $flag)
                ? 'Reported stolen on record'
                : 'No theft records found';
        }

        $specs = $body['specifications'] ?? $body['specs'] ?? null;
        if (is_array($specs)) {
            $out['specs'] = $specs;
        }

        return $out;
    }
}
