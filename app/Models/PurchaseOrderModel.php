<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseOrderModel extends Model
{
    protected $table          = 'purchase_orders';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'uid', 'po_no', 'vendor_id', 'status', 'order_date', 'description', 'reference',
        'show_pricing', 'show_vehicle', 'vehicle_id', 'veh_make', 'veh_model', 'veh_year',
        'veh_vin', 'veh_plate', 'veh_colour', 'veh_engine', 'veh_odometer',
        'vat_rate', 'subtotal', 'vat_amount', 'total', 'notes', 'sent_at', 'created_by',
    ];

    public const STATUSES = ['draft' => 'Draft', 'sent' => 'Sent', 'received' => 'Received', 'cancelled' => 'Cancelled'];
    public const VEHICLE  = [
        'veh_make' => 'Make', 'veh_model' => 'Model', 'veh_year' => 'Year', 'veh_vin' => 'VIN',
        'veh_plate' => 'Registration', 'veh_colour' => 'Colour', 'veh_engine' => 'Engine', 'veh_odometer' => 'Odometer',
    ];

    public function findByUid(string $uid): ?array
    {
        return $this->where('uid', $uid)->first() ?: null;
    }

    /** @return list<array<string,mixed>> POs joined with vendor name */
    public function listing(array $f = [], int $limit = 500): array
    {
        $b = $this->select('purchase_orders.*, vendors.name AS vendor_name')
            ->join('vendors', 'vendors.id = purchase_orders.vendor_id', 'left')
            ->orderBy('purchase_orders.order_date', 'DESC')->orderBy('purchase_orders.id', 'DESC');
        if (! empty($f['from'])) {
            $b->where('purchase_orders.order_date >=', $f['from']);
        }
        if (! empty($f['to'])) {
            $b->where('purchase_orders.order_date <=', $f['to']);
        }
        if (! empty($f['vendor_id'])) {
            $b->where('purchase_orders.vendor_id', (int) $f['vendor_id']);
        }
        if (! empty($f['status'])) {
            $b->where('purchase_orders.status', $f['status']);
        }
        if (! empty($f['q'])) {
            $b->groupStart()->like('purchase_orders.po_no', $f['q'])->orLike('vendors.name', $f['q'])
                ->orLike('purchase_orders.veh_vin', $f['q'])->orLike('purchase_orders.veh_plate', $f['q'])
                ->orLike('purchase_orders.description', $f['q'])->groupEnd();
        }

        return $b->findAll($limit);
    }

    public static function hasVehicle(array $po): bool
    {
        foreach (array_keys(self::VEHICLE) as $k) {
            if (trim((string) ($po[$k] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}
