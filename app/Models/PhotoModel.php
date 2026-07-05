<?php

namespace App\Models;

use CodeIgniter\Model;

class PhotoModel extends Model
{
    protected $table         = 'inspection_photos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'inspection_id', 'value_id', 'ref', 'path', 'thumb_path',
        'mime', 'caption', 'is_video', 'taken_at', 'sort',
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
