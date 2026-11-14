<?php

namespace Platform\Services;

use App\Tenancy\Tenant;
use Platform\Models\EventModel;
use Platform\Models\TenantModel;

/**
 * Permanently deletes a workspace's data after the retention period.
 * Safety: only workspaces whose tables were created by the platform
 * (prefix "w<id>_") are dropped automatically; attached/legacy data is
 * never dropped by the robot — it's flagged for you instead.
 */
class Purge
{
    public static function canAutoPurge(array $t): bool
    {
        return ($t['db_mode'] ?: 'prefix') === 'prefix' && $t['db_prefix'] === 'w' . (int) $t['id'] . '_';
    }

    public static function tenant(array $t): bool
    {
        if (! self::canAutoPurge($t)) {
            EventModel::log((int) $t['id'], 'purge_skipped', 'Retention period over, but this workspace uses attached tables — delete its data manually if you want to.', 'system');

            return false;
        }
        $db     = db_connect('platform');
        $prefix = $t['db_prefix'];
        $tables = $db->DBDriver === 'SQLite3'
            ? array_column($db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE ?", [$prefix . '%'])->getResultArray(), 'name')
            : array_map(static fn ($r) => array_values($r)[0], $db->query('SHOW TABLES LIKE ' . $db->escape(str_replace('_', '\\_', $prefix) . '%'))->getResultArray());

        if ($db->DBDriver !== 'SQLite3') {
            $db->query('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($tables as $name) {
            if (str_starts_with($name, $prefix)) {
                $db->query('DROP TABLE IF EXISTS `' . str_replace('`', '', $name) . '`');
            }
        }
        if ($db->DBDriver !== 'SQLite3') {
            $db->query('SET FOREIGN_KEY_CHECKS=1');
        }

        foreach ([Tenant::baseWritable() . '/tenants/' . $t['slug'], FCPATH . 'tenant-files/' . $t['slug']] as $dir) {
            self::rmdir($dir);
        }
        TenantRegistry::remove($t['slug']);
        model(TenantModel::class)->update($t['id'], ['status' => 'cancelled', 'provisioned_at' => null, 'paystack_auth' => null]);
        model(TenantModel::class)->delete($t['id']);
        EventModel::log((int) $t['id'], 'purged', 'All data deleted after the retention period (' . count($tables) . ' tables).', 'system');

        return true;
    }

    private static function rmdir(string $dir): void
    {
        if ($dir === '' || ! is_dir($dir) || strlen($dir) < 10) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
