<?php

namespace App\Services;

use App\Models\InspectionModel;

/**
 * Builds a vehicle's inspection timeline for the history & wear-trends feature.
 * Reuses the intelligence engine so score / counts / verdict / tread depths are
 * computed identically to the report.
 */
class HistoryService
{
    /**
     * @return array<int,array<string,mixed>> chronological (oldest first)
     */
    public function forVehicle(int $vehicleId): array
    {
        $rows = model(InspectionModel::class)
            ->where('vehicle_id', $vehicleId)
            ->where('status', 'completed')
            ->orderBy('inspection_date', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $timeline = [];
        foreach ($rows as $ins) {
            $report = model(InspectionModel::class)->assembleReport($ins['report_uid']);
            if ($report === null) {
                continue;
            }
            $c = service('intelligence')->compute($report);

            $treads = [];
            foreach ($c['tyres'] as $t) {
                if (($t['tread'] ?? 0) > 0) {
                    $treads[$t['pos']] = (float) $t['tread'];
                }
            }

            $timeline[] = [
                'uid'     => $ins['report_uid'],
                'date'    => $ins['inspection_date'] ?: substr((string) $ins['created_at'], 0, 10),
                'score'   => (int) $c['score'],
                'verdict' => $c['verdict'],
                'counts'  => $c['counts'],
                'treads'  => $treads,
            ];
        }

        return $timeline;
    }

    /**
     * Summarise the timeline: score delta, tread wear per position, new-issue trend.
     *
     * @param array<int,array<string,mixed>> $timeline
     * @return array<string,mixed>
     */
    public function summary(array $timeline): array
    {
        $n = count($timeline);
        if ($n < 1) {
            return ['count' => 0];
        }
        $first = $timeline[0];
        $last  = $timeline[$n - 1];

        // Tread wear per position (first vs last where both present).
        $treadWear = [];
        foreach ($last['treads'] as $pos => $mm) {
            if (isset($first['treads'][$pos]) && $n > 1) {
                $treadWear[$pos] = [
                    'first' => $first['treads'][$pos],
                    'last'  => $mm,
                    'delta' => round($mm - $first['treads'][$pos], 1),
                ];
            }
        }

        return [
            'count'      => $n,
            'scoreFirst' => $first['score'],
            'scoreLast'  => $last['score'],
            'scoreDelta' => $last['score'] - $first['score'],
            'spanFrom'   => $first['date'],
            'spanTo'     => $last['date'],
            'treadWear'  => $treadWear,
        ];
    }
}
