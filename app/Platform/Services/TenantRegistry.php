<?php

namespace Platform\Services;

use App\Tenancy\Tenant;
use Platform\Models\PackageModel;
use Platform\Models\TenantModel;

/**
 * Writes the JSON files the app reads before boot (see App\Tenancy\Tenant):
 * writable/platform/platform.json and writable/platform/tenants/<slug>.json.
 * Called whenever a workspace, its package or the root domain changes, so
 * the file is always the effective, ready-to-use view: status, database,
 * modules (package + extras) and limits (package, then overrides).
 */
class TenantRegistry
{
    public static function dir(): string
    {
        $base = Tenant::baseWritable() ?: rtrim(WRITEPATH, '\\/');

        return $base . '/platform';
    }

    public static function writePlatform(): void
    {
        $g = PlatformSettings::get('general');
        self::put(self::dir() . '/platform.json', [
            'rootDomain'    => strtolower(trim((string) $g['rootDomain'])),
            'platformHosts' => ['www', 'app'],
        ]);
    }

    /** Effective modules & limits for a tenant row. @return array{modules:list<string>,limits:array<string,int|null>} */
    public static function effective(array $t): array
    {
        $pkg     = $t['package_id'] ? model(PackageModel::class)->find($t['package_id']) : null;
        $modules = $pkg ? PackageModel::modules($pkg) : [];
        $modules = array_values(array_unique(array_merge($modules, (array) json_decode((string) ($t['extra_modules'] ?? '[]'), true))));
        $limits  = $pkg ? PackageModel::limits($pkg) : [];
        foreach ((array) json_decode((string) ($t['limit_overrides'] ?? '{}'), true) as $k => $v) {
            $limits[$k] = $v === '' ? null : $v;
        }
        foreach ($limits as $k => $v) {
            $limits[$k] = ($v === null || $v === '') ? null : (int) $v;
        }

        return ['modules' => $modules, 'limits' => $limits];
    }

    public static function write(array $t): void
    {
        $eff = self::effective($t);
        $pkg = $t['package_id'] ? model(PackageModel::class)->find($t['package_id']) : null;
        self::put(self::dir() . '/tenants/' . $t['slug'] . '.json', [
            'id'      => (int) $t['id'],
            'slug'    => $t['slug'],
            'name'    => $t['company_name'],
            'status'  => $t['status'],
            'plan'    => $pkg['name'] ?? null,
            'db'      => ['mode' => $t['db_mode'] ?: 'prefix', 'prefix' => (string) $t['db_prefix']],
            'modules' => $eff['modules'],
            'limits'  => $eff['limits'],
            'trialEndsAt' => $t['trial_ends_at'],
        ]);
    }

    public static function writeAll(): int
    {
        $n = 0;
        foreach (model(TenantModel::class)->findAll() as $t) {
            self::write($t);
            $n++;
        }

        return $n;
    }

    public static function writeForPackage(int $packageId): void
    {
        foreach (model(TenantModel::class)->where('package_id', $packageId)->findAll() as $t) {
            self::write($t);
        }
    }

    public static function remove(string $slug): void
    {
        @unlink(self::dir() . '/tenants/' . $slug . '.json');
    }

    private static function put(string $file, array $data): void
    {
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        @chmod($tmp, 0644);
        rename($tmp, $file); // atomic: a request never reads a half-written file
    }
}
