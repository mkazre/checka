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

        $invoice = null;
        if (feature('billing')) {
            $invoice = model(\App\Models\InvoiceModel::class)
                ->where('inspection_id', $report['inspection']['id'])
                ->where('status !=', 'void')
                ->orderBy('id', 'DESC')
                ->first();
        }

        return view('reports/view', [
            'title'   => 'Inspection Report',
            'uid'     => $uid,
            'report'  => $report,
            'compute' => service('intelligence')->compute($report),
            'ratings' => CHECKA_RATINGS,
            'quote'   => feature('quotes') ? (new \App\Services\QuoteService())->build($report) : null,
            'invoice' => $invoice,
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

    /**
     * AJAX: send to the client. `include` = report (default) | invoice | both.
     * Report-only goes through the notifications feature (email/WhatsApp);
     * anything with the invoice goes through billing (email, with the report
     * attached too for "both").
     */
    public function send(string $uid)
    {
        $include = (string) ($this->request->getPost('include') ?: 'report');
        if (! in_array($include, ['report', 'invoice', 'both'], true)) {
            $include = 'report';
        }
        if ($include === 'report' && ! feature('notifications')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Delivery is disabled.']);
        }
        if ($include !== 'report' && ! feature('billing')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Billing is disabled.']);
        }

        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null || $report['inspection']['status'] !== 'completed') {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Report not found or not completed.']);
        }

        $email = trim((string) $this->request->getPost('email'));
        $phone = trim((string) $this->request->getPost('phone'));
        if ($include !== 'report') {
            return $this->sendWithInvoice($report, $include, $email, $phone);
        }
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

        $attachPdf = $this->request->getPost('attach_pdf') ? true : false;
        $results   = (new \App\Services\NotificationService())->sendReport($report, $email ?: null, $phone ?: null, $attachPdf);

        $sent   = array_keys(array_filter($results, static fn ($r) => $r['ok'] ?? false));
        $errors = array_map(static fn ($r) => $r['error'] ?? '', array_filter($results, static fn ($r) => ! ($r['ok'] ?? false)));

        return $this->response->setJSON([
            'ok'      => ! empty($sent),
            'sent'    => $sent,
            'error'   => empty($sent) ? (implode(' ', $errors) ?: 'Nothing was sent — check which channels are enabled.') : null,
            'partial' => ! empty($sent) && ! empty($errors) ? implode(' ', $errors) : null,
        ]);
    }

    /** Invoice-only or invoice + report. Creates the invoice from the inspection charge if there isn't one yet. */
    private function sendWithInvoice(array $report, string $include, string $email, string $phone)
    {
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Invoices are sent by email — enter a valid email address.']);
        }

        $ins = $report['inspection'];
        try {
            $invoiceId = (new \App\Services\BillingService())->fromInspection($ins);
        } catch (\RuntimeException $e) {
            return $this->response->setJSON(['ok' => false, 'error' => $e->getMessage()]);
        }
        $invoice = model(\App\Models\InvoiceModel::class)->find($invoiceId);

        $res  = (new \App\Services\BillingMailer())->sendInvoice($invoice, $email, $include === 'both');
        $sent = $res['ok'] ? ['email'] : [];

        // For "both", also WhatsApp the report link when a number was given.
        $partial = null;
        if ($include === 'both' && $phone !== '' && feature('notifications') && feature('notifyWhatsapp')) {
            $wa = (new \App\Services\NotificationService())->sendReport($report, null, $phone);
            if ($wa['whatsapp']['ok'] ?? false) {
                $sent[] = 'whatsapp';
            } else {
                $partial = $wa['whatsapp']['error'] ?? 'WhatsApp not sent.';
            }
        }

        return $this->response->setJSON([
            'ok'      => $res['ok'],
            'sent'    => $sent,
            'error'   => $res['ok'] ? null : ($res['error'] ?? 'Could not send.'),
            'partial' => $res['ok'] ? $partial : null,
            'invoice' => ['no' => $invoice['invoice_no'], 'url' => site_url('invoices/' . $invoice['uid'])],
        ]);
    }

    /** Downloadable PDF (mPDF). */
    public function pdf(string $uid)
    {
        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $pdf = service('reportPdf')->build($report);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['bytes']);
    }
}
