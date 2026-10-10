<?php

namespace App\Services;

/**
 * Builds the "vin_checks/pdf" view for a saved VIN report into PDF bytes.
 * Mirrors ReportPdfService so the admin download and the emailed
 * attachment never drift from one another.
 */
class VinReportPdfService
{
    /**
     * @param array<string, mixed> $row  VinReportModel row
     * @param array<string, mixed> $data Decoded payload (VinReportModel::payload($row))
     *
     * @return array{bytes:string, filename:string}
     */
    public function build(array $row, array $data): array
    {
        $html = view('vin_checks/pdf', [
            'row'       => $row,
            'data'      => $data,
            'shareUrl'  => site_url('vin-check/' . $row['report_uid']),
        ]);

        $filename = 'Checka-VIN-Check-' . preg_replace('/[^A-Z0-9]+/', '', strtoupper($row['vin'])) . '.pdf';

        return [
            'bytes'    => service('pdf')->toString($html, 'VIN History Check — ' . $row['vin']),
            'filename' => $filename,
        ];
    }
}
