<?php

namespace App\Controllers;

use App\Models\InspectionModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Reports extends BaseController
{
    /** On-screen report. */
    public function view(string $uid)
    {
        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('reports/view', [
            'title'   => 'Inspection Report',
            'uid'     => $uid,
            'report'  => $report,
            'compute' => service('intelligence')->compute($report),
            'ratings' => CHECKA_RATINGS,
            'quote'   => feature('quotes') ? (new \App\Services\QuoteService())->build($report) : null,
        ]);
    }

    /** Downloadable PDF (mPDF). */
    public function pdf(string $uid)
    {
        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $imgSvc     = new \App\Services\VehicleImageService();
        $stockImage = $imgSvc->dataUri($imgSvc->imageFor($report['vehicle'] ?? []));

        $html = view('reports/pdf', [
            'report'     => $report,
            'compute'    => service('intelligence')->compute($report),
            'ratings'    => CHECKA_RATINGS,
            'uploadDir'  => WRITEPATH . 'uploads/',
            'stockImage' => $stockImage,
            'quote'      => feature('quotes') ? (new \App\Services\QuoteService())->build($report) : null,
        ]);

        $v        = $report['vehicle'] ?? [];
        $filename = 'Checka-Report-' . trim(($v['make'] ?? '') . '-' . ($v['model'] ?? '') . '-' . substr($uid, 0, 8), '-') . '.pdf';
        $filename = preg_replace('/\s+/', '-', $filename);

        $bytes = service('pdf')->toString($html, 'Checka Inspection Report');

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($bytes);
    }
}
