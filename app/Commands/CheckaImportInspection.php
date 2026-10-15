<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Loads a folder made by checka:export-inspection into this install.
 *
 *   php spark checka:import-inspection exports/inspection-<uid> --dry-run
 *   php spark checka:import-inspection exports/inspection-<uid> --client="Autoplug Motors"
 *
 * - New ids everywhere; photo/signature files are re-homed under
 *   writable/uploads/<new inspection id>/ and their paths rewritten.
 * - Template: matched by name, else this install's default template.
 *   Field links (used for dropdown options when editing) are matched by
 *   section title + field name.
 * - Vehicle: an existing vehicle with the same VIN (or registration) is
 *   reused and only its blank fields are filled; otherwise it's created.
 * - Users: matched by email; otherwise the first admin.
 * - --client (uid or exact name) links the inspection to a client here.
 * - Refuses to run if this install already has the same report id.
 * Paths are relative to writable/ unless absolute.
 */
class CheckaImportInspection extends BaseCommand
{
    protected $group       = 'Checka';
    protected $name        = 'checka:import-inspection';
    protected $description = 'Import one inspection exported with checka:export-inspection.';
    protected $usage       = 'checka:import-inspection <export folder> [--client=<uid|name>] [--dry-run]';
    protected $options     = [
        '--client'  => 'Client uid or exact name to link the inspection to.',
        '--dry-run' => 'Show what would happen without writing anything.',
    ];

