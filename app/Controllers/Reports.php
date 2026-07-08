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

    /** AJAX: generate & store AI insights (summary + triage) for a report. */
    public function aiInsights(string $uid)
    {
        if (! feature('aiIntelligence')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'AI intelligence is disabled.']);
        }
        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Report not found']);
        }

        $res = (new \App\Services\AiService())->insights($report);
        if ($res['ok'] ?? false) {
            model(InspectionModel::class)->update($report['inspection']['id'], [
                'ai_summary' => json_encode(['summary' => $res['summary'], 'triage' => $res['triage']]),
            ]);
        }

        return $this->response->setJSON($res);
    }

    /** AJAX: email / WhatsApp the report link to the client (feature: notifications). */
    public function send(string $uid)
    {
        if (! feature('notifications')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Delivery is disabled.']);
        }

        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null || $report['inspection']['status'] !== 'completed') {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Report not found or not completed.']);
        }

        $email = trim((string) $this->request->getPost('email'));
        $phone = trim((string) $this->request->getPost('phone'));
        if ($email === '' && $phone === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Enter an email address or phone number.']);
        }

        // Remember the contact on the vehicle for next time.
        if (! empty($report['vehicle']['id'])) {
            model(\App\Models\VehicleModel::class)->update($report['vehicle']['id'], array_filter([
                'client_email' => $email ?: null,
                'client_phone' => $phone ?: null,
            ], static fn ($x) => $x !== null));
        }

        $results = (new \App\Services\NotificationService())->sendReport($report, $email ?: null, $phone ?: null);

        $sent   = array_keys(array_filter($results, static fn ($r) => $r['ok'] ?? false));
        $errors = array_map(static fn ($r) => $r['error'] ?? '', array_filter($results, static fn ($r) => ! ($r['ok'] ?? false)));

        return $this->response->setJSON([
            'ok'      => ! empty($sent),
            'sent'    => $sent,
            'error'   => empty($sent) ? (implode(' ', $errors) ?: 'Nothing was sent — check which channels are enabled.') : null,
            'partial' => ! empty($sent) && ! empty($errors) ? implode(' ', $errors) : null,
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
