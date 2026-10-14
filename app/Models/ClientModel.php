<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientModel extends Model
{
    protected $table          = 'clients';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'uid', 'type', 'name', 'contact_person', 'phone', 'email', 'billing_email',
        'address', 'vat_number', 'registration_number', 'payment_terms_days',
        'monthly_statements', 'last_statement_period', 'notes', 'created_by',
    ];

    protected $validationRules = [
        'name'          => 'required|max_length[160]',
        'type'          => 'required|in_list[business,individual]',
        'email'         => 'permit_empty|valid_email|max_length[160]',
        'billing_email' => 'permit_empty|valid_email|max_length[160]',
    ];

    protected $beforeInsert = ['assignUid'];

    protected function assignUid(array $data): array
    {
        $data['data']['uid'] ??= bin2hex(random_bytes(10));

        return $data;
    }

    public function findByUid(string $uid): ?array
    {
        return $this->where('uid', $uid)->first() ?: null;
    }

    /** Where invoices & statements go: the billing address if set, else the main email. */
    public static function invoiceEmail(array $client): string
    {
        return trim((string) ($client['billing_email'] ?: $client['email']));
    }

    /** @return list<array<string,mixed>> */
    public function search(string $q = '', int $limit = 200): array
    {
        $b = $this->orderBy('name', 'ASC');
        if ($q !== '') {
            $b->groupStart()
                ->like('name', $q)
                ->orLike('contact_person', $q)
                ->orLike('email', $q)
                ->orLike('phone', $q)
                ->orLike('vat_number', $q)
                ->groupEnd();
        }

        return $b->findAll($limit);
    }
}
