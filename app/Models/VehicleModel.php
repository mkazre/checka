<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleModel extends Model
{
    protected $table          = 'vehicles';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'make', 'model', 'year', 'colour', 'vin', 'plate', 'odometer',
        'seller', 'service_date', 'service_km', 'created_by',
    ];

    protected $validationRules = [
        'make'  => 'permit_empty|string|max_length[80]',
        'model' => 'permit_empty|string|max_length[80]',
        'year'  => 'permit_empty|integer',
        'vin'   => 'permit_empty|string|max_length[40]',
    ];

    public function label(array $v): string
    {
        $parts = array_filter([$v['year'] ?? null, $v['make'] ?? null, $v['model'] ?? null]);

        return trim(implode(' ', $parts)) ?: ('Vehicle #' . ($v['id'] ?? '?'));
    }
}
