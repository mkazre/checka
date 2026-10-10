<?php

namespace App\Services\Vin;

/**
 * Paid slot 4 — carVertical (carvertical.com). Strong international VIN
 * history coverage (Europe-heavy, plus mileage/odometer fraud detection,
 * accident/damage records, theft flags). Their business API is
 * sales-gated — there is no public self-serve signup or published
 * endpoint spec; you get a key and API docs from their sales/integration
 * team after contacting them via carvertical.com/business/api.
 *
 * The endpoint/auth shape below (x-api-key header, GET by VIN, JSON
 * response) is a best-effort placeholder until you have their actual
 * contract in hand — update fetch()/extractFields() to match what their
 * team gives you. The raw response is always kept on the report
 * regardless, so nothing is lost even before the mapping is accurate.
 */
class CarVerticalProvider extends AbstractHttpProvider implements VinProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $label = 'carVertical',
    ) {
    }

    public function label(): string
    {
        return $this->label;
    }

    public function capabilities(): array
    {
        return ['accidentHistory', 'stolenStatus', 'titleStatus'];
    }

    public function fetch(string $vin): array
    {
        if (! $this->configured($this->apiKey)) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'No API key configured.'];
        }

        $res = $this->request('GET', rtrim($this->baseUrl, '/') . '/' . rawurlencode($vin), [
            'headers' => ['x-api-key' => $this->apiKey],
        ]);

        if ($res === null) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'carVertical is unreachable right now.'];
        }
        if ($res['status'] >= 300 || ! is_array($res['json'])) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'carVertical returned an error (HTTP ' . $res['status'] . ').', 'raw' => $res['body']];
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
        $body = $data['data'] ?? $data['report'] ?? $data;

        $events = $body['accidents'] ?? $body['damages'] ?? $body['history'] ?? [];
        if (! empty($events)) {
            $out['accidentHistory'] = $events;
        }

        if (array_key_exists('stolen', $body) || array_key_exists('theft', $body)) {
            $flag                = $body['stolen'] ?? $body['theft'];
            $out['stolenStatus'] = (is_array($flag) ? ! empty($flag) : (bool) $flag)
                ? 'Reported stolen on record'
                : 'No theft records found';
        }

        $title = $body['title_status'] ?? $body['title'] ?? null;
        if ($title !== null) {
            $out['titleStatus'] = is_array($title) ? ($title['status'] ?? json_encode($title)) : (string) $title;
        }

        return $out;
    }
}
