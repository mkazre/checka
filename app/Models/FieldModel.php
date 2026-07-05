<?php

namespace App\Models;

use CodeIgniter\Model;

class FieldModel extends Model
{
    protected $table         = 'template_fields';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'section_id', 'ref', 'name', 'type', 'role', 'options', 'choices',
        'measure', 'is_sub', 'is_required', 'allow_photos', 'sort',
    ];

    /** Field input types the builder offers. */
    public const TYPES = [
        'rating'    => 'RAG rating (Green/Blue/Orange/Red)',
        'text'      => 'Short text',
        'textarea'  => 'Long text',
        'number'    => 'Number',
        'radio'     => 'Radio (single choice)',
        'dropdown'  => 'Dropdown (single choice)',
        'checkbox'  => 'Checkboxes (multi choice)',
        'date'      => 'Date',
        'photo'     => 'Photo upload only',
        'signature' => 'Signature',
    ];

    /** Semantic roles that feed the report intelligence engine. */
    public const ROLES = [
        ''                 => '— none —',
        'rating'           => 'Primary RAG rating (counts toward score)',
        'tread_depth'      => 'Tyre tread depth (mm)',
        'dot_code'         => 'Tyre DOT / production date',
        'body_zone_front'  => 'Body damage: Front',
        'body_zone_back'   => 'Body damage: Rear',
        'body_zone_left'   => 'Body damage: Left',
        'body_zone_right'  => 'Body damage: Right',
        'odometer'         => 'Odometer reading',
        'service_date'     => 'Last service date',
        'service_km'       => 'Last service km',
    ];

    protected $validationRules = [
        'section_id' => 'required|is_natural_no_zero',
        'name'       => 'required|string|max_length[200]',
        'type'       => 'required|string|max_length[30]',
    ];

    /**
     * Decode the JSON options column into an array.
     *
     * @param array<string, mixed> $field
     *
     * @return list<string>
     */
    public static function options(array $field): array
    {
        if (empty($field['options'])) {
            return [];
        }
        $decoded = json_decode((string) $field['options'], true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Decode the JSON standardised-assessment choices into an array.
     *
     * @param array<string, mixed> $field
     *
     * @return list<string>
     */
    public static function choicesList($choices): array
    {
        if (empty($choices)) {
            return [];
        }
        $decoded = json_decode((string) $choices, true);

        return is_array($decoded) ? $decoded : [];
    }
}
