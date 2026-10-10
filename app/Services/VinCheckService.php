<?php

namespace App\Services;

use App\Services\Vin\CarsXeProvider;
use App\Services\Vin\DiditZaProvider;
use App\Services\Vin\FreeVinDecodeProvider;
use App\Services\Vin\VinAuditProvider;
use App\Services\Vin\VinProviderInterface;

/**
 * Runs a VIN through every enabled provider (free + whichever paid slots
 * are on) and merges them into one combined report. The free decoder
 * supplies specs; paid providers each fill in whatever history buckets
 * they cover (accidentHistory, stolenStatus, titleStatus, registryMatch).
 * Nothing is overwritten — if two providers both answer stolenStatus, both
 * answers are kept, labelled by provider, so a disagreement is visible
 * rather than silently dropped.
 */
class VinCheckService
{
    /** @return list<VinProviderInterface> only the providers switched on in Settings */
    public function enabledProviders(): array
    {
        $providers = [];

        if ((bool) setting('VinProviders.freeEnabled')) {
            $providers[] = new FreeVinDecodeProvider();
        }
        if ((bool) setting('VinProviders.p1Enabled') && trim((string) setting('VinProviders.p1ApiKey')) !== '') {
            $providers[] = new VinAuditProvider(
                (string) setting('VinProviders.p1ApiKey'),
                (string) (setting('VinProviders.p1BaseUrl') ?: 'https://api.vinaudit.com/v2/vinhistory'),
                (string) (setting('VinProviders.p1Label') ?: 'VinAudit'),
            );
        }
        if ((bool) setting('VinProviders.p2Enabled') && trim((string) setting('VinProviders.p2ApiKey')) !== '') {
            $providers[] = new CarsXeProvider(
                (string) setting('VinProviders.p2ApiKey'),
                (string) (setting('VinProviders.p2BaseUrl') ?: 'https://api.carsxe.com/history'),
                (string) (setting('VinProviders.p2Label') ?: 'CarsXE'),
            );
        }
        if ((bool) setting('VinProviders.p3Enabled') && trim((string) setting('VinProviders.p3ApiKey')) !== '') {
            $providers[] = new DiditZaProvider(
                (string) setting('VinProviders.p3ApiKey'),
                (string) (setting('VinProviders.p3BaseUrl') ?: 'https://verification.didit.me/v3/database-validation/'),
                (string) (setting('VinProviders.p3Label') ?: 'Didit NATIS (South Africa)'),
            );
        }

        return $providers;
    }

    /**
     * @return array{
     *   vin: string, ok: bool, providersUsed: list<string>, providersFailed: list<array{provider:string,error:string}>,
     *   specs: array<string,mixed>, accidentHistory: list<array>, stolenStatus: list<array{provider:string,status:string}>,
     *   titleStatus: list<array{provider:string,status:string}>, registryMatch: list<array{provider:string,data:mixed}>,
     *   raw: array<string,mixed>
     * }
     */
    public function run(string $vin): array
    {
        $vin = strtoupper(trim($vin));

        $out = [
            'vin'              => $vin,
            'ok'               => false,
            'providersUsed'    => [],
            'providersFailed'  => [],
            'specs'            => [],
            'accidentHistory'  => [],
            'stolenStatus'     => [],
            'titleStatus'      => [],
            'registryMatch'    => [],
            'raw'              => [],
        ];

        $providers = $this->enabledProviders();
        if ($providers === []) {
            $out['providersFailed'][] = ['provider' => '—', 'error' => 'No VIN data providers are enabled in Settings.'];

            return $out;
        }

        foreach ($providers as $provider) {
            $res = $provider->fetch($vin);
            $out['raw'][$provider->label()] = $res['raw'] ?? null;

            if (! ($res['ok'] ?? false)) {
                $out['providersFailed'][] = ['provider' => $provider->label(), 'error' => $res['error'] ?? 'Unknown error'];

                continue;
            }

            $out['providersUsed'][] = $provider->label();
            $fields = $res['fields'] ?? [];

            if (! empty($fields['specs']) && is_array($fields['specs'])) {
                // First provider to answer a given spec field wins (keeps the free decode as the base).
                $out['specs'] += $fields['specs'];
            }
            if (! empty($fields['accidentHistory'])) {
                $out['accidentHistory'][] = ['provider' => $provider->label(), 'events' => $fields['accidentHistory']];
            }
            if (isset($fields['stolenStatus'])) {
                $out['stolenStatus'][] = ['provider' => $provider->label(), 'status' => (string) $fields['stolenStatus']];
            }
            if (isset($fields['titleStatus'])) {
                $out['titleStatus'][] = ['provider' => $provider->label(), 'status' => (string) $fields['titleStatus']];
            }
            if (isset($fields['registryMatch'])) {
                $out['registryMatch'][] = ['provider' => $provider->label(), 'data' => $fields['registryMatch']];
            }
        }

        $out['ok'] = $out['providersUsed'] !== [];

        return $out;
    }

    /** Human-readable headline for the archive list ("Clean", "Stolen record found", "No history data"). */
    public function headline(array $report): string
    {
        foreach ($report['stolenStatus'] ?? [] as $s) {
            if (stripos($s['status'], 'stolen') !== false && stripos($s['status'], 'no ') !== 0 && stripos($s['status'], 'no theft') === false) {
                return 'Stolen record found';
            }
        }
        if (! empty($report['accidentHistory'])) {
            return 'Accident history on record';
        }
        if (! empty($report['registryMatch']) || ! empty($report['stolenStatus']) || ! empty($report['titleStatus'])) {
            return 'Clean — no adverse records found';
        }
        if (! empty($report['specs'])) {
            return 'Specs decoded — no history provider checked';
        }

        return 'No data returned';
    }
}
