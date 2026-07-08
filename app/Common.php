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
