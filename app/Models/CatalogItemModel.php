<?php

namespace App\Models;

use CodeIgniter\Model;

class CatalogItemModel extends Model
{
    protected $table          = 'catalog_items';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['name', 'part_no', 'default_price', 'notes', 'use_count', 'last_used_at'];
    protected $validationRules = ['name' => 'required|max_length[255]'];

    /** Matches on name or part number, most-used first. @return list<array<string,mixed>> */
    public function suggest(string $q, int $limit = 12): array
    {
        return $this->groupStart()->like('name', $q)->orLike('part_no', $q)->groupEnd()
            ->orderBy('use_count', 'DESC')->orderBy('name', 'ASC')->findAll($limit);
    }

    /** @return list<array<string,mixed>> */
    public function search(string $q = '', string $sort = 'name', int $limit = 1000): array
    {
        if ($q !== '') {
            $this->groupStart()->like('name', $q)->orLike('part_no', $q)->orLike('notes', $q)->groupEnd();
        }
        match ($sort) {
            'used'   => $this->orderBy('use_count', 'DESC'),
            'recent' => $this->orderBy('last_used_at', 'DESC'),
            default  => $this->orderBy('name', 'ASC'),
        };

        return $this->findAll($limit);
    }

    /**
     * Remember an item typed on a PO: update the catalogue entry with the
     * same name (case-insensitive), or create it. A price or part number
     * on the PO refreshes the stored one; a blank never wipes it.
     */
    public function remember(string $name, ?string $partNo, $price, bool $countUse = true): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }
        $row = $this->where('LOWER(name) = ' . $this->db->escape(mb_strtolower($name)), null, false)->first();
        $data = ['last_used_at' => date('Y-m-d H:i:s')];
        if ($partNo !== null && trim($partNo) !== '') {
            $data['part_no'] = trim($partNo);
        }
        if ($price !== null && $price !== '') {
            $data['default_price'] = round((float) $price, 2);
        }
        if ($row) {
            $data['use_count'] = (int) $row['use_count'] + ($countUse ? 1 : 0);
            $this->update($row['id'], $data);
        } else {
            $this->insert($data + ['name' => mb_substr($name, 0, 255), 'use_count' => 1]);
        }
    }
}
