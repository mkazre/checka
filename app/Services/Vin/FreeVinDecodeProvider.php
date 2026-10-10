<?php

namespace App\Services\Vin;

use App\Services\VinService;

/**
 * Free source: NHTSA vPIC VIN decode (no key, no cost). Wraps the existing
 * VinService (used elsewhere for auto-filling the vehicle form) and
 * reshapes its output into the VinProviderInterface contract so it can sit
 * alongside paid history providers in one combined VIN Check report.
 *
 * Coverage note: vPIC is a US registry. Manufacturers that register global
 * VIN patterns decode well regardless of where the car was sold; SA-only
 * models/derivatives may come back thin. It never returns accident, stolen
 * or service history — that data isn't published for free anywhere.
 */
class FreeVinDecodeProvider implements VinProviderInterface
{
    public function label(): string
    {
        return 'NHTSA vPIC (free)';
    }

    public function capabilities(): array
    {
        return ['specs'];
    }

    public function fetch(string $vin): array
    {
        $res = (new VinService())->decodeFull($vin);

        if (! ($res['ok'] ?? false)) {
            return [
                'ok'       => false,
                'provider' => $this->label(),
                'fields'   => [],
                'error'    => $res['error'] ?? 'VIN decode failed.',
            ];
        }

        return [
            'ok'       => true,
            'provider' => $this->label(),
            // 'specs' drives the summary grid (the handful mapped to vehicle-form
            // columns); 'raw' carries every other non-blank vPIC field for the
            // "full provider data" section.
            'fields'   => ['specs' => $res['fields']],
            'raw'      => $res['raw'] ?? $res['fields'],
        ];
    }
}
