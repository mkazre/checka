<?php

namespace App\Services;

use Config\Services;

/**
 * Outbound email using the SMTP/sendmail settings under Notify.*, with
 * in-memory PDFs as attachments. Attachments go via temp files in
 * writable/cache because that's the path known to produce intact PDF
 * attachments on this host (see NotificationService).
 */
class Mailer
{
    public function configured(): bool
    {
        return trim((string) setting('Notify.fromEmail')) !== '';
    }

    /**
     * @param array<string,string> $attachments filename => raw bytes
     * @return array{ok:bool,error?:string}
     */
    public function send(string $to, string $subject, string $html, array $attachments = [], ?string $cc = null): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'Email sender is not configured (Settings → Notifications).'];
        }
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Invalid recipient email address.'];
        }

        $email = Services::email(null, false);
        $email->initialize([
            'protocol'   => (string) (setting('Notify.protocol') ?: 'mail'),
            'mailType'   => 'html',
            'charset'    => 'UTF-8',
            'wordWrap'   => true,
            'SMTPHost'   => (string) setting('Notify.smtpHost'),
            'SMTPUser'   => (string) setting('Notify.smtpUser'),
            'SMTPPass'   => (string) setting('Notify.smtpPass'),
            'SMTPPort'   => (int) (setting('Notify.smtpPort') ?: 587),
            'SMTPCrypto' => (string) setting('Notify.smtpCrypto'),
        ]);
        $email->setFrom((string) setting('Notify.fromEmail'), (string) (billing('companyName') ?: setting('Notify.fromName')));
        $replyTo = trim((string) setting('Billing.companyEmail'));
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $email->setReplyTo($replyTo);
        }
        $email->setTo($to);
        if ($cc && filter_var($cc, FILTER_VALIDATE_EMAIL) && strcasecmp($cc, $to) !== 0) {
            $email->setCC($cc);
        }
        $email->setSubject($subject);
        $email->setMessage($html);

        $tmp = [];
        $dir = WRITEPATH . 'cache/mail_attach';
        if ($attachments !== [] && ! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        foreach ($attachments as $name => $bytes) {
            $path = $dir . '/' . bin2hex(random_bytes(8)) . '.pdf';
            if (@file_put_contents($path, $bytes) !== false) {
                $tmp[] = $path;
                $email->attach($path, '', $name);
            } else {
                log_message('error', 'Mailer: could not write attachment ' . $path);
            }
        }

        $sent = $email->send(false);
        foreach ($tmp as $p) {
            @unlink($p);
        }
        if (! $sent) {
            log_message('error', 'Mailer send failed: ' . $email->printDebugger(['headers']));

            return ['ok' => false, 'error' => 'Email could not be sent (check the SMTP settings).'];
        }

        return ['ok' => true];
    }

    /**
     * Branded HTML email wrapper.
     *
     * @param list<array{label:string,url:string}> $buttons
     */
    public static function layout(string $heading, string $bodyHtml, array $buttons = [], string $footerHtml = ''): string
    {
        $primary = brand('primary');
        $btns    = '';
        foreach ($buttons as $i => $b) {
            $style = $i === 0
                ? 'background:' . $primary . ';color:#fff;'
                : 'background:#fff;color:' . $primary . ';border:2px solid ' . $primary . ';';
            $btns .= '<a href="' . esc($b['url']) . '" style="' . $style . 'text-decoration:none;padding:12px 24px;border-radius:9px;font-weight:bold;display:inline-block;margin:4px 6px">' . esc($b['label']) . '</a>';
        }

        return '<div style="background:#f4f5f6;padding:24px 12px;font-family:Arial,Helvetica,sans-serif">'
            . '<div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e4e6;color:#2d2d2d">'
            . '<div style="text-align:center;border-bottom:3px solid ' . $primary . ';padding:18px"><img src="' . esc(brand('logo')) . '" alt="' . esc(billing('companyName')) . '" style="height:46px"></div>'
            . '<div style="padding:24px 24px 8px">'
            . '<h2 style="margin:0 0 10px;font-size:20px">' . esc($heading) . '</h2>'
            . '<div style="color:#4a4a4a;line-height:1.6;font-size:14px">' . $bodyHtml . '</div>'
            . ($btns !== '' ? '<p style="text-align:center;margin:24px 0 8px">' . $btns . '</p>' : '')
            . '</div>'
            . ($footerHtml !== '' ? '<div style="padding:8px 24px 18px;color:#555;font-size:12.5px;line-height:1.55">' . $footerHtml . '</div>' : '')
            . '<div style="text-align:center;color:#9aa0a6;font-size:11px;padding:14px;border-top:1px solid #eef0f2">'
            . esc(billing('companyName'))
            . (trim((string) setting('Billing.companyPhone')) !== '' ? ' · ' . esc(setting('Billing.companyPhone')) : '')
            . (trim((string) setting('Billing.companyEmail')) !== '' ? ' · ' . esc(setting('Billing.companyEmail')) : '')
            . '</div></div></div>';
    }

    /** Bank details block for emails (empty string when EFT is off or unset). */
    public static function bankDetailsHtml(string $reference = ''): string
    {
        if (! setting('Billing.payEftEnabled') || trim((string) setting('Billing.bankAccountNumber')) === '') {
            return '';
        }
        $rows = [
            'Bank'           => setting('Billing.bankName'),
            'Account name'   => setting('Billing.bankAccountName'),
            'Account number' => setting('Billing.bankAccountNumber'),
            'Branch code'    => setting('Billing.bankBranchCode'),
            'Account type'   => setting('Billing.bankAccountType'),
            'SWIFT'          => setting('Billing.bankSwift'),
            'Reference'      => $reference,
        ];
        $html = '<table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:6px">';
        foreach ($rows as $k => $v) {
            if (trim((string) $v) === '') {
                continue;
            }
            $html .= '<tr><td style="padding:4px 0;color:#7a7a7a;width:40%">' . esc($k) . '</td><td style="padding:4px 0;font-weight:bold">' . esc((string) $v) . '</td></tr>';
        }

        return '<div style="background:#f7f9fa;border:1px solid #e2e4e6;border-radius:9px;padding:12px 14px;margin-top:10px">'
            . '<div style="font-weight:bold;margin-bottom:2px">Pay by EFT</div>' . $html . '</table></div>';
    }
}
