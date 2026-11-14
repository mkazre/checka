<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

/**
 * Expects all namespaces to be PSR-4 compliant.
 */
class Autoload extends AutoloadConfig
{
    /**
     * Map of namespaces to their directories.
     *
     * @var array<string, list<string>|string>
     */
    public $psr4 = [
        APP_NAMESPACE => APPPATH, // For custom app namespace
        'Config'      => APPPATH . 'Config',
        'Platform'    => APPPATH . 'Platform', // SaaS control panel (app.<root domain>)
    ];

    /**
     * Map of class names and locations.
     *
     * @var array<string, string>
     */
    public $classmap = [];

    /**
     * List of files to always load.
     *
     * @var list<string>
     */
    public $files = [];

    /**
     * List of helpers to always load.
     *
     * @var list<string>
     */
    public $helpers = ['auth', 'setting'];
}
