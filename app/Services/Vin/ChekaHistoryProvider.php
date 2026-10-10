<?php

namespace App\Services\Vin;

use App\Models\InspectionModel;
use App\Models\InspectionValueModel;
use App\Models\PhotoModel;
use App\Models\VehicleModel;

/**
 * Free, unlimited, South-Africa-specific "own data source": every past
 * Checka inspection ever recorded against this VIN, on this workshop's own
 * install. Unlike the paid US-registry providers, this is real condition
 * history with dates, inspector findings/comments, severity, and actual
 * photos — because it's the platform's own accumulated inspection data,
 * not a third-party lookup. It costs nothing, has no rate limit, and gets
 * more valuable the more inspections Checka records over time — this is
 * the practical way to "build your own" vehicle history for SA vehicles:
 * the inspections already being captured ARE the history.
 *
 * It cannot tell you about a stolen report or a workshop-service visit
 * that happened outside Checka — only what this install itself inspected.
 */
class ChekaHistoryProvider implements VinProviderInterface
{
    public function label(): string
    {
        return 'Checka Inspection History';
    }

    public function capabilities(): array
    {
        return ['serviceHistory'];
    }

    public function fetch(string $vin): array
    {
        $vin        = strtoupper(trim($vin));
        $vehicleIds = array_column(
            model(VehicleModel::class)->select('id')->where('vin', $vin)->findAll(),
            'id',
        );
        if ($vehicleIds === []) {
            return [
                'ok'       => true,
                'provider' => $this->label(),
                'fields'   => [],
                'raw'      => ['note' => 'No vehicle record on this install matches this VIN yet.'],
            ];
        }

        $inspections = model(InspectionModel::class)
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('status', 'completed')
            ->orderBy('inspection_date', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $entries = [];
        foreach ($inspections as $ins) {
            $values  = model(InspectionValueModel::class)->forInspection((int) $ins['id']);
            $flagged = array_values(array_filter($values, static fn ($v) => ($v['rating'] ?? 'ok') !== 'ok'));

            $findings = [];
            foreach ($flagged as $v) {
                $findings[] = [
                    'severity' => $v['rating'],
                    'name'     => $v['field_name'],
                    'comment'  => $v['comment'],
                ];
            }

            $photoRows = model(PhotoModel::class)->forInspection((int) $ins['id']);
            $photos    = [];
            foreach (array_slice($photoRows, 0, 6) as $p) {
                if ($p['is_video']) {
                    continue;
                }
                $photos[] = [
                    'url'        => site_url('media/' . $p['id']),
                    'thumb'      => site_url('media/' . $p['id'] . '/thumb'),
                    'caption'    => $p['caption'],
                    'localThumb' => WRITEPATH . 'uploads/' . ($p['thumb_path'] ?: $p['path']),
                ];
            }

            $entries[] = [
                'date'       => $ins['inspection_date'] ?: substr((string) $ins['created_at'], 0, 10),
                'report_uid' => $ins['report_uid'],
                'score'      => $ins['score'],
                'verdict'    => $ins['verdict_text'],
                'findings'   => $findings,
                'photos'     => $photos,
            ];
        }

        if ($entries === []) {
            return [
                'ok'       => true,
                'provider' => $this->label(),
                'fields'   => [],
                'raw'      => ['note' => 'No completed Checka inspections on file for this VIN yet.'],
            ];
        }

        // No 'raw' here on purpose: these entries already carry an absolute
        // local file path per photo (localThumb, used only by the PDF
        // renderer) that has no business being dumped into the "full data"
        // section, and the Activity Timeline already shows this data in a
        // clean form — a raw echo would just be noisy duplication.
        return [
            'ok'       => true,
            'provider' => $this->label(),
            'fields'   => ['serviceHistory' => $entries],
        ];
    }
}
