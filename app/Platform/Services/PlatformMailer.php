<?php

namespace Platform\Services;

use Throwable;

/**
 * Sends the platform's own emails (welcome, invoices, reminders…) using the
 * transport chosen under Settings → Email: the server's mail(), SMTP, or the
 * Brevo HTTP API (works even where outbound SMTP is blocked).
 */
class PlatformMailer
{
    public static string $lastError = '';

    /** Send one of the editable templates (Settings → Email templates). */
    public static function template(string $key, string $to, array $vars): bool
    {
        $tpl  = PlatformSettings::get('templates')[$key] ?? null;
        if (! $tpl) {
            self::$lastError = 'Unknown template ' . $key;

            return false;
        }
        $vars += ['platform' => PlatformSettings::value('general', 'platformName', 'CheckA+')];

        return self::send($to, PlatformSettings::render($tpl['subject'], $vars), PlatformSettings::render($tpl['body'], $vars));
    }

    public static function send(string $to, string $subject, string $text): bool
    {
        self::$lastError = '';
        $cfg      = PlatformSettings::get('mail');
        $general  = PlatformSettings::get('general');
        $from     = $cfg['fromEmail'] ?: ($general['supportEmail'] ?: 'no-reply@' . $general['rootDomain']);
        $fromName = $cfg['fromName'] ?: $general['platformName'];
        $html     = self::html($text, $general['platformName']);

        try {
            if ($cfg['transport'] === 'brevo') {
                $res = \Config\Services::curlrequest(['http_errors' => false, 'timeout' => 20], null, null, false)->post('https://api.brevo.com/v3/smtp/email', [
                    'headers' => ['api-key' => $cfg['brevoKey'], 'accept' => 'application/json'],
                    'json'    => [
                        'sender'      => ['email' => $from, 'name' => $fromName],
                        'to'          => [['email' => $to]],
                        'subject'     => $subject,
                        'htmlContent' => $html,
                        'textContent' => $text,
                    ],
                ]);
                if ($res->getStatusCode() >= 300) {
                    self::$lastError = 'Brevo: ' . $res->getStatusCode() . ' ' . mb_substr($res->getBody(), 0, 300);

                    return false;
                }

                return true;
            }

            $email = \Config\Services::email([
                'protocol'   => $cfg['transport'] === 'smtp' ? 'smtp' : 'mail',
                'SMTPHost'   => $cfg['smtpHost'],
                'SMTPUser'   => $cfg['smtpUser'],
                'SMTPPass'   => $cfg['smtpPass'],
                'SMTPPort'   => (int) $cfg['smtpPort'],
                'SMTPCrypto' => $cfg['smtpCrypto'],
                'mailType'   => 'html',
                'charset'    => 'UTF-8',
            ], false);
            $email->setFrom($from, $fromName)->setTo($to)->setSubject($subject)->setMessage($html)->setAltMessage($text);
            if (! $email->send(false)) {
                self::$lastError = mb_substr(trim(strtok(html_entity_decode(strip_tags($email->printDebugger([]))), "
")), 0, 200);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            self::$lastError = $e->getMessage();

            return false;
        }
    }

    private static function html(string $text, string $brand): string
    {
        $body = nl2br(preg_replace('~(https?://[^\s<]+)~', '<a href="$1" style="color:#0b7a5a">$1</a>', esc($text)));

        return '<!doctype html><html><body style="margin:0;background:#f4f6f8;font-family:Segoe UI,Roboto,Arial,sans-serif;color:#16202a">'
            . '<div style="max-width:560px;margin:0 auto;padding:28px 16px"><div style="font-weight:800;font-size:18px;margin-bottom:14px">' . esc($brand) . '</div>'
            . '<div style="background:#fff;border:1px solid #e3e7eb;border-radius:12px;padding:24px;font-size:15px;line-height:1.6">' . $body . '</div></div></body></html>';
    }
}
