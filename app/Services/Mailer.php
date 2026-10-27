<?php

namespace App\Services;

use App\Libraries\CheckaEmail;

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

        $email = self::newEmail();
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
            $debug  = $email->printDebugger([]);
            $reason = self::serverReason($debug);

            return [
                'ok'     => false,
                'error'  => 'Email could not be sent' . ($reason !== '' ? ' — the mail server said: ' . $reason : ' (check the SMTP settings).'),
                'reason' => $reason,
                // The SMTP conversation (server replies only — CodeIgniter doesn't log the credentials).
                'debug'  => trim(html_entity_decode(strip_tags(str_replace(['<br>', '</pre>'], "\n", $debug)), ENT_QUOTES)),
            ];
        }

        return ['ok' => true];
    }

    /**
     * A fresh email instance configured from Notify.*. Every outgoing
     * email in the app (reports, invoices, statements, VIN checks) is
     * built here so they all behave identically.
     */
    public static function newEmail(): CheckaEmail
    {
        $port   = (int) (setting('Notify.smtpPort') ?: 587);
        $crypto = (string) setting('Notify.smtpCrypto');
        // Port 465 is already encrypted from the first byte; adding STARTTLS
        // ("TLS") on top of it breaks the handshake. 587 is the other way round.
        if ($port === 465) {
            $crypto = 'ssl';
        } elseif ($port === 587 && $crypto === 'ssl') {
            $crypto = 'tls';
        }

        $email = new CheckaEmail(config(\Config\Email::class));
        $email->initialize([
            'protocol'    => (string) (setting('Notify.protocol') ?: 'mail'),
            'mailType'    => 'html',
            'charset'     => 'UTF-8',
            'wordWrap'    => true,
            'SMTPHost'    => (string) setting('Notify.smtpHost'),
            'SMTPUser'    => (string) setting('Notify.smtpUser'),
            'SMTPPass'    => (string) setting('Notify.smtpPass'),
            'SMTPPort'    => $port,
            'SMTPCrypto'  => $crypto,
            'SMTPTimeout' => 10,
        ]);

        return $email;
    }

    /**
     * Pull the useful part out of CodeIgniter's debug output — the SMTP
     * server's own error reply (e.g. "535 Authentication failed") or the
     * library's connection error — so the person sending sees why, instead
     * of a generic "check your settings".
     */
    public static function serverReason(string $debug): string
    {
        $text  = html_entity_decode(strip_tags(str_replace(['<br>', '<br />', '</pre>'], "\n", $debug)), ENT_QUOTES);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), static fn ($l) => $l !== ''));

        $picked = [];
        foreach ($lines as $l) {
            // SMTP replies with a 4xx/5xx code, or connection/auth failures from the library.
            if (preg_match('/\b[45]\d\d[\s-]/', $l) || preg_match('/unable to|failed|could not|refused|timed out|authenticat|certificate|ssl|tls/i', $l)) {
                $picked[] = $l;
            }
        }
        $picked = array_values(array_unique($picked));

        return mb_substr(implode(' | ', array_slice($picked, -3)), 0, 400);
    }

    /**
     * Branded HTML email wrapper.
     *
     * @param list<array{label:string,url:string}> $buttons
     */
    public static function layout(string $heading, string $bodyHtml, array $buttons = [], string $footerHtml = '', ?string $logoUrl = null): string
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
            . '<div style="text-align:center;border-bottom:3px solid ' . $primary . ';padding:18px"><img src="' . esc($logoUrl ?? brand('logo')) . '" alt="' . esc(billing('companyName')) . '" style="height:46px"></div>'
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
