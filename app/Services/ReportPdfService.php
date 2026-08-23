<?php

namespace App\Services;

/**
 * Builds the "reports/pdf" view for an assembled report into PDF bytes.
 * Single source of truth so the admin download, the client-portal download,
 * and the emailed attachment can never drift from one another.
 */
class ReportPdfService
{
    /**
     * @param array<string, mixed> $report Output of InspectionModel::assembleReport()
     *
     * @return array{bytes:string, filename:string}
     */
    public function build(array $report): array
    {
        $imgSvc     = new VehicleImageService();
        $stockImage = $imgSvc->dataUri($imgSvc->imageFor($report['vehicle'] ?? []));

        $uid = $report['inspection']['report_uid'] ?? '';

        $html = view('reports/pdf', [
            'report'     => $report,
            'compute'    => service('intelligence')->compute($report),
            'ratings'    => CHECKA_RATINGS,
            'uploadDir'  => WRITEPATH . 'uploads/',
            'stockImage' => $stockImage,
            'quote'      => feature('quotes') ? (new QuoteService())->build($report) : null,
        ]);

        $v        = $report['vehicle'] ?? [];
        $filename = 'Checka-Report-' . trim(($v['make'] ?? '') . '-' . ($v['model'] ?? '') . '-' . substr((string) $uid, 0, 8), '-') . '.pdf';
        $filename = preg_replace('/\s+/', '-', $filename);

        return [
            'bytes'    => service('pdf')->toString($html, 'Checka Inspection Report'),
            'filename' => $filename,
        ];
    }
}
