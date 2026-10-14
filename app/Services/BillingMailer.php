<?php

namespace App\Services;

use App\Models\ClientModel;
use App\Models\InspectionModel;
use App\Models\InvoiceModel;

/**
 * Emails invoices (optionally with the inspection report) and statement
 * bundles. Every email carries the PDF(s), a Pay Now link and the EFT
 * bank details.
 */
class BillingMailer
{
    /**
     * @param bool $withReport also attach + link the linked inspection's report
     * @return array{ok:bool,error?:string}
     */
    public function sendInvoice(array $invoice, string $to, bool $withReport = false, ?string $cc = null): array
    {
        $svc    = new BillingService();
        $bundle = $svc->invoiceBundle($invoice);
        if ($invoice['status'] === 'void') {
            return ['ok' => false, 'error' => 'A void invoice cannot be sent.'];
        }
        if ($invoice['status'] === 'draft') {
            $svc->markIssued((int) $invoice['id']);
            $invoice = model(InvoiceModel::class)->find($invoice['id']);
            $bundle  = $svc->invoiceBundle($invoice);
        }

        $attachments = [];
        $pdf         = (new BillingPdfService())->invoice($invoice);
        $attachments[$pdf['filename']] = $pdf['bytes'];

        $reportLine = '';
        $buttons    = [];
        if ($bundle['balance'] > 0) {
            $buttons[] = ['label' => 'Pay now — ' . money($bundle['balance']), 'url' => $bundle['payUrl']];
        }
        if ($withReport && $bundle['inspection']) {
            $uid    = $bundle['inspection']['report_uid'];
            $report = model(InspectionModel::class)->assembleReport($uid);
            if ($report !== null && $report['inspection']['status'] === 'completed') {
                try {
                    $rp = service('reportPdf')->build($report);
                    $attachments[$rp['filename']] = $rp['bytes'];
                } catch (\Throwable $e) {
                    log_message('error', 'Invoice email: report PDF failed: ' . $e->getMessage());
                }
                $link       = feature('clientPortal') ? site_url('portal/' . $uid) : site_url('verify/' . $uid);
                $buttons[]  = ['label' => 'View inspection report', 'url' => $link];
                $reportLine = ' The inspection report is attached as well.';
            }
        }

        $client = $bundle['client'];
        $body   = '<p>Dear ' . esc($client['contact_person'] ?: $client['name']) . ',</p>'
            . '<p>Please find attached invoice <b>' . esc($invoice['invoice_no']) . '</b> for <b>' . esc(money($invoice['total'])) . '</b>'
            . ($bundle['inspection'] ? ' for the vehicle inspection we carried out' : '') . '.' . $reportLine . '</p>'
            . ($bundle['balance'] > 0
                ? '<p>Amount due: <b>' . esc(money($bundle['balance'])) . '</b> by <b>' . esc(date('j F Y', strtotime($invoice['due_date']))) . '</b>.</p>'
                : '<p><b>This invoice is paid in full — thank you.</b></p>');

        $footer = $bundle['balance'] > 0 ? Mailer::bankDetailsHtml($invoice['invoice_no']) : '';
        $html   = Mailer::layout('Invoice ' . $invoice['invoice_no'], $body, $buttons, $footer);

        $subject = 'Invoice ' . $invoice['invoice_no'] . ' from ' . billing('companyName') . ($withReport && $reportLine ? ' + inspection report' : '');
        $res     = (new Mailer())->send($to, $subject, $html, $attachments, $cc);
        if ($res['ok']) {
            model(InvoiceModel::class)->update($invoice['id'], ['sent_at' => date('Y-m-d H:i:s')]);
        }

        return $res;
    }

    /**
     * Statement for a period with every open invoice attached in the same PDF.
     *
     * @return array{ok:bool,error?:string}
     */
    public function sendStatement(array $client, string $from, string $to, ?string $toEmail = null): array
    {
        $toEmail ??= ClientModel::invoiceEmail($client);
        if ($toEmail === '') {
            return ['ok' => false, 'error' => 'This client has no email address.'];
        }

        $svc       = new BillingService();
        $statement = $svc->statement((int) $client['id'], $from, $to);
        $pdf       = (new BillingPdfService())->statement($client, $from, $to, true);
        $closing   = (float) $statement['closing'];
        $payUrl    = site_url('pay/account/' . $client['uid']);
        $period    = date('F Y', strtotime($to));

        $openList = '';
        if ($statement['open'] !== []) {
            $openList = '<table style="width:100%;border-collapse:collapse;font-size:13px;margin:8px 0">';
            foreach ($statement['open'] as $o) {
                $openList .= '<tr><td style="padding:5px 0;border-bottom:1px solid #eef0f2">' . esc($o['invoice_no'])
                    . ' <span style="color:#7a7a7a">· due ' . esc(date('j M Y', strtotime($o['due_date']))) . '</span></td>'
                    . '<td style="padding:5px 0;border-bottom:1px solid #eef0f2;text-align:right;font-weight:bold">' . esc(money(InvoiceModel::balance($o))) . '</td></tr>';
            }
            $openList .= '</table>';
        }

        $body = '<p>Dear ' . esc($client['contact_person'] ?: $client['name']) . ',</p>'
            . '<p>Please find attached your statement for <b>' . esc($period) . '</b>'
            . ($statement['open'] !== [] ? ', together with your open invoices' : '') . '.</p>'
            . ($closing > 0
                ? '<p>Balance due: <b style="font-size:16px">' . esc(money($closing)) . '</b></p>' . $openList
                : '<p><b>Your account is up to date' . ($closing < 0 ? ' — you have a credit of ' . esc(money(abs($closing))) : '') . '. Thank you!</b></p>');

        $buttons = $closing > 0 ? [['label' => 'Pay now — ' . money($closing), 'url' => $payUrl]] : [];
        $footer  = $closing > 0 ? Mailer::bankDetailsHtml(strtoupper(substr((string) $client['uid'], 0, 8))) : '';
        $html    = Mailer::layout('Statement — ' . $period, $body, $buttons, $footer);

        return (new Mailer())->send($toEmail, 'Statement for ' . $period . ' — ' . billing('companyName'), $html, [$pdf['filename'] => $pdf['bytes']]);
    }

    /** Previous calendar month as [from, to, 'YYYY-MM']. */
    public static function lastMonth(): array
    {
        $from = date('Y-m-01', strtotime('first day of last month'));
        $to   = date('Y-m-t', strtotime($from));

        return [$from, $to, substr($from, 0, 7)];
    }
}
