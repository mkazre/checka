<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/** Purchase order numbering (feature: purchasing). Company details come from Config\Billing. */
class Purchasing extends BaseConfig
{
    public string $poPrefix     = 'PO-';
    public int    $nextPoNumber = 1001;
    public float  $defaultVatRate = 15.0;
    /** Logo used on purchase orders only (relative to public/). Empty = system logo. */
    public string $logoPath = '';

    /** Company details for purchase orders only. Blank = use Config\Billing's. */
    public string $companyName    = '';
    public string $companyAddress = '';
    public string $companyPhone   = '';
    public string $companyEmail   = '';
    public string $companyRegNo   = '';
    public string $companyVatNo   = '';
}
