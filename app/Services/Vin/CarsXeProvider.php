<?php

namespace App\Services\Vin;

/**
 * Paid slot 2 — CarsXE (carsxe.com). GET https://api.carsxe.com/history
 * with `key` + `vin` query params. Confirmed live against their real
 * endpoint: it returns raw NMVTIS (US title registry) data — there is no
 * simple "stolen: true/false" field, theft is inferred from brand/title
 * records whose code or description mentions theft. Top-level keys:
 * junkAndSalvageInformation, insuranceInformation, brandsInformation,
 * currentTitleInformation, HistoricTitleAbstract, historyInformation,
 * plus success/vin/vinChanged. An error or "no record for this VIN"
 * response looks like {"success":false,"message":"..."}.
 *
 * Because it's NMVTIS-backed, it only has data for US-titled vehicles —
 * a South African VIN will typically come back as "no record found",
 * which is not an error, just no US history to report.
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
            'raw'      => $json,
        ];
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

        // Theft is a brand code/description on title-brand records, not a flag.
        $stolen = false;
        $brandNotes = [];
        foreach ((array) ($data['brandsInformation'] ?? []) as $b) {
            $text = ($b['VehicleBrandCode'] ?? '') . ' ' . ($b['description'] ?? '');
            if (stripos($text, 'theft') !== false || stripos($text, 'stolen') !== false) {
                $stolen = true;
                $brandNotes[] = trim($b['VehicleBrandCode'] ?? $b['description'] ?? '');
            }
        }
        if (! empty($data['brandsInformation'])) {
            $out['stolenStatus'] = $stolen
                ? ('Theft-related title brand on record: ' . implode(', ', array_unique($brandNotes)))
                : 'No theft-related title brand found';
        }

        $titleBits = [];
        foreach ((array) ($data['currentTitleInformation'] ?? []) as $t) {
            $titleBits[] = trim(($t['TitleIssuingAuthorityName'] ?? '') . ' — ' . ($t['TitleIssueDate'] ?? ''), ' —');
        }
        if ($titleBits !== []) {
            $out['titleStatus'] = implode('; ', array_filter($titleBits));
        }

        return $out;
    }
}
