<?php

namespace App\Services;

/**
 * Checka intelligence engine.
 *
 * A faithful PHP port of the JS logic in the design sample
 * (inspection-report.html): severity counts, condition score, overall
 * verdict, DOT-date decoding, tread status, body-damage zones and tyre
 * health. This is the single source of truth used by BOTH the web report
 * view and the PDF renderer, so the two can never drift.
 */
class InspectionIntelligence
{
    /** RAG rating meta (label, weight, rank, colour, bg). */
    public function ratings(): array
    {
        return CHECKA_RATINGS;
    }

    private function rank(string $status): int
    {
        return CHECKA_RATINGS[$status]['rank'] ?? 0;
    }

    private function weight(string $status): int
    {
        return CHECKA_RATINGS[$status]['weight'] ?? 0;
    }

    /**
     * Flatten all rating-bearing items across sections.
     * Only fields of type "rating" contribute to score/verdict.
     *
     * @param array<string, mixed> $report Output of InspectionModel::assembleReport()
     *
     * @return list<array<string, mixed>>
     */
    public function ratingItems(array $report): array
    {
        $items = [];
        foreach ($report['sections'] ?? [] as $section) {
            foreach ($section['items'] ?? [] as $item) {
                if (($item['type'] ?? 'rating') === 'rating') {
                    $item['section_title']  = $section['title'];
                    $item['is_critical']    = $section['is_critical'] ?? false;
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * @return array{ok:int, minor:int, lesser:int, major:int}
     */
    public function severityCounts(array $report): array
    {
        $c = ['ok' => 0, 'minor' => 0, 'lesser' => 0, 'major' => 0];
        foreach ($this->ratingItems($report) as $item) {
            $status = $item['status'] ?? 'ok';
            if (isset($c[$status])) {
                $c[$status]++;
            }
        }

        return $c;
    }

    /**
     * Condition score 0..100. Safety-critical sections weigh double.
     */
    public function conditionScore(array $report): int
    {
        $score = 100;
        foreach ($this->ratingItems($report) as $item) {
            $w = $this->weight($item['status'] ?? 'ok');
            if (! empty($item['is_critical'])) {
                $w *= 2;
            }
            $score -= $w;
        }

        return max(0, min(100, $score));
    }

    /**
     * @return array{status:string, text:string}
     */
    public function overallVerdict(array $report): array
    {
        $c = $this->severityCounts($report);
        if ($c['major']) {
            return ['status' => 'major', 'text' => 'Not Roadworthy — Major Issues'];
        }
        if ($c['lesser']) {
            return ['status' => 'lesser', 'text' => 'Approved if Repaired'];
        }
        if ($c['minor']) {
            return ['status' => 'minor', 'text' => 'Approved — Minor Items Noted'];
        }

        return ['status' => 'ok', 'text' => 'Approved — No Issues'];
    }

    /**
     * Decode a DOT code "WWYY" (e.g. 4719 = week 47 of 2019) into age + flag.
     *
     * @return array{label:string, age:?float, flag:?string}
     */
    public function decodeDOT(?string $code, ?string $inspectionDate = null): array
    {
        $code = trim((string) $code);
        if (! preg_match('/^\d{4}$/', $code)) {
            return ['label' => $code, 'age' => null, 'flag' => null];
        }
        $wk  = (int) substr($code, 0, 2);
        $yr  = 2000 + (int) substr($code, 2);
        $made = mktime(0, 0, 0, 1, 1 + ($wk - 1) * 7, $yr);
        $insp = $inspectionDate ? strtotime($inspectionDate) : time();
        $age  = ($insp - $made) / (365.25 * 24 * 3600);

        $flag = 'ok';
        if ($age >= 10) {
            $flag = 'major';
        } elseif ($age >= 6) {
            $flag = 'lesser';
        } elseif ($age >= 5) {
            $flag = 'minor';
        }

        return ['label' => "Wk {$wk} · {$yr}", 'age' => round($age, 1), 'flag' => $flag];
    }

    /**
     * Tread status per SA/industry norms.
     *
     * @return array{flag:string, text:string}
     */
    public function treadStatus(float $mm): array
    {
        if ($mm < 1.6) {
            return ['flag' => 'major', 'text' => 'Below legal'];
        }
        if ($mm < 3) {
            return ['flag' => 'lesser', 'text' => 'Replace soon'];
        }
        if ($mm < 4) {
            return ['flag' => 'minor', 'text' => 'Monitor'];
        }

        return ['flag' => 'ok', 'text' => 'Serviceable'];
    }

    /**
     * Body-damage zones, coloured from items tagged with body_zone_* roles.
     *
     * @return array<string, string>
     */
    public function bodyZones(array $report): array
    {
        $zones = ['front' => 'ok', 'back' => 'ok', 'left' => 'ok', 'right' => 'ok', 'roof' => 'ok'];
        foreach ($report['sections'] ?? [] as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $role = $item['role'] ?? '';
                if (str_starts_with((string) $role, 'body_zone_')) {
                    $zone = substr($role, strlen('body_zone_'));
                    if (isset($zones[$zone])) {
                        $zones[$zone] = $item['status'] ?? 'ok';
                    }
                }
            }
        }

        return $zones;
    }

    /**
     * Build tyre-health cards. Walks the checklist in order: a tread_depth
     * item opens a tyre; a following dot_code item attaches its DOT.
     *
     * @return list<array<string, mixed>>
     */
    public function tyres(array $report): array
    {
        $inspectionDate = $report['inspection']['inspection_date'] ?? null;
        $tyres   = [];
        $current = null;

        foreach ($report['sections'] ?? [] as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $role = $item['role'] ?? '';
                if ($role === 'tread_depth') {
                    if ($current !== null) {
                        $tyres[] = $current;
                    }
                    $tread   = $this->numberFrom($item);
                    $current = [
                        'pos'   => $item['name'],
                        'tread' => $tread,
                        'dot'   => null,
                        'tstat' => $this->treadStatus($tread),
                        'dotInfo' => null,
                    ];
                } elseif ($role === 'dot_code' && $current !== null) {
                    $dot     = $item['text'] ?: (string) $item['comment'];
                    $dot     = preg_replace('/\D/', '', (string) $dot);
                    $current['dot']     = $dot;
                    $current['dotInfo'] = $this->decodeDOT($dot, $inspectionDate);
                }
            }
        }
        if ($current !== null) {
            $tyres[] = $current;
        }

        // worst-of flag per tyre
        foreach ($tyres as &$t) {
            $treadFlag = $t['tstat']['flag'];
            $dotFlag   = $t['dotInfo']['flag'] ?? 'ok';
            $t['worst'] = $this->rank($treadFlag) >= $this->rank($dotFlag) ? $treadFlag : $dotFlag;
        }
        unset($t);

        return $tyres;
    }

    private function numberFrom(array $item): float
    {
        if (isset($item['number']) && $item['number'] !== null && $item['number'] !== '') {
            return (float) $item['number'];
        }
        // fall back to first number in the comment / text
        $hay = (string) ($item['text'] ?? '') . ' ' . (string) ($item['comment'] ?? '');
        if (preg_match('/\d+(\.\d+)?/', $hay, $m)) {
            return (float) $m[0];
        }

        return 0.0;
    }

    /**
     * Flagged (non-ok) parent rating items, worst first — the report's
     * "Summary of Findings".
     *
     * @return list<array<string, mixed>>
     */
    public function findings(array $report): array
    {
        $flagged = [];
        foreach ($this->ratingItems($report) as $item) {
            if (($item['status'] ?? 'ok') !== 'ok' && empty($item['sub'])) {
                $flagged[] = $item;
            }
        }
        usort($flagged, fn ($a, $b) => $this->rank($b['status']) <=> $this->rank($a['status']));

        return $flagged;
    }

    /**
     * Everything the report/PDF needs, computed once.
     *
     * @return array<string, mixed>
     */
    public function compute(array $report): array
    {
        $counts  = $this->severityCounts($report);
        $verdict = $this->overallVerdict($report);

        return [
            'counts'    => $counts,
            'score'     => $this->conditionScore($report),
            'verdict'   => $verdict,
            'tyres'     => $this->tyres($report),
            'bodyZones' => $this->bodyZones($report),
            'findings'  => $this->findings($report),
            'ratings'   => CHECKA_RATINGS,
        ];
    }
}
