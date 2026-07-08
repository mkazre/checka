<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Online payment settings (feature: payments). Currently Paystack (South
 * Africa). Dormant until keys are entered on the Settings page.
 */
class Pay extends BaseConfig
{
    public string $provider       = 'paystack';
    public string $currency       = 'ZAR';
    public string $paystackPublic = '';
    public string $paystackSecret = '';
    /** Flat inspection fee to charge when there is no approved repair total (0 = off). */
    public float $inspectionFee   = 0;
}
