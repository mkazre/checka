<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Master feature switches. Every flag is read through the global feature()
 * helper (feature('vinDecode')) and can be toggled per-feature on the Settings
 * page. Values here are the defaults; the admin's choices persist in the
 * `settings` table via setting('Features.<key>'). All default OFF so nothing
 * activates until it's explicitly switched on.
 */
class Features extends BaseConfig
{
    /** 1 · Offline-first capture (installable PWA + sync queue). */
    public bool $offline = false;

    /** 2 · VIN auto-decode (free NHTSA vPIC). */
    public bool $vinDecode = false;

    /** 3 · Client approval & e-signature portal. */
    public bool $clientPortal = false;

    /** 4 · Repair quote / estimate builder. */
    public bool $quotes = false;

    /** 5 · Automated report delivery. */
    public bool $notifications = false;
    public bool $notifyEmail    = false;
    public bool $notifyWhatsapp = false;

    /** 6 · Re-inspection history & wear trends. */
    public bool $history = false;

    /** 7 · AI report intelligence (beyond field auto-fill). */
    public bool $aiIntelligence  = false;
    public bool $aiConclusion    = false;
    public bool $aiPhotoAnalysis = false;

    /** 8 · Photo annotation / markup. */
    public bool $annotation = false;

    /** 9 · Analytics dashboard. */
    public bool $analytics = false;

    /** 10 · White-label branding & payments. */
    public bool $whiteLabel = false;
    public bool $payments   = false;
}