    public function run(array $params): int
    {
        $arg     = rtrim((string) ($params[0] ?? ''), '/\\');
        $src     = $arg !== '' && ($arg[0] === '/' || preg_match('#^[A-Za-z]:[\\\\/]#', $arg)) ? $arg : WRITEPATH . ltrim($arg, '/');
        $dry     = self::flag('dry-run');
        $clientQ = self::opt('client');

        if ($arg === '' || ! is_file($src . '/bundle.json')) {
            CLI::error('No bundle.json found in ' . $src . ' — give the export folder (unzip it first if you zipped it).');

            return EXIT_ERROR;
        }
        $b = json_decode((string) file_get_contents($src . '/bundle.json'), true);
        if (! is_array($b) || ($b['format'] ?? '') !== 'checka-inspection') {
            CLI::error('Not a Checka inspection export (bundle.json missing or invalid).');

            return EXIT_ERROR;
        }

        $db  = db_connect();
        $ins = $b['inspection'];
        $uid = (string) $ins['report_uid'];

        if ($db->table('inspections')->where('report_uid', $uid)->countAllResults() > 0) {
            CLI::error('This install already has an inspection with report id ' . $uid . ' — nothing imported.');

            return EXIT_ERROR;
        }

        // ---- template + field map ----
        $tpl = null;
        if (! empty($b['template']['name'])) {
            $tpl = $db->table('templates')->where('name', $b['template']['name'])->orderBy('id', 'DESC')->get()->getRowArray();
        }
        $tpl ??= $db->table('templates')->where('is_default', 1)->get()->getRowArray()
            ?? $db->table('templates')->orderBy('id')->get()->getRowArray();
        $fieldMap = [];
        if ($tpl) {
            $rows = $db->table('template_fields f')->select('f.id, f.name, s.title')
                ->join('template_sections s', 's.id = f.section_id')
                ->where('s.template_id', $tpl['id'])->get()->getResultArray();
            foreach ($rows as $r) {
                $fieldMap[$this->key($r['title'], $r['name'])] ??= (int) $r['id'];
            }
        }

        // ---- users ----
        $userFor = function (?string $email) use ($db): ?int {
            if ($email && $db->tableExists('auth_identities')) {
                $r = $db->table('auth_identities')->where('type', 'email_password')->where('secret', $email)->get()->getRowArray();
                if ($r) {
                    return (int) $r['user_id'];
                }
            }
            $admin = $db->tableExists('auth_groups_users')
                ? $db->table('auth_groups_users')->where('group', 'admin')->orderBy('user_id')->get()->getRowArray()
                : null;

            return $admin ? (int) $admin['user_id'] : null;
        };
        $mechanicId = $userFor($b['users']['mechanic'] ?? null);
        $createdBy  = $userFor($b['users']['created_by'] ?? null);

        // ---- client ----
        $client = null;
        if ($clientQ !== null && $clientQ !== '' && $db->tableExists('clients')) {
            $client = $db->table('clients')->where('deleted_at', null)
                ->groupStart()->where('uid', $clientQ)->orWhere('name', $clientQ)->groupEnd()
                ->get()->getRowArray();
            if ($client === null) {
                CLI::error('No client matches "' . $clientQ . '". Add them under Clients first, or leave --client off and link later from the report page.');

                return EXIT_ERROR;
            }
        }

        // ---- vehicle ----
        $srcVeh  = $b['vehicle'] ?? null;
        $vehicle = null;
        if ($srcVeh) {
            if (trim((string) ($srcVeh['vin'] ?? '')) !== '') {
                $vehicle = $db->query(
                    'SELECT * FROM vehicles WHERE deleted_at IS NULL AND UPPER(TRIM(vin)) = ? ORDER BY id DESC LIMIT 1',
                    [strtoupper(trim($srcVeh['vin']))],
                )->getRowArray();
            }
            if ($vehicle === null && trim((string) ($srcVeh['plate'] ?? '')) !== '') {
                $plate   = strtoupper(preg_replace('/[\s-]+/', '', $srcVeh['plate']));
                $vehicle = $db->query(
                    "SELECT * FROM vehicles WHERE deleted_at IS NULL AND UPPER(REPLACE(REPLACE(plate,' ',''),'-','')) = ? ORDER BY id DESC LIMIT 1",
                    [$plate],
                )->getRowArray();
            }
        }

        $label = $srcVeh ? trim(($srcVeh['make'] ?? '') . ' ' . ($srcVeh['model'] ?? '') . ' ' . ($srcVeh['plate'] ?? '')) : '(no vehicle)';
        CLI::write('Importing ' . $uid . ' — ' . $label . ', ' . $ins['inspection_date'] . ' (from ' . ($b['source'] ?? '?') . ')', 'green');
        CLI::write('  template: ' . ($tpl ? $tpl['name'] . ' (#' . $tpl['id'] . ')' : 'none') . ($tpl && ($b['template']['name'] ?? '') !== $tpl['name'] ? '  [no name match — using default]' : ''));
        CLI::write('  vehicle:  ' . ($vehicle ? 'reuse existing #' . $vehicle['id'] : ($srcVeh ? 'create new' : 'none')));
        CLI::write('  client:   ' . ($client ? $client['name'] : 'none (link later from the report page)'));
        CLI::write('  values: ' . count($b['values']) . ', photos: ' . count($b['photos']));

        if ($dry) {
            $unmatched = 0;
            foreach ($b['values'] as $v) {
                if (! isset($fieldMap[$this->key($v['section_title'], $v['field_name'])])) {
                    $unmatched++;
                }
            }
            CLI::write('  fields without a template match (still imported, just not linked): ' . $unmatched);
            CLI::write('Dry run — nothing written.', 'yellow');

            return EXIT_SUCCESS;
        }

        $newDir  = null;
        $written = [];
        $db->transBegin();

        try {
            // Vehicle
            if ($srcVeh && $vehicle === null) {
                $row               = $this->only($db, 'vehicles', $srcVeh, ['id']);
                $row['created_by'] = $createdBy;
                if ($client) {
                    $row['client'] = $client['name'];
                    $row['client_email'] = $client['email'] ?: ($row['client_email'] ?? null);
                    $row['client_phone'] = $client['phone'] ?: ($row['client_phone'] ?? null);
                }
                $db->table('vehicles')->insert($row);
                $vehicleId = (int) $db->insertID();
            } elseif ($vehicle !== null) {
                $vehicleId = (int) $vehicle['id'];
                $fill      = [];
                foreach ($this->only($db, 'vehicles', $srcVeh, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by']) as $k => $v) {
                    if (($vehicle[$k] ?? null) === null || $vehicle[$k] === '') {
                        if ($v !== null && $v !== '') {
                            $fill[$k] = $v;
                        }
                    }
                }
                if ($client) {
                    $fill['client'] = $client['name'];
                }
                if ($fill !== []) {
                    $db->table('vehicles')->where('id', $vehicleId)->update($fill);
                }
            } else {
                $vehicleId = null;
            }

            // Inspection
            $row                = $this->only($db, 'inspections', $ins, ['id']);
            $row['vehicle_id']  = $vehicleId;
            $row['template_id'] = $tpl['id'] ?? $row['template_id'] ?? null;
            $row['mechanic_id'] = $mechanicId;
            $row['created_by']  = $createdBy;
            if ($client && $db->fieldExists('client_id', 'inspections')) {
                $row['client_id'] = (int) $client['id'];
            }
            $db->table('inspections')->insert($row);
            $newId  = (int) $db->insertID();
            $newDir = WRITEPATH . 'uploads/' . $newId;

            // Values
            $valueMap = [];
            foreach ($b['values'] as $v) {
                $oldVid           = (int) $v['id'];
                $r                = $this->only($db, 'inspection_values', $v, ['id']);
                $r['inspection_id'] = $newId;
                $r['field_id']    = $fieldMap[$this->key($v['section_title'], $v['field_name'])] ?? null;
                $db->table('inspection_values')->insert($r);
                $valueMap[$oldVid] = (int) $db->insertID();
            }

            // Files → uploads/<newId>/<basename>
            $relocate = function (?string $rel) use ($src, $newId, $newDir, &$written): ?string {
                if ($rel === null || $rel === '') {
                    return null;
                }
                $rel  = ltrim($rel, '/');
                $name = basename($rel);
                $dest = $newDir . '/' . $name;
                if (! isset($written[$dest])) {
                    $from = $src . '/files/' . $rel;
                    if (! is_file($from)) {
                        return $newId . '/' . $name; // file was missing at export; keep the reference
                    }
                    if (! is_dir($newDir) && ! mkdir($newDir, 0755, true) && ! is_dir($newDir)) {
                        throw new \RuntimeException('Cannot create ' . $newDir);
                    }
                    if (! copy($from, $dest)) {
                        throw new \RuntimeException('Cannot write ' . $dest);
                    }
                    @chmod($dest, 0644);
                    $written[$dest] = true;
                }

                return $newId . '/' . $name;
            };

            foreach ($b['photos'] as $p) {
                $r                  = $this->only($db, 'inspection_photos', $p, ['id']);
                $r['inspection_id'] = $newId;
                $r['value_id']      = $p['value_id'] ? ($valueMap[(int) $p['value_id']] ?? null) : null;
                $r['path']          = $relocate($p['path']);
                $r['thumb_path']    = $relocate($p['thumb_path'] ?? null);
                $db->table('inspection_photos')->insert($r);
            }
            if (! empty($ins['signature_path'])) {
                $db->table('inspections')->where('id', $newId)->update(['signature_path' => $relocate($ins['signature_path'])]);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database error during import.');
            }
            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            foreach (array_keys($written) as $f) {
                @unlink($f);
            }
            if ($newDir && is_dir($newDir)) {
                @rmdir($newDir);
            }
            CLI::error('Import failed, nothing kept: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Imported as inspection #' . $newId . ' — ' . count($written) . ' file(s) copied.', 'green');
        CLI::write('  open: ' . site_url('reports/' . $uid));

        return EXIT_SUCCESS;
    }

    /** `--name value` (CodeIgniter's style) or `--name=value`. */
    public static function opt(string $name): ?string
    {
        $v = CLI::getOption($name);
        if (is_string($v) && $v !== '') {
            return $v;
        }
        $argv = $_SERVER['argv'] ?? [];
        foreach ($argv as $i => $a) {
            if (str_starts_with($a, '--' . $name . '=')) {
                return substr($a, strlen($name) + 3);
            }
            if ($a === '--' . $name && isset($argv[$i + 1]) && ! str_starts_with($argv[$i + 1], '--')) {
                return $argv[$i + 1];
            }
        }

        return null;
    }

    public static function flag(string $name): bool
    {
        return CLI::getOption($name) !== null || in_array('--' . $name, $_SERVER['argv'] ?? [], true);
    }

    private function key(?string $section, ?string $field): string
    {
        return mb_strtolower(trim((string) $section)) . '|' . mb_strtolower(trim((string) $field));
    }

    /** The row's columns that also exist in this install's table, minus $drop. */
    private function only($db, string $table, array $row, array $drop = []): array
    {
        static $cols = [];
        $cols[$table] ??= array_flip($db->getFieldNames($table));

        return array_diff_key(array_intersect_key($row, $cols[$table]), array_flip($drop));
    }
}
