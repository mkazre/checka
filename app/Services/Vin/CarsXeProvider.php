<?php

namespace App\Services\Vin;

/**
 * Paid slot 2 — CarsXE (carsxe.com). GET https://api.carsxe.com/history
 * with `key` + `vin` query params. Confirmed live against their real
 * endpoint. Response shape (verified, not guessed):
 *
 *  - junkAndSalvageInformation[], insuranceInformation[] — actual dated
 *    events for this VIN (salvage/junk yard records, insurance total-loss
 *    records). Closest thing NMVTIS gives to "damage/accident" history.
 *  - currentTitleInformation[] (each with a nested HistoricTitleAbstract[])
 *    — actual title records for this VIN: issuing state, date, odometer.
 *  - brandsInformation[] — THIS IS NOT VEHICLE-SPECIFIC. It's the full
 *    ~67-entry NMVTIS title-brand code glossary (code/name/description)
 *    bundled into every response as a reference table. The ONLY entries
 *    that actually apply to this VIN are the ones carrying an extra
 *    nested `record` object (ReportingEntityAbstract, VehicleBrandCode,
 *    VehicleBrandDate) — everything else in that array is noise and must
 *    be filtered out, both from the summary and from the raw dump, or a
 *    clean vehicle (with zero real brand events) looks like it has ~67
 *    findings.
 *  - success:false + message on error or "nothing on file for this VIN".
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
        return ['accidentHistory', 'stolenStatus', 'titleStatus'];
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

        $json = $res['json'];

        // CarsXE answers both real errors and "nothing on file for this VIN"
        // with {success:false, message:...} — HTTP status alone can't tell
        // those apart (401 = bad key, 404 commonly = no US history record).
        if (is_array($json) && ($json['success'] ?? null) === false) {
            $msg = (string) ($json['message'] ?? 'No response message.');
            if ($res['status'] === 404 || stripos($msg, 'not found') !== false || stripos($msg, 'no record') !== false) {
                return [
                    'ok'       => true,
                    'provider' => $this->label(),
                    'fields'   => ['stolenStatus' => 'No US title/history record found for this VIN (CarsXE is NMVTIS-backed — US vehicles only).'],
                    'raw'      => $json,
                ];
            }

            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'CarsXE: ' . $msg, 'raw' => $json];
        }

        if ($res['status'] >= 300 || ! is_array($json)) {
            return ['ok' => false, 'provider' => $this->label(), 'fields' => [], 'error' => 'CarsXE returned an error (HTTP ' . $res['status'] . ').', 'raw' => $res['body']];
        }

        return [
            'ok'       => true,
            'provider' => $this->label(),
            'fields'   => $this->extractFields($json),
            // Strip the brand-code glossary noise out of what gets shown in
            // the "full data" dump — keep only entries that actually apply
            // to this VIN, same filter used by extractFields() below.
            'raw'      => array_merge($json, ['brandsInformation' => $this->applicableBrands($json)]),
        ];
    }

    /** Only brandsInformation[] entries with a nested `record` — the rest is the static code glossary, not vehicle data. */
    private function applicableBrands(array $data): array
    {
        return array_values(array_filter((array) ($data['brandsInformation'] ?? []), static fn ($b) => is_array($b) && isset($b['record'])));
    }

    private function extractFields(array $data): array
    {
        $out = [];

        // Salvage/junk + insurance total-loss records are the closest thing
        // NMVTIS gives to "damage/accident" history — individual at-fault
        // collision claims aren't part of this data set.
        $events = [];
        foreach ((array) ($data['junkAndSalvageInformation'] ?? []) as $r) {
            $events[] = [
                'date'        => $r['VehicleObtainedDate'] ?? null,
                'description' => trim('Junk/salvage — ' . ($r['ReportingEntityAbstract']['EntityName'] ?? '') . ' (' . ($r['VehicleDispositionText'] ?? '') . ')'),
            ];
        }
        foreach ((array) ($data['insuranceInformation'] ?? []) as $r) {
            $events[] = [
                'date'        => $r['VehicleObtainedDate'] ?? ($r['Date'] ?? null),
                'description' => trim('Insurance total-loss record — ' . ($r['ReportingEntityAbstract']['EntityName'] ?? $r['EntityName'] ?? '')),
            ];
        }
        if ($events !== []) {
            $out['accidentHistory'] = $events;
        }

        // Real, vehicle-specific brand events only (see applicableBrands()).
        $applicable = $this->applicableBrands($data);
        $stolen     = false;
        $brandLines = [];
        foreach ($applicable as $b) {
            $text = ($b['name'] ?? '') . ' ' . ($b['description'] ?? '');
            if (stripos($text, 'theft') !== false || stripos($text, 'stolen') !== false) {
                $stolen = true;
            }
            $date  = $b['record']['VehicleBrandDate']['Date'] ?? null;
            $state = $b['record']['ReportingEntityAbstract']['EntityName'] ?? '';
            $brandLines[] = trim(($b['name'] ?? 'Brand') . ($date ? ' (' . substr((string) $date, 0, 10) . ')' : '') . ($state ? ' — ' . $state : ''));
        }
        if ($applicable !== []) {
            $out['stolenStatus'] = $stolen
                ? ('Theft-related title brand on record: ' . implode(', ', $brandLines))
                : ('Title brand(s) on record (not theft-related): ' . implode(', ', $brandLines));
        } elseif (array_key_exists('brandsInformation', $data)) {
            $out['stolenStatus'] = 'No title brands on record';
        }

        $titleBits = [];
        foreach ((array) ($data['currentTitleInformation'] ?? []) as $t) {
            $odo = trim((string) ($t['VehicleOdometerReadingMeasure'] ?? ''), '0') ?: ($t['VehicleOdometerReadingMeasure'] ?? '');
            $titleBits[] = trim(
                ($t['TitleIssuingAuthorityName'] ?? '') . ' — ' . ($t['TitleIssueDate']['Date'] ?? '')
                . ($odo !== '' ? ' — odometer ' . $odo . ' ' . ($t['VehicleOdometerReadingUnitCode'] ?? '') : ''),
                ' —',
            );
        }
        if ($titleBits !== []) {
            $out['titleStatus'] = implode('; ', array_filter($titleBits));
        }

        return $out;
    }
}
