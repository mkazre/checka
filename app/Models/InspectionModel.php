<?php

namespace App\Models;

use CodeIgniter\Model;

class InspectionModel extends Model
{
    protected $table          = 'inspections';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'report_uid', 'template_id', 'vehicle_id', 'mechanic_id', 'status',
        'inspection_date', 'conclusion', 'signature_path', 'score',
        'verdict_status', 'verdict_text', 'meta', 'created_by',
    ];

    /**
     * Dashboard list with vehicle label joined in.
     *
     * @return list<array<string, mixed>>
     */
    public function listWithVehicle(?string $status = null): array
    {
        $builder = $this->select('inspections.*, vehicles.make, vehicles.model, vehicles.year, vehicles.plate, vehicles.vin')
            ->join('vehicles', 'vehicles.id = inspections.vehicle_id', 'left')
            ->orderBy('inspections.created_at', 'DESC');

        if ($status !== null && $status !== '') {
            $builder->where('inspections.status', $status);
        }

        return $builder->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUid(string $uid): ?array
    {
        return $this->where('report_uid', $uid)->first();
    }

    /**
     * Assemble the full report data model (mirrors the JSON in the sample):
     * vehicle + sections[] (each with items[], each item with photos[]).
     *
     * @return array<string, mixed>|null
     */
    public function assembleReport(string $uid): ?array
    {
        $inspection = $this->findByUid($uid);
        if ($inspection === null) {
            return null;
        }

        $vehicle = $inspection['vehicle_id']
            ? model(VehicleModel::class)->find($inspection['vehicle_id'])
            : null;

        $values = model(InspectionValueModel::class)->forInspection((int) $inspection['id']);
        $photos = model(PhotoModel::class)->forInspection((int) $inspection['id']);

        // Group photos by the value row they belong to.
        $photosByValue = [];
        foreach ($photos as $p) {
            $photosByValue[(int) $p['value_id']][] = [
                'id'      => (int) $p['id'],
                'ref'     => $p['ref'],
                'tag'     => $p['ref'],
                'ts'      => $p['taken_at'],
                'caption' => $p['caption'],
                'video'   => (bool) $p['is_video'],
                'url'     => site_url('media/' . $p['id']),
                'thumb'   => site_url('media/' . $p['id'] . '/thumb'),
                'path'    => $p['path'],
                'thumb_path' => $p['thumb_path'] ?: $p['path'],
            ];
            // (thumb_path used by the PDF to embed the small image, not the full-res one)
        }

        // Group values into sections.
        $sections = [];
        foreach ($values as $v) {
            $idx = (int) $v['section_idx'];
            if (! isset($sections[$idx])) {
                $sections[$idx] = [
                    'idx'         => $idx,
                    'title'       => $v['section_title'],
                    'is_critical' => (bool) $v['is_critical'],
                    'items'       => [],
                ];
            }
            $sections[$idx]['items'][] = [
                'id'      => (int) $v['id'],
                'ref'     => $v['ref'],
                'name'    => $v['field_name'],
                'type'    => $v['field_type'],
                'role'    => $v['role'],
                'measure' => $v['measure'],
                'sub'      => (bool) $v['is_sub'],
                'required' => (bool) ($v['is_required'] ?? false),
                'status'  => $v['rating'] ?: 'ok',
                'text'    => $v['value_text'],
                'number'  => $v['value_number'],
                'comment' => $v['comment'],
                'cost'    => $v['cost'],
                'choices' => \App\Models\FieldModel::choicesList($v['choices'] ?? null),
                'choice'  => $v['choice'] ?? null,
                'photos'  => $photosByValue[(int) $v['id']] ?? [],
            ];
        }
        ksort($sections);

        $meta = [];
        if (! empty($inspection['meta'])) {
            $decoded = json_decode((string) $inspection['meta'], true);
            $meta    = is_array($decoded) ? $decoded : [];
        }

        return [
            'inspection' => $inspection,
            'vehicle'    => $vehicle,
            'meta'       => $meta,
            'sections'   => array_values($sections),
            'verifyUrl'  => site_url('verify/' . $inspection['report_uid']),
        ];
    }
}
