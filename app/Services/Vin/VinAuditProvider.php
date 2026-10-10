<?php

namespace App\Services\Vin;

/**
 * Paid slot 1 — VinAudit (vinaudit.com). NMVTIS-backed US vehicle history:
 * title/salvage status, reported theft, accident/damage events, odometer
 * readings. Self-serve API key from their site.
 *
 * Endpoint/response shape below reflects their published "Vehicle History
 * API" (key + vin query params, JSON response with a `records` array of
 * dated events). Vendor APIs change without notice — if fields come back
 * empty after you wire up a real key, check their current docs and adjust
 * the mapping in extractFields() below; the raw response is always kept on
 * the report regardless, so no data is lost even if mapping drifts.
 */
class VinAuditProvider extends AbstractHttpProvider implements VinProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $label = 'VinAudit',
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

        $res = $this->request('GET', $this->baseUrl, [
            'query' => ['key' => $this->apiKey, 'vin' => $vin, 'format' => 'json'],
        ]);

        if ($res === null) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'VinAudit is unreachable right now.'];
        }
        if ($res['status'] >= 300 || ! is_array($res['json'])) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'VinAudit returned an error (HTTP ' . $res['status'] . ').', 'raw' => $res['body']];
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
        $out    = [];
        $events = $data['events'] ?? $data['records'] ?? $data['history'] ?? [];

        $accidents = array_values(array_filter((array) $events, static fn ($e) => stripos((string) ($e['type'] ?? $e['category'] ?? ''), 'accident') !== false
            || stripos((string) ($e['type'] ?? $e['category'] ?? ''), 'damage') !== false));
        if ($accidents !== []) {
            $out['accidentHistory'] = $accidents;
        }

        $theft = $data['theft'] ?? $data['theft_records'] ?? null;
        if ($theft !== null) {
            $isStolen           = is_array($theft) ? ! empty($theft) : (bool) $theft;
            $out['stolenStatus'] = $isStolen ? 'Reported stolen on record' : 'No theft records found';
        }

        $title = $data['title'] ?? $data['title_status'] ?? null;
        if ($title !== null) {
            $out['titleStatus'] = is_array($title) ? ($title['status'] ?? json_encode($title)) : (string) $title;
        }

        return $out;
    }
}
