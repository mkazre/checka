<?php

namespace App\Services\Vin;

/**
 * Paid slot 3 — Didit South Africa NATIS registry validation (didit.me).
 * The only South-Africa-specific source found with a self-serve API key:
 * it matches a VIN/chassis number against the SA national vehicle register
 * (NATIS) and returns the matched registry fields. This is a registry
 * match, NOT an accident or stolen-vehicle history report — SAPS doesn't
 * publish a stolen-vehicle API, and no SA accident-history API exists at
 * the time this was wired up. Treat a "match" as "this VIN is genuine and
 * registered", not as a clean bill of health.
 *
 * POST /v3/database-validation/ with an x-api-key header and
 * {issuing_state: "ZAF", services: "zaf_vehicle_by_vin", vin}. Verify
 * against Didit's current docs when you wire up a real key.
 */
class DiditZaProvider extends AbstractHttpProvider implements VinProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $label = 'Didit NATIS (South Africa)',
    ) {
    }

    public function label(): string
    {
        return $this->label;
    }

    public function capabilities(): array
    {
        return ['registryMatch'];
    }

    public function fetch(string $vin): array
    {
        if (! $this->configured($this->apiKey)) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'No API key configured.'];
        }

        $res = $this->request('POST', $this->baseUrl, [
            'headers' => ['x-api-key' => $this->apiKey, 'Content-Type' => 'application/json'],
            'json'    => ['issuing_state' => 'ZAF', 'services' => 'zaf_vehicle_by_vin', 'vin' => $vin],
        ]);

        if ($res === null) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'Didit is unreachable right now.'];
        }
        if ($res['status'] >= 300 || ! is_array($res['json'])) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'Didit returned an error (HTTP ' . $res['status'] . ').', 'raw' => $res['body']];
        }

        $body    = $res['json'];
        $matched = $body['status'] ?? $body['result'] ?? null;
        $source  = $body['source_data'] ?? $body['data'] ?? null;

        return [
            'ok'       => true,
            'provider' => $this->label(),
            'fields'   => array_filter([
                'registryMatch' => [
                    'status' => $matched ?? 'unknown',
                    'data'   => $source,
                ],
            ]),
            'raw'      => $body,
        ];
    }
}
