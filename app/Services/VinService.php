<?php

namespace App\Services;

use Config\Services;

/**
 * Decodes a VIN into vehicle spec fields using the free NHTSA vPIC API
 * (no key, no cost): https://vpic.nhtsa.dot.gov/api/
 *
 * Returns the same shape as AiService::enrichVehicle so the front-end fill
 * logic is identical: ['ok'=>bool, 'fields'=>[...], 'error'=>?string].
 */
class VinService
{
    /** vPIC result key => our vehicle column. */
    private const MAP = [
        'Make'                => 'make',
        'Model'               => 'model',
        'ModelYear'           => 'year',
        'Series'              => 'derivative',
        'Trim'                => 'derivative',
        'BodyClass'           => 'body_type',
        'VehicleType'         => 'vehicle_type',
        'Doors'               => 'doors',
        'FuelTypePrimary'     => 'fuel',
        'TransmissionStyle'   => 'transmission',
        'GVWR'                => 'gvm',
        'EngineModel'         => 'engine_number',
    ];

    /**
     * @return array{ok: bool, fields?: array<string,string>, provider?: string, error?: string}
     */
    public function decode(string $vin): array
    {
        $full = $this->decodeFull($vin);
        if (! $full['ok']) {
            return $full;
        }

        return ['ok' => true, 'fields' => $full['fields'], 'provider' => 'NHTSA vPIC'];
    }

    /**
     * Same lookup as decode(), but also returns every non-blank field vPIC
     * gave back (90+ of them — plant info, drive type, airbags, GVWR class,
     * engine config, etc.), not just the handful mapped to our vehicle form
     * columns. Used by the VIN Check report, which wants to show everything
     * available rather than just what auto-fills the vehicle form.
     *
     * @return array{ok: bool, fields?: array<string,string>, raw?: array<string,string>, error?: string}
     */
    public function decodeFull(string $vin): array
    {
        $vin = strtoupper(trim($vin));
        if (strlen($vin) < 11) {
            return ['ok' => false, 'error' => 'A full VIN (17 characters) is needed to decode.'];
        }

        try {
            $res = Services::curlrequest([
                'timeout'     => 20,
                'http_errors' => false,
                'headers'     => ['User-Agent' => 'CheckaInspections/1.0'],
            ])->get('https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValues/' . rawurlencode($vin), [
                'query' => ['format' => 'json'],
            ]);

            $data = json_decode((string) $res->getBody(), true);
            $row  = $data['Results'][0] ?? null;
            if (! is_array($row)) {
                return ['ok' => false, 'error' => 'Could not decode that VIN.'];
            }

            $isBlank = static fn ($v) => trim((string) $v) === '' || trim((string) $v) === '0' || stripos((string) $v, 'not applicable') !== false;

            $fields = [];
            foreach (self::MAP as $src => $col) {
                $val = trim((string) ($row[$src] ?? ''));
                if ($isBlank($val)) {
                    continue;
                }
                // Don't overwrite an already-mapped column (first hit wins, e.g. Series before Trim).
                $fields[$col] ??= $val;
            }

            // Displacement: prefer litres, fall back to cylinders.
            $disp = trim((string) ($row['DisplacementL'] ?? ''));
            $cyl  = trim((string) ($row['EngineCylinders'] ?? ''));
            if ($disp !== '' && $disp !== '0') {
                $fields['power_displacement'] = round((float) $disp, 1) . 'L' . ($cyl ? ' ' . $cyl . 'cyl' : '');
            }

            if (empty($fields)) {
                return ['ok' => false, 'error' => 'VIN decoded but returned no usable fields.'];
            }

            // Every other non-blank vPIC field, for a "show everything available" view.
            $raw = [];
            foreach ($row as $k => $v) {
                if ($k === 'ErrorCode' || $k === 'ErrorText' || $k === 'SuggestedVIN' || $isBlank($v)) {
                    continue;
                }
                $raw[$k] = trim((string) $v);
            }

            return ['ok' => true, 'fields' => $fields, 'raw' => $raw];
        } catch (\Throwable $e) {
            log_message('error', 'VinService decode failed: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'VIN lookup service is unavailable right now.'];
        }
    }
}
