<?php

namespace App\Models;

use CodeIgniter\Model;

class VinReportModel extends Model
{
    protected $table          = 'vin_reports';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'report_uid', 'vin', 'vehicle_id', 'requested_by', 'headline',
        'make', 'model', 'year', 'providers_used', 'providers_failed', 'data_json',
    ];

    public function findByUid(string $uid): ?array
    {
        $row = $this->where('report_uid', $uid)->first();

        return $row ?: null;
    }

    /** Decoded report payload (see VinCheckService::run shape) for a row. */
    public function payload(array $row): array
    {
        return json_decode((string) $row['data_json'], true) ?: [];
    }

    public function archive(int $limit = 50, int $offset = 0, string $search = ''): array
    {
        $q = $this->orderBy('created_at', 'DESC');
        if ($search !== '') {
            $q->groupStart()
                ->like('vin', $search)
                ->orLike('make', $search)
                ->orLike('model', $search)
                ->groupEnd();
        }

        return $q->findAll($limit, $offset);
    }
}
