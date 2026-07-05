<?php

namespace App\Models;

use CodeIgniter\Model;

class TemplateModel extends Model
{
    protected $table            = 'templates';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'name', 'description', 'version', 'is_active', 'is_default', 'created_by',
    ];

    protected $validationRules = [
        'name' => 'required|string|max_length[160]',
    ];

    /**
     * Load a template with its sections and fields, ordered for display.
     *
     * @return array<string, mixed>|null
     */
    public function withTree(int $id): ?array
    {
        $template = $this->find($id);
        if ($template === null) {
            return null;
        }

        $sections = model(SectionModel::class)
            ->where('template_id', $id)
            ->orderBy('sort', 'ASC')
            ->orderBy('idx', 'ASC')
            ->findAll();

        $fieldModel = model(FieldModel::class);
        foreach ($sections as &$section) {
            $section['fields'] = $fieldModel
                ->where('section_id', $section['id'])
                ->orderBy('sort', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();
        }
        unset($section);

        $template['sections'] = $sections;

        return $template;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function defaultTemplate(): ?array
    {
        $row = $this->where('is_default', 1)->where('is_active', 1)->first();

        return $row ?: $this->where('is_active', 1)->orderBy('id', 'ASC')->first();
    }
}
