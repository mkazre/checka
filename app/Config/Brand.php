<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * White-label branding (feature: whiteLabel). Defaults reproduce the stock
 * CheckA+ look; a reseller overrides them on the Settings page.
 */
class Brand extends BaseConfig
{
    public string $brandName    = 'CheckA+';
    public string $tagline      = 'by WheelsTurn';
    public string $primaryColor = '#37b34a';
    public string $darkColor    = '#2d2d2d';
    public string $logoPath     = ''; // relative to public/, e.g. assets/img/brand-logo.png
}
