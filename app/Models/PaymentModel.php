<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'client_id', 'invoice_id', 'amount', 'method', 'reference', 'gateway_ref',
        'paid_at', 'notes', 'recorded_by',
    ];

    public const METHODS = [
        'eft'      => 'EFT / bank transfer',
        'card'     => 'Card (in person)',
        'cash'     => 'Cash',
        'paystack' => 'Online (Paystack)',
        'other'    => 'Other',
    ];

    /** @return list<array<string,mixed>> payments joined with client name + invoice number */
    public function listing(?int $clientId = null, int $limit = 300): array
    {
        $b = $this->select('payments.*, clients.name AS client_name, invoices.invoice_no, invoices.uid AS invoice_uid')
            ->join('clients', 'clients.id = payments.client_id', 'left')
            ->join('invoices', 'invoices.id = payments.invoice_id', 'left')
            ->orderBy('payments.paid_at', 'DESC')
            ->orderBy('payments.id', 'DESC');
        if ($clientId !== null) {
            $b->where('payments.client_id', $clientId);
        }

        return $b->findAll($limit);
    }
}
