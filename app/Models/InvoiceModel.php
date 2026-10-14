<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table          = 'invoices';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'uid', 'invoice_no', 'client_id', 'inspection_id', 'status',
        'issue_date', 'due_date', 'subtotal', 'vat_rate', 'vat_amount', 'total',
        'amount_paid', 'notes', 'sent_at', 'created_by',
    ];

    /** Statuses that still count towards what the client owes. */
    public const OPEN = ['sent', 'partial'];

    public function findByUid(string $uid): ?array
    {
        return $this->where('uid', $uid)->first() ?: null;
    }

    /** @return list<array<string,mixed>> invoices joined with client name */
    public function listing(string $status = '', string $q = '', ?int $clientId = null, int $limit = 300): array
    {
        $b = $this->select('invoices.*, clients.name AS client_name')
            ->join('clients', 'clients.id = invoices.client_id', 'left')
            ->orderBy('invoices.issue_date', 'DESC')
            ->orderBy('invoices.id', 'DESC');

        if ($status === 'overdue') {
            $b->whereIn('invoices.status', self::OPEN)->where('invoices.due_date <', date('Y-m-d'));
        } elseif ($status !== '') {
            $b->where('invoices.status', $status);
        }
        if ($clientId !== null) {
            $b->where('invoices.client_id', $clientId);
        }
        if ($q !== '') {
            $b->groupStart()->like('invoices.invoice_no', $q)->orLike('clients.name', $q)->groupEnd();
        }

        return $b->findAll($limit);
    }

    public static function balance(array $inv): float
    {
        return round((float) $inv['total'] - (float) $inv['amount_paid'], 2);
    }

    public static function isOverdue(array $inv): bool
    {
        return in_array($inv['status'], self::OPEN, true) && $inv['due_date'] < date('Y-m-d');
    }
}
