<?php

namespace App\Models;

use CodeIgniter\Model;

class SectionModel extends Model
{
    protected $table         = 'template_sections';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'template_id', 'idx', 'title', 'is_critical', 'sort',
    ];

    protected $validationRules = [
        'template_id' => 'required|is_natural_no_zero',
        'title'       => 'required|string|max_length[160]',
    ];
}
