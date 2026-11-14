<?php

namespace Platform\Services;

use App\Tenancy\Tenant;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Read (and lightly write) a workspace's own tables from the platform:
 * usage counts, its clients (for the cross-customer client list/export),
 * and switching modules on in its settings. Workspaces in the shared
 * database are reached by table prefix; ones with their own database get a
 * dedicated connection.
 */
class TenantData
{
    public static function conn(array $t): BaseConnection
    {
        if (($t['db_mode'] ?? 'prefix') === 'database') {
            $cfg = config(Database::class)->default;
            $db  = json_decode((string) ($t['db_config'] ?? '{}'), true) ?: [];
            $cfg = array_merge($cfg, array_intersect_key($db, array_flip(['hostname', 'database', 'username', 'password'])), ['DBPrefix' => '']);

            return Database::connect($cfg, false);
        }

        return db_connect('platform');
    }

    /** Fully-qualified, quoted table name for a tenant's table (raw SQL only). */
    public static function table(array $t, string $name): string
    {
        $prefix = (($t['db_mode'] ?? 'prefix') === 'database') ? '' : (string) $t['db_prefix'];

        return '`' . str_replace('`', '', $prefix . $name) . '`';
    }

    public static function tableExists(array $t, string $name): bool
    {
        $prefix = (($t['db_mode'] ?? 'prefix') === 'database') ? '' : (string) $t['db_prefix'];
        $conn   = self::conn($t);
        if ($conn->DBDriver === 'SQLite3') {
            return (bool) $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$prefix . $name])->getRowArray();
        }

        return (bool) $conn->query('SHOW TABLES LIKE ' . $conn->escape($prefix . $name))->getRowArray();
    }

    /** @return array{users:int,inspections_month:int,clients:int,inspections_total:int,storage_mb:float} */
    public static function usage(array $t, bool $withStorage = false): array
    {
        $out = ['users' => 0, 'inspections_month' => 0, 'inspections_total' => 0, 'clients' => 0, 'storage_mb' => 0.0];
        if (! $t['provisioned_at'] || ! self::tableExists($t, 'users')) {
            return $out;
        }
        $c   = self::conn($t);
        $one = static fn (string $sql, array $b = []) => (int) (array_values((array) $c->query($sql, $b)->getRowArray())[0] ?? 0);

        $out['users']             = $one('SELECT COUNT(*) FROM ' . self::table($t, 'users') . ' WHERE deleted_at IS NULL');
        $out['inspections_month'] = $one('SELECT COUNT(*) FROM ' . self::table($t, 'inspections') . ' WHERE created_at >= ?', [date('Y-m-01 00:00:00')]);
        $out['inspections_total'] = $one('SELECT COUNT(*) FROM ' . self::table($t, 'inspections') . ' WHERE deleted_at IS NULL');
        if (self::tableExists($t, 'clients')) {
            $out['clients'] = $one('SELECT COUNT(*) FROM ' . self::table($t, 'clients') . ' WHERE deleted_at IS NULL');
        }
        if ($withStorage) {
            $out['storage_mb'] = round(self::dirSize(Tenant::baseWritable() . '/tenants/' . $t['slug']) / 1048576, 1);
        }

        return $out;
    }

    /** @return list<array<string,mixed>> */
    public static function clients(array $t, string $q = '', int $limit = 5000): array
    {
        if (! $t['provisioned_at'] || ! self::tableExists($t, 'clients')) {
            return [];
        }
        $sql  = 'SELECT name, type, contact_person, email, billing_email, phone, address, vat_number, registration_number, created_at FROM '
            . self::table($t, 'clients') . ' WHERE deleted_at IS NULL';
        $bind = [];
        if ($q !== '') {
            $sql   .= ' AND (name LIKE ? OR email LIKE ? OR contact_person LIKE ? OR phone LIKE ?)';
            $like   = '%' . $q . '%';
            $bind   = [$like, $like, $like, $like];
        }
        $sql .= ' ORDER BY name LIMIT ' . (int) $limit;

        return self::conn($t)->query($sql, $bind)->getResultArray();
    }

    /** Write a value into the workspace's settings table (codeigniter4/settings format). */
    public static function setSetting(array $t, string $class, string $key, $value): void
    {
        $c    = self::conn($t);
        $tbl  = self::table($t, 'settings');
        $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
        $val  = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        $now  = date('Y-m-d H:i:s');
        $row  = $c->query("SELECT id FROM {$tbl} WHERE class = ? AND `key` = ? AND context IS NULL", [$class, $key])->getRowArray();
        $row
            ? $c->query("UPDATE {$tbl} SET value = ?, type = ?, updated_at = ? WHERE id = ?", [$val, $type, $now, $row['id']])
            : $c->query("INSERT INTO {$tbl} (class, `key`, value, type, context, created_at, updated_at) VALUES (?, ?, ?, ?, NULL, ?, ?)", [$class, $key, $val, $type, $now, $now]);
    }

    /** Switch on every Config\Features flag belonging to the given modules. */
    public static function enableModules(array $t, array $modules): void
    {
        if (! self::tableExists($t, 'settings')) {
            return;
        }
        $catalog = config('SaasModules')->modules;
        foreach ($modules as $m) {
            foreach ($catalog[$m]['flags'] ?? [] as $flag) {
                self::setSetting($t, 'Config\\Features', $flag, true);
            }
        }
    }

    private static function dirSize(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
            $size += $f->getSize();
        }

        return $size;
    }
}
