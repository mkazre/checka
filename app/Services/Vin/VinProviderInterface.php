<?php

namespace App\Services\Vin;

/**
 * A single VIN data source (free decode or a paid history provider). Every
 * provider — however different its real API — reports back in this same
 * shape so VinCheckService can merge several of them into one report
 * without caring which vendor said what.
 */
interface VinProviderInterface
{
    /** Short label shown in the UI and stored against the report ("NHTSA vPIC", "VinAudit", ...). */
    public function label(): string;

    /** Which buckets this provider can fill, e.g. ['specs'], or ['accidentHistory','stolenStatus']. */
    public function capabilities(): array;

    /**
     * @return array{
     *   ok: bool,
     *   provider: string,
     *   fields: array<string, mixed>,
     *   raw?: mixed,
     *   error?: string
     * }
     */
    public function fetch(string $vin): array;
}
