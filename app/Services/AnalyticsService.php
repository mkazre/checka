<?php

namespace App\Services;

use Config\Database;

/**
 * Workshop analytics — aggregate KPIs over inspections for the analytics
 * dashboard (feature: analytics). Pure query-builder aggregates; no per-report
 * assembly, so it stays fast even with many inspections.
 */
class AnalyticsService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /** @return array<string,mixed> */
    public function summary(): array
    {
        $ins = $this->db->table('inspections')->where('deleted_at', null);
        $total = (clone $ins)->countAllResults();

        $completed = (int) $this->db->table('inspections')->where('deleted_at', null)->where('status', 'completed')->countAllResults();
        $draft     = $total - $completed;

        $avg = $this->db->table('inspections')->where('deleted_at', null)->where('status', 'completed')
            ->selectAvg('score', 'avg')->get()->getRow('avg');

        $approved = (int) $this->db->table('inspections')->where('deleted_at', null)->where('client_status', 'approved')->countAllResults();
        $declined = (int) $this->db->table('inspections')->where('deleted_at', null)->where('client_status', 'declined')->countAllResults();

        return [
            'total'     => $total,
            'completed' => $completed,
            'draft'     => $draft,
            'avgScore'  => $avg !== null ? round((float) $avg) : null,
            'approved'  => $approved,
            'declined'  => $declined,
        ];
    }

    /**
     * Completed inspections per month for the last $months (continuous).
     * @return array<int,array{label:string,ym:string,count:int}>
     */
    public function monthly(int $months = 6): array
    {
        $rows = $this->db->query(
            "SELECT DATE_FORMAT(COALESCE(inspection_date, created_at), '%Y-%m') ym, COUNT(*) c
             FROM inspections WHERE deleted_at IS NULL AND status='completed'
             GROUP BY ym"
        )->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $map[$r['ym']] = (int) $r['c'];
        }

        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts = strtotime("first day of -{$i} month");
            $ym = date('Y-m', $ts);
            $out[] = ['label' => date('M', $ts), 'ym' => $ym, 'count' => $map[$ym] ?? 0];
        }

        return $out;
    }

    /** Verdict distribution among completed inspections. @return array<string,int> */
    public function verdictDist(): array
    {
        $rows = $this->db->table('inspections')->where('deleted_at', null)->where('status', 'completed')
            ->select('verdict_status, COUNT(*) c')->groupBy('verdict_status')->get()->getResultArray();
        $dist = ['ok' => 0, 'minor' => 0, 'lesser' => 0, 'major' => 0];
        foreach ($rows as $r) {
            $k = $r['verdict_status'] ?: 'ok';
            $dist[$k] = ($dist[$k] ?? 0) + (int) $r['c'];
        }

        return $dist;
    }

    /**
     * Most common lesser/major findings across completed inspections.
     * @return array<int,array{name:string,count:int}>
     */
    public function topFailures(int $limit = 8): array
    {
        return $this->db->query(
            "SELECT v.field_name name, COUNT(*) count
             FROM inspection_values v
             JOIN inspections i ON i.id = v.inspection_id AND i.deleted_at IS NULL AND i.status='completed'
             WHERE v.field_type='rating' AND v.rating IN ('lesser','major')
             GROUP BY v.field_name ORDER BY count DESC LIMIT " . (int) $limit
        )->getResultArray();
    }

    /**
     * Per-mechanic productivity (count + avg score).
     * @return array<int,array{name:string,count:int,avg:?int}>
     */
    public function mechanics(): array
    {
        $rows = $this->db->table('inspections')->where('deleted_at', null)->where('status', 'completed')
            ->select('mechanic_id, COUNT(*) c, AVG(score) a')
            ->groupBy('mechanic_id')->orderBy('c', 'DESC')->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'name'  => $this->userName($r['mechanic_id']),
                'count' => (int) $r['c'],
                'avg'   => $r['a'] !== null ? round((float) $r['a']) : null,
            ];
        }

        return $out;
    }

    private function userName($id): string
    {
        if (! $id) {
            return 'Unassigned';
        }
        $u = $this->db->table('users')->where('id', $id)->get()->getRowArray();
        if ($u && ! empty($u['username'])) {
            return $u['username'];
        }
        $ident = $this->db->table('auth_identities')->where('user_id', $id)->where('type', 'email_password')->get()->getRowArray();
        if ($ident && ! empty($ident['secret'])) {
            return $ident['secret'];
        }

        return 'User #' . $id;
    }
}
