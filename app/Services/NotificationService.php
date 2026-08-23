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
    public function sendReport(array $report, ?string $toEmail, ?string $toPhone, bool $attachPdf = false): array
    {
        $ins     = $report['inspection'];
        $v       = $report['vehicle'] ?? [];
        $uid     = $ins['report_uid'];
        $vehicle = trim(($v['year'] ?? '') . ' ' . ($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) ?: 'your vehicle';
        $link    = feature('clientPortal') ? site_url('portal/' . $uid) : ($report['verifyUrl'] ?? site_url('verify/' . $uid));
        $out     = [];

        if (feature('notifyEmail') && $toEmail) {
            $out['email'] = $this->sendEmail($toEmail, $vehicle, $link, $ins, false, $attachPdf ? $report : null);
        }
        if (feature('notifyWhatsapp') && $toPhone) {
            $out['whatsapp'] = $this->sendWhatsApp($toPhone, $vehicle, $link);
        }

        // Optionally copy the workshop by email.
        if (feature('notifyEmail') && setting('Notify.notifyWorkshop') && trim((string) setting('Notify.workshopEmail')) !== '') {
            $this->sendEmail((string) setting('Notify.workshopEmail'), $vehicle, $link, $ins, true, $attachPdf ? $report : null);
        }

        return $out;
    }

    /**
     * @param array<string,mixed>|null $pdfReport when given, the full inspection PDF is attached
     *
     * @return array{ok:bool,error?:string}
     */
    private function sendEmail(string $to, string $vehicle, string $link, array $ins, bool $workshopCopy = false, ?array $pdfReport = null): array
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

        $tmpPdfPath = null;
        $attached   = false;
        if ($pdfReport !== null) {
            try {
                $pdf = service('reportPdf')->build($pdfReport);
                // Same base dir PdfService already writes mPDF's own temp
                // files into (writable/cache), so we know it's writable —
                // writable/uploads/tmp was NOT reliably writable on this host.
                $dir = WRITEPATH . 'cache/mail_attach';
                if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
                    throw new \RuntimeException("Could not create {$dir}");
                }
                $tmpPdfPath = $dir . '/' . bin2hex(random_bytes(8)) . '.pdf';
                if (@file_put_contents($tmpPdfPath, $pdf['bytes']) === false) {
                    throw new \RuntimeException("Could not write {$tmpPdfPath}");
                }
                if (filesize($tmpPdfPath) !== strlen($pdf['bytes'])) {
                    throw new \RuntimeException("Short write to {$tmpPdfPath}");
                }
                // Let CI4 detect the mime type from the file itself (the
                // path ends in .pdf) rather than forcing one — passing an
                // explicit mime here previously produced a corrupt (~1KB)
                // attachment on this host.
                if ($email->attach($tmpPdfPath, '', $pdf['filename']) === false) {
                    throw new \RuntimeException('Email::attach() returned false for ' . $tmpPdfPath);
                }
                $attached = true;
                // Logged at 'error' level (not a real error) purely so it
                // survives this app's threshold=4 log config for diagnosis.
                log_message('error', 'DIAG: Report PDF attached OK: ' . $tmpPdfPath . ' (' . filesize($tmpPdfPath) . ' bytes)');
            } catch (\Throwable $e) {
                log_message('error', 'Report PDF attachment failed: ' . $e->getMessage());
                if ($tmpPdfPath !== null && is_file($tmpPdfPath)) {
                    @unlink($tmpPdfPath);
                }
                $tmpPdfPath = null;
            }
        }

        $email->setMessage($this->emailHtml($vehicle, $link, (string) $score, (string) $verdict, $attached));

        $sent = $email->send(false);

        if ($tmpPdfPath !== null && is_file($tmpPdfPath)) {
            @unlink($tmpPdfPath);
        }

        if ($sent) {
            return ['ok' => true];
        }

        log_message('error', 'Report email failed: ' . $email->printDebugger(['headers']));

        return ['ok' => false, 'error' => 'Email could not be sent (check SMTP/from settings).'];
    }

    private function emailHtml(string $vehicle, string $link, string $score, string $verdict, bool $pdfAttached = false): string
    {
        $logo = base_url('assets/img/logo.png');

        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:auto;color:#2d2d2d">'
            . '<div style="text-align:center;border-bottom:3px solid #37b34a;padding:16px"><img src="' . esc($logo) . '" alt="CheckA+" style="height:46px"></div>'
            . '<div style="padding:22px 20px">'
            . '<h2 style="margin:0 0 6px">Your inspection report is ready</h2>'
            . '<p style="color:#555;line-height:1.6">The inspection for <b>' . esc($vehicle) . '</b> has been completed'
            . ($verdict ? ' with a verdict of <b>' . esc($verdict) . '</b>' : '')
            . ($score ? ' and a condition score of <b>' . esc($score) . '/100</b>' : '') . '.'
            . ($pdfAttached ? ' The full PDF report is attached to this email.' : '') . '</p>'
            . '<p style="text-align:center;margin:26px 0">'
            . '<a href="' . esc($link) . '" style="background:#37b34a;color:#fff;text-decoration:none;padding:13px 26px;border-radius:9px;font-weight:bold;display:inline-block">View &amp; approve your report</a></p>'
            . '<p style="color:#888;font-size:12px;line-height:1.5">Or open this link:<br>' . esc($link) . '</p>'
            . '</div>'
            . '<div style="text-align:center;color:#aaa;font-size:11px;padding:14px">Powered by CheckA+ by WheelsTurn</div></div>';
    }

    public function pushConfigured(): bool
    {
        return trim((string) setting('Push.fcmServerKey')) !== '';
    }

    /**
     * Send a native push (FCM) to every device registered to a user.
     * Dormant until a server key is set. @return array{ok:bool,sent:int}
     */
    public function pushToUser(int $userId, string $title, string $body, array $data = []): array
    {
        if (! $this->pushConfigured()) {
            return ['ok' => false, 'sent' => 0];
        }
        $tokens = model(\App\Models\DeviceTokenModel::class)->tokensFor($userId);
        if (empty($tokens)) {
            return ['ok' => false, 'sent' => 0];
        }

        $sent = 0;
        try {
            $client = Services::curlrequest(['timeout' => 15, 'http_errors' => false, 'headers' => [
                'Authorization' => 'key=' . trim((string) setting('Push.fcmServerKey')),
                'Content-Type'  => 'application/json',
            ]]);
            foreach (array_chunk($tokens, 500) as $batch) {
                $res = $client->post('https://fcm.googleapis.com/fcm/send', ['json' => [
                    'registration_ids' => $batch,
                    'notification'     => ['title' => $title, 'body' => $body, 'sound' => 'default'],
                    'data'             => $data,
                ]]);
                if ($res->getStatusCode() < 300) {
                    $sent += count($batch);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'FCM push failed: ' . $e->getMessage());
        }

        return ['ok' => $sent > 0, 'sent' => $sent];
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
