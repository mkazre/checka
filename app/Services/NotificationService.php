<?php

namespace App\Services;

use Config\Services;

/**
 * Delivers a completed report to the client by email and/or WhatsApp
 * (feature: notifications, with notifyEmail / notifyWhatsapp sub-flags).
 * All settings come from Config\Notify via setting('Notify.*').
 */
class NotificationService
{
    public function emailConfigured(): bool
    {
        return trim((string) setting('Notify.fromEmail')) !== '';
    }

    public function whatsappConfigured(): bool
    {
        return trim((string) setting('Notify.waToken')) !== '' && trim((string) setting('Notify.waPhoneId')) !== '';
    }

    /**
     * @param array<string,mixed> $report assembled report
     * @return array<string,mixed> per-channel result
     */
    public function sendReport(array $report, ?string $toEmail, ?string $toPhone): array
    {
        $ins     = $report['inspection'];
        $v       = $report['vehicle'] ?? [];
        $uid     = $ins['report_uid'];
        $vehicle = trim(($v['year'] ?? '') . ' ' . ($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) ?: 'your vehicle';
        $link    = feature('clientPortal') ? site_url('portal/' . $uid) : ($report['verifyUrl'] ?? site_url('verify/' . $uid));
        $out     = [];

        if (feature('notifyEmail') && $toEmail) {
            $out['email'] = $this->sendEmail($toEmail, $vehicle, $link, $ins);
        }
        if (feature('notifyWhatsapp') && $toPhone) {
            $out['whatsapp'] = $this->sendWhatsApp($toPhone, $vehicle, $link);
        }

        // Optionally copy the workshop by email.
        if (feature('notifyEmail') && setting('Notify.notifyWorkshop') && trim((string) setting('Notify.workshopEmail')) !== '') {
            $this->sendEmail((string) setting('Notify.workshopEmail'), $vehicle, $link, $ins, true);
        }

        return $out;
    }

    /** @return array{ok:bool,error?:string} */
    private function sendEmail(string $to, string $vehicle, string $link, array $ins, bool $workshopCopy = false): array
    {
        if (! $this->emailConfigured()) {
            return ['ok' => false, 'error' => 'Email sender not configured in Settings.'];
        }

        $email  = Services::email();
        $config = [
            'protocol'    => setting('Notify.protocol') ?: 'mail',
            'mailType'    => 'html',
            'charset'     => 'UTF-8',
            'wordWrap'    => true,
            'SMTPHost'    => (string) setting('Notify.smtpHost'),
            'SMTPUser'    => (string) setting('Notify.smtpUser'),
            'SMTPPass'    => (string) setting('Notify.smtpPass'),
            'SMTPPort'    => (int) (setting('Notify.smtpPort') ?: 587),
            'SMTPCrypto'  => (string) setting('Notify.smtpCrypto'),
        ];
        $email->initialize($config);
        $email->setFrom((string) setting('Notify.fromEmail'), (string) (setting('Notify.fromName') ?: 'CheckA+'));
        $email->setTo($to);

        $score   = $ins['score'] ?? '';
        $verdict = $ins['verdict_text'] ?? '';
        $subject = ($workshopCopy ? '[Copy] ' : '') . 'Vehicle inspection report — ' . $vehicle;
        $email->setSubject($subject);
        $email->setMessage($this->emailHtml($vehicle, $link, (string) $score, (string) $verdict));

        if ($email->send(false)) {
            return ['ok' => true];
        }

        log_message('error', 'Report email failed: ' . $email->printDebugger(['headers']));

        return ['ok' => false, 'error' => 'Email could not be sent (check SMTP/from settings).'];
    }

    private function emailHtml(string $vehicle, string $link, string $score, string $verdict): string
    {
        $logo = base_url('assets/img/logo.png');

        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:auto;color:#2d2d2d">'
            . '<div style="text-align:center;border-bottom:3px solid #37b34a;padding:16px"><img src="' . esc($logo) . '" alt="CheckA+" style="height:46px"></div>'
            . '<div style="padding:22px 20px">'
            . '<h2 style="margin:0 0 6px">Your inspection report is ready</h2>'
            . '<p style="color:#555;line-height:1.6">The inspection for <b>' . esc($vehicle) . '</b> has been completed'
            . ($verdict ? ' with a verdict of <b>' . esc($verdict) . '</b>' : '')
            . ($score ? ' and a condition score of <b>' . esc($score) . '/100</b>' : '') . '.</p>'
            . '<p style="text-align:center;margin:26px 0">'
            . '<a href="' . esc($link) . '" style="background:#37b34a;color:#fff;text-decoration:none;padding:13px 26px;border-radius:9px;font-weight:bold;display:inline-block">View &amp; approve your report</a></p>'
            . '<p style="color:#888;font-size:12px;line-height:1.5">Or open this link:<br>' . esc($link) . '</p>'
            . '</div>'
            . '<div style="text-align:center;color:#aaa;font-size:11px;padding:14px">Powered by CheckA+ by WheelsTurn</div></div>';
    }

    /** @return array{ok:bool,error?:string} */
    private function sendWhatsApp(string $to, string $vehicle, string $link): array
    {
        if (! $this->whatsappConfigured()) {
            return ['ok' => false, 'error' => 'WhatsApp not configured yet.'];
        }

        // Normalise to digits (WhatsApp wants full international number, no +).
        $to = ltrim(preg_replace('/\D+/', '', $to), '0');
        if ($to === '') {
            return ['ok' => false, 'error' => 'Invalid phone number.'];
        }

        $text = "Your CheckA+ vehicle inspection report for {$vehicle} is ready. View & approve here: {$link}";

        try {
            $res = Services::curlrequest([
                'timeout'     => 20,
                'http_errors' => false,
                'headers'     => [
                    'Authorization' => 'Bearer ' . setting('Notify.waToken'),
                    'Content-Type'  => 'application/json',
                ],
            ])->post('https://graph.facebook.com/v20.0/' . rawurlencode((string) setting('Notify.waPhoneId')) . '/messages', [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['preview_url' => true, 'body' => $text],
                ],
            ]);

            $data = json_decode((string) $res->getBody(), true);
            if ($res->getStatusCode() < 300 && ! isset($data['error'])) {
                return ['ok' => true];
            }

            return ['ok' => false, 'error' => $data['error']['message'] ?? 'WhatsApp send failed.'];
        } catch (\Throwable $e) {
            log_message('error', 'WhatsApp send failed: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'WhatsApp service unavailable.'];
        }
    }
}
