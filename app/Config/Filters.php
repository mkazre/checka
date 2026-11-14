<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use CodeIgniter\Shield\Filters\AuthRates;
use CodeIgniter\Shield\Filters\ChainAuth;
use CodeIgniter\Shield\Filters\ForcePasswordResetFilter;
use CodeIgniter\Shield\Filters\GroupFilter;
use CodeIgniter\Shield\Filters\PermissionFilter;
use CodeIgniter\Shield\Filters\SessionAuth;
use CodeIgniter\Shield\Filters\TokenAuth;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes.
     *
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,

        // Shield authentication filters
        'session'     => SessionAuth::class,
        'tokens'      => TokenAuth::class,
        'chain'       => ChainAuth::class,
        'auth-rates'  => AuthRates::class,
        'group'       => GroupFilter::class,
        'permission'  => PermissionFilter::class,
        'force-reset' => ForcePasswordResetFilter::class,

        // SaaS: workspace status + package entitlements (no-op outside SaaS mode)
        'tenant'      => \App\Filters\TenantGuard::class,
        'platform-auth' => \Platform\Filters\PlatformAuth::class,
    ];

    /**
     * Filters that are run at the framework level.
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps',
            'pagecache',
        ],
        'after' => [
            'pagecache',
            'performance',
            'toolbar',
        ],
    ];

    /**
     * List of filter aliases that are always applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            'tenant',
            'csrf' => ['except' => ['api/*', 'signup', 'find-workspace', 'webhooks/*', 'pay/*/return/*']],
            // 'invalidchars',
        ],
        'after' => [
            // 'honeypot',
            'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a particular HTTP method.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any before or after URIs.
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [];
}
