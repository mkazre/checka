<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Delivery settings for the notifications feature (email + WhatsApp). Defaults
 * here; the admin overrides them on the Settings page, persisted via
 * setting('Notify.<key>').
 */
class Notify extends BaseConfig
{
    // --- Email ---
    public string $fromEmail  = '';
    public string $fromName   = 'CheckA+ by WheelsTurn';
    public string $protocol   = 'mail';   // 'mail' (cPanel sendmail) or 'smtp'
    public string $smtpHost   = '';
    public string $smtpUser   = '';
    public string $smtpPass   = '';
    public int    $smtpPort   = 587;
    public string $smtpCrypto = 'tls';    // 'tls', 'ssl' or ''

    // --- WhatsApp Cloud API (Meta) — dormant until token + phone id are set ---
    public string $waToken   = '';        // permanent access token
    public string $waPhoneId = '';        // phone number ID

    // Also copy the workshop on every send?
    public bool   $notifyWorkshop = false;
    public string $workshopEmail  = '';
}
