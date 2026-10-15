<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Packs ONE inspection — its vehicle, every captured value, photos/videos,
 * thumbnails and signature — into a folder that checka:import-inspection
 * can load on another Checka install. Uses raw table access only (no
 * models), so it also runs on older installs.
 *
 *   php spark checka:export-inspection JSM682K          (by registration — latest inspection)
 *   php spark checka:export-inspection 6d1f0c2a9b8e7f11 (by report id from the report URL)
 *
 * Output: writable/exports/inspection-<report_uid>/  (bundle.json + files/)
 */
class CheckaExportInspection extends BaseCommand
{
    protected $group       = 'Checka';
    protected $name        = 'checka:export-inspection';
    protected $description = 'Export one inspection (with vehicle + photos) to a zip for import elsewhere.';
    protected $usage       = 'checka:export-inspection <report_uid | registration>';

    public function run(array $params): int
    {
        $key = trim((string) ($params[0] ?? ''));
        if ($key === '') {
            CLI::error('Give a report id or a vehicle registration.');

            return EXIT_ERROR;
        }

        $db  = db_connect();
        $ins = $db->table('inspections')->where('report_uid', $key)->where('deleted_at', null)->get()->getRowArray();
        if ($ins === null) {
            $plate = strtoupper(preg_replace('/[\s-]+/', '', $key));
            $veh   = $db->query(
                "SELECT id FROM vehicles WHERE deleted_at IS NULL AND UPPER(REPLACE(REPLACE(plate,' ',''),'-','')) = ? ORDER BY id DESC",
                [$plate],
            )->getResultArray();
            if ($veh !== []) {
                $ins = $db->table('inspections')->whereIn('vehicle_id', array_column($veh, 'id'))
                    ->where('deleted_at', null)->orderBy('inspection_date', 'DESC')->orderBy('id', 'DESC')
                    ->get()->getRowArray();
            }
        }
        if ($ins === null) {
            CLI::error('No inspection found for "' . $key . '".');

            return EXIT_ERROR;
        }

        $id      = (int) $ins['id'];
        $vehicle = $ins['vehicle_id'] ? $db->table('vehicles')->where('id', $ins['vehicle_id'])->get()->getRowArray() : null;
        $values  = $db->table('inspection_values')->where('inspection_id', $id)->orderBy('id')->get()->getResultArray();
        $photos  = $db->table('inspection_photos')->where('inspection_id', $id)->orderBy('id')->get()->getResultArray();
        $tpl     = $ins['template_id'] ? $db->table('templates')->select('id, name')->where('id', $ins['template_id'])->get()->getRowArray() : null;

        $emailOf = static function ($userId) use ($db): ?string {
            if (! $userId || ! $db->tableExists('auth_identities')) {
                return null;
            }
            $row = $db->table('auth_identities')->where('user_id', $userId)->where('type', 'email_password')->get()->getRowArray();

            return $row['secret'] ?? null;
        };

        // Every file the inspection references, relative to writable/uploads/.
        $files = [];
        foreach ($photos as $p) {
            foreach (['path', 'thumb_path'] as $col) {
                if (! empty($p[$col])) {
                    $files[] = ltrim((string) $p[$col], '/');
                }
            }
        }
        if (! empty($ins['signature_path'])) {
            $files[] = ltrim((string) $ins['signature_path'], '/');
        }
        $files = array_values(array_unique($files));

        // A plain folder (zip/unzip it with cPanel File Manager) — no PHP zip extension needed.
        $out = WRITEPATH . 'exports/inspection-' . $ins['report_uid'];
        if (is_dir($out)) {
            CLI::error('Export folder already exists: ' . $out . ' — delete it first.');

            return EXIT_ERROR;
        }
        mkdir($out . '/files', 0755, true);

        $missing = [];
        foreach ($files as $rel) {
            $full = WRITEPATH . 'uploads/' . $rel;
            if (! is_file($full)) {
                $missing[] = $rel;

                continue;
            }
            $dest = $out . '/files/' . $rel;
            if (! is_dir(dirname($dest))) {
                mkdir(dirname($dest), 0755, true);
            }
            if (! copy($full, $dest)) {
                CLI::error('Could not copy ' . $full);

                return EXIT_ERROR;
            }
            @chmod($dest, 0644);
        }

        file_put_contents($out . '/bundle.json', json_encode([
            'format'      => 'checka-inspection',
            'version'     => 1,
            'exported_at' => date('c'),
            'source'      => base_url(),
            'inspection'  => $ins,
            'vehicle'     => $vehicle,
            'template'    => $tpl,
            'users'       => ['mechanic' => $emailOf($ins['mechanic_id'] ?? null), 'created_by' => $emailOf($ins['created_by'] ?? null)],
            'values'      => $values,
            'photos'      => $photos,
            'missing'     => $missing,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        @chmod($out . '/bundle.json', 0644);

        $label = $vehicle ? trim(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '') . ' ' . ($vehicle['plate'] ?? '')) : 'no vehicle';
        CLI::write('Exported inspection ' . $ins['report_uid'] . ' (' . $label . ', ' . $ins['inspection_date'] . ')', 'green');
        CLI::write('  values: ' . count($values) . ', photo rows: ' . count($photos) . ', files: ' . (count($files) - count($missing)));
        if ($missing !== []) {
            CLI::write('  files missing on disk (skipped): ' . implode(', ', $missing), 'yellow');
        }
        CLI::write('  folder: ' . $out);

        return EXIT_SUCCESS;
    }
}
