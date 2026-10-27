<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseOrderItemModel extends Model
{
    protected $table         = 'purchase_order_items';
    protected $returnType    = 'array';
    protected $allowedFields = ['po_id', 'description', 'part_no', 'qty', 'unit_price', 'sort'];

    /** @return list<array<string,mixed>> */
    public function forPo(int $poId): array
    {
        return $this->where('po_id', $poId)->orderBy('sort')->orderBy('id')->findAll();
    }
}
