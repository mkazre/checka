<?php

namespace App\Services\Vin;

/**
 * Dev/testing only — returns fabricated accident/stolen/title data instantly
 * with no API key and no real network call, so the full VIN Check flow
 * (archive, report view, PDF, email, QR) can be exercised end-to-end before
 * a real paid provider key is on hand. The data is deterministic per VIN
 * (seeded off the VIN itself) purely so repeat checks on the same VIN look
 * consistent, not because it reflects anything real about that vehicle.
 *
 * Every field this returns is clearly labelled "(mock)" so it can never be
 * mistaken for genuine data in a report, PDF, or shared link.
 */
class MockProvider implements VinProviderInterface
{
    public function label(): string
    {
        return 'Mock (dev/testing only)';
    }

    public function capabilities(): array
    {
        return ['accidentHistory', 'stolenStatus', 'titleStatus'];
    }

    public function fetch(string $vin): array
    {
        // Deterministic per VIN so the same VIN always demos the same way.
        $seed   = crc32(strtoupper(trim($vin)));
        $stolen = ($seed % 10) === 0; // 1 in 10 VINs "flagged" for demo variety
        $events = $seed % 3; // 0, 1 or 2 fake accident events

        $accidents = [];
        for ($i = 0; $i < $events; $i++) {
            $accidents[] = [
                'date'        => date('Y-m-d', strtotime('-' . (($seed % 900) + ($i * 120)) . ' days')),
                'description' => $i === 0 ? 'Minor front-end collision (mock)' : 'Reported hail/body damage (mock)',
            ];
        }

        return [
            'ok'       => true,
            'provider' => $this->label(),
            'fields'   => array_filter([
                'accidentHistory' => $accidents ?: null,
                'stolenStatus'    => $stolen ? 'Reported stolen on record (mock)' : 'No theft records found (mock)',
                'titleStatus'     => ($seed % 7) === 0 ? 'Salvage title (mock)' : 'Clean title (mock)',
            ]),
            'raw' => ['note' => 'Fabricated demo data — not a real vehicle history check.', 'seed' => $seed],
        ];
    }
}
