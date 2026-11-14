<?php

namespace App\Tenancy;

/**
 * Which customer (tenant) this request or CLI run belongs to.
 *
 * Runs BEFORE the framework boots (required directly from public/index.php
 * and spark), so it only uses plain PHP. It reads two kinds of JSON files
 * written by the platform admin:
 *
 *   writable/platform/platform.json        rootDomain, platform hosts
 *   writable/platform/tenants/<slug>.json  one per tenant: status, db, modules, limits
 *
 * No platform.json = single-company mode: nothing changes, so existing
 * installs keep working exactly as before.
 *
 * Each tenant gets its own writable folder (uploads, sessions, cache, logs)
 * and its own database space: a table prefix in one shared database
 * (cPanel-friendly), or a separate database.
 */
final class Tenant
{
    private static ?array $platform = null;
    private static ?array $tenant   = null;
    private static bool $isPlatformHost = false;
    private static string $baseWritable = '';

    /** Resolve the tenant and point $paths->writableDirectory at its folder. Returns false if the request was answered (e.g. unknown workspace). */
    public static function bootstrap(object $paths): bool
    {
        self::$baseWritable = rtrim(realpath($paths->writableDirectory) ?: $paths->writableDirectory, '\\/');
        $pf = self::readJson(self::$baseWritable . '/platform/platform.json');
        if ($pf === null) {
            return true; // single-company mode
        }
        self::$platform = $pf;

        if (PHP_SAPI === 'cli') {
            $slug = (string) (getenv('CHECKA_TENANT') ?: '');
            if ($slug === '') {
                self::$isPlatformHost = true;

                return true;
            }
        } else {
            $slug = self::slugFromHost((string) ($_SERVER['HTTP_HOST'] ?? ''));
            if ($slug === null) {
                self::$isPlatformHost = true;

                return true;
            }
        }

        $t = preg_match('/^[a-z0-9][a-z0-9-]{1,40}$/', $slug) ? self::readJson(self::$baseWritable . '/platform/tenants/' . $slug . '.json') : null;
        if ($t === null) {
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, "Unknown tenant '{$slug}'.\n");
                exit(1);
            }
            self::notFound($slug);

            return false;
        }

        self::$tenant = $t;
        $dir          = self::$baseWritable . '/tenants/' . $t['slug'];
        foreach (['', '/cache', '/logs', '/session', '/uploads', '/debugbar'] as $sub) {
            if (! is_dir($dir . $sub)) {
                @mkdir($dir . $sub, 0755, true);
            }
        }
        $paths->writableDirectory = $dir;

        return true;
    }

    /** "acme" from acme.checkaplus.co.za; null for the root, www, app, or a foreign host. */
    public static function slugFromHost(string $host): ?string
    {
        $host = strtolower(preg_replace('/:\d+$/', '', $host));
        $root = strtolower((string) (self::$platform['rootDomain'] ?? ''));
        if ($root === '' || $host === $root || ! str_ends_with($host, '.' . $root)) {
            return null;
        }
        $sub = substr($host, 0, -strlen('.' . $root));
        if (str_contains($sub, '.') || in_array($sub, (array) (self::$platform['platformHosts'] ?? ['www', 'app']), true)) {
            return null;
        }

        return $sub;
    }

    public static function multiTenant(): bool
    {
        return self::$platform !== null;
    }

    public static function isPlatform(): bool
    {
        return self::$platform !== null && self::$isPlatformHost;
    }

    public static function active(): bool
    {
        return self::$tenant !== null;
    }

    public static function get(): ?array
    {
        return self::$tenant;
    }

    public static function slug(): ?string
    {
        return self::$tenant['slug'] ?? null;
    }

    public static function status(): string
    {
        return (string) (self::$tenant['status'] ?? 'active');
    }

    /** Modules this tenant's package includes (catalogue keys). */
    public static function modules(): array
    {
        return (array) (self::$tenant['modules'] ?? []);
    }

    /** null = unlimited / not set. */
    public static function limit(string $key): ?int
    {
        $v = self::$tenant['limits'][$key] ?? null;

        return $v === null || $v === '' ? null : (int) $v;
    }

    public static function db(): array
    {
        return (array) (self::$tenant['db'] ?? []);
    }

    public static function platformConfig(): array
    {
        return self::$platform ?? [];
    }

    public static function baseWritable(): string
    {
        return self::$baseWritable;
    }

    /** Re-read the tenant file (after the platform changed it in this same request/run). */
    public static function reload(): void
    {
        if (self::$tenant !== null) {
            self::$tenant = self::readJson(self::$baseWritable . '/platform/tenants/' . self::$tenant['slug'] . '.json') ?? self::$tenant;
        }
    }

    private static function readJson(string $file): ?array
    {
        if (! is_file($file)) {
            return null;
        }
        $d = json_decode((string) file_get_contents($file), true);

        return is_array($d) ? $d : null;
    }

    private static function notFound(string $slug): void
    {
        $root = htmlspecialchars((string) (self::$platform['rootDomain'] ?? ''), ENT_QUOTES);
        http_response_code(404);
        echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Workspace not found</title>'
            . '<div style="font-family:system-ui,sans-serif;max-width:460px;margin:12vh auto;padding:0 20px;text-align:center;color:#2d2d2d">'
            . '<h1 style="font-size:22px">Workspace not found</h1>'
            . '<p style="color:#6b7682">There is no workspace at <b>' . htmlspecialchars($slug, ENT_QUOTES) . '.' . $root . '</b>.</p>'
            . '<p><a href="https://' . $root . '" style="color:#37b34a;font-weight:600">Go to ' . $root . '</a></p></div>';
    }
}
