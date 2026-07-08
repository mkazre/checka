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
        // core
        'make', 'model', 'year', 'colour', 'vin', 'plate', 'odometer',
        'seller', 'service_date', 'service_km', 'created_by',
        // spec
        'vehicle_type', 'derivative', 'body_type', 'doors', 'engine_number',
        'power_displacement', 'fuel', 'transmission', 'paint_finish', 'options',
        // registration / licence disc
        'registered_owner', 'register_number', 'licence_no', 'disc_control_no',
        'date_first_licensing', 'last_cor', 'licence_expiry', 'gvm', 'tare',
        'seating', 'date_of_test',
        // assignment / context
        'client', 'client_email', 'client_phone', 'inspection_location', 'disc_raw',
        // reference (Wikimedia) hero image
        'stock_image_url',
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
