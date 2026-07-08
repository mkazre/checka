<?php

/**
 * App-wide helper functions (auto-loaded by CodeIgniter).
 */

use Config\Features;

if (! function_exists('feature')) {
    /**
     * Is a feature switched on? Reads the persisted Settings value, falling
     * back to the Config\Features default. Usage: feature('vinDecode').
     */
    function feature(string $key): bool
    {
        static $config = null;
        $config ??= new Features();

        // Only accept known flags so a typo can't silently read "true".
        if (! property_exists($config, $key)) {
            return false;
        }

        return (bool) setting('Features.' . $key);
    }
}

if (! function_exists('brand')) {
    /**
     * Branding value with white-label overrides applied.
     * Keys: name, tagline, logo (absolute URL), primary, dark.
     */
    function brand(string $key): string
    {
        $defaults = [
            'name'    => 'CheckA+',
            'tagline' => 'by WheelsTurn',
            'logo'    => base_url('assets/img/logo.png'),
            'primary' => '#37b34a',
            'dark'    => '#2d2d2d',
        ];

        if (! feature('whiteLabel')) {
            return $defaults[$key] ?? '';
        }

        switch ($key) {
            case 'name':    return (string) (setting('Brand.brandName') ?: $defaults['name']);
            case 'tagline': return (string) (setting('Brand.tagline') ?? $defaults['tagline']);
            case 'primary': return (string) (setting('Brand.primaryColor') ?: $defaults['primary']);
            case 'dark':    return (string) (setting('Brand.darkColor') ?: $defaults['dark']);
            case 'logo':
                $p = trim((string) setting('Brand.logoPath'));
                return $p !== '' ? base_url($p) : $defaults['logo'];
            default: return $defaults[$key] ?? '';
        }
    }
}
