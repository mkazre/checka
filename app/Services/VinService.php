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

            $fields = [];
            foreach (self::MAP as $src => $col) {
                $val = trim((string) ($row[$src] ?? ''));
                // vPIC returns "0"/"Not Applicable" for blanks — skip those.
                if ($val === '' || $val === '0' || stripos($val, 'not applicable') !== false) {
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

            return ['ok' => true, 'fields' => $fields, 'provider' => 'NHTSA vPIC'];
        } catch (\Throwable $e) {
            log_message('error', 'VinService decode failed: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'VIN lookup service is unavailable right now.'];
        }
    }
}
