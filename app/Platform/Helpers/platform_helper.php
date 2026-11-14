<?php

use App\Tenancy\Tenant;
use Platform\Models\TenantModel;
use Platform\Services\PlatformSettings;

/* Small view helpers for the platform admin panel. */

if (! function_exists('pf_money')) {
    function pf_money($amount): string
    {
        $cur = PlatformSettings::value('general', 'currency', 'ZAR');
        $sym = ['ZAR' => 'R', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'][$cur] ?? $cur;

        return $sym . ' ' . number_format((float) $amount, 2, '.', ' ');
    }
}

if (! function_exists('pf_root')) {
    function pf_root(): string
    {
        return (string) (Tenant::platformConfig()['rootDomain'] ?? PlatformSettings::value('general', 'rootDomain', 'checkaplus.co.za'));
    }
}

if (! function_exists('pf_workspace_url')) {
    /** Public address of a workspace, keeping this site's scheme and port (so it also works locally). */
    function pf_workspace_url(string $slug, string $path = ''): string
    {
        $base   = config('App')->baseURL;
        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $port   = parse_url($base, PHP_URL_PORT);

        return $scheme . '://' . $slug . '.' . pf_root() . ($port ? ':' . $port : '') . '/' . ltrim($path, '/');
    }
}

if (! function_exists('pf_status')) {
    function pf_status(string $status): string
    {
        $label = TenantModel::STATUSES[$status] ?? ucfirst($status);

        return '<span class="badge s-' . esc($status, 'attr') . '">' . esc($label) . '</span>';
    }
}

if (! function_exists('pf_date')) {
    function pf_date(?string $d, bool $time = false): string
    {
        return $d ? date($time ? 'd M Y H:i' : 'd M Y', strtotime($d)) : '—';
    }
}

if (! function_exists('pf_admin')) {
    /** The signed-in platform admin row (cached per request). */
    function pf_admin(): ?array
    {
        static $a = false;
        if ($a === false) {
            $id = session('pf_admin_id');
            $a  = $id ? model(\Platform\Models\AdminModel::class)->find($id) : null;
        }

        return $a ?: null;
    }
}
