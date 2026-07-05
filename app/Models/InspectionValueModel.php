<?php

namespace App\Models;

use CodeIgniter\Model;

class InspectionValueModel extends Model
{
    protected $table         = 'inspection_values';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'inspection_id', 'field_id', 'section_idx', 'section_title', 'is_critical',
        'ref', 'field_name', 'field_type', 'role', 'measure', 'is_sub',
        'choices', 'choice',
        'rating', 'value_text', 'value_number', 'comment', 'cost', 'sort',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function forInspection(int $inspectionId): array
    {
        return $this->where('inspection_id', $inspectionId)
            ->orderBy('sort', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
