<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceItemModel extends Model
{
    protected $table         = 'invoice_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['invoice_id', 'description', 'qty', 'unit_price', 'line_total', 'sort'];

    /** @return list<array<string,mixed>> */
    public function forInvoice(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)->orderBy('sort', 'ASC')->orderBy('id', 'ASC')->findAll();
    }
}
