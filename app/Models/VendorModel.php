<?php

namespace App\Models;

use CodeIgniter\Model;

class VendorModel extends Model
{
    protected $table          = 'vendors';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'uid', 'name', 'contact_person', 'phone', 'email', 'address', 'vat_number',
        'registration_number', 'account_number', 'notes', 'created_by',
    ];
    protected $validationRules = [
        'name'  => 'required|max_length[160]',
        'email' => 'permit_empty|valid_email|max_length[160]',
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

    /** @return list<array<string,mixed>> */
    public function search(string $q = '', int $limit = 500): array
    {
        $b = $this->orderBy('name', 'ASC');
        if ($q !== '') {
            $b->groupStart()->like('name', $q)->orLike('contact_person', $q)->orLike('email', $q)
                ->orLike('phone', $q)->orLike('account_number', $q)->groupEnd();
        }

        return $b->findAll($limit);
    }
}
