<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Marks every field of the default template as required.
 */
class RequireDefaultFieldsSeeder extends Seeder
{
    public function run(): void
    {
        $tpl = $this->db->table('templates')->where('is_default', 1)->get()->getRowArray();
        if (! $tpl) {
            return;
        }

        $sectionIds = $this->db->table('template_sections')
            ->select('id')->where('template_id', $tpl['id'])->get()->getResultArray();
        $sectionIds = array_column($sectionIds, 'id');
        if ($sectionIds === []) {
            return;
        }

        $this->db->table('template_fields')
            ->whereIn('section_id', $sectionIds)
            ->update(['is_required' => 1]);
    }
}
