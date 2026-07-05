<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a standardised "assessment" radio group to checklist items:
 *  - template_fields.choices : JSON list of tailored options for the item
 *  - inspection_values.choices : snapshot of those options at capture time
 *  - inspection_values.choice  : the option the mechanic selected
 * Independent of the RAG rating.
 */
class AddFieldChoices extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('template_fields', [
            'choices' => ['type' => 'TEXT', 'null' => true, 'after' => 'options'],
        ]);
        $this->forge->addColumn('inspection_values', [
            'choices' => ['type' => 'TEXT', 'null' => true, 'after' => 'measure'],
            'choice'  => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'choices'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('template_fields', 'choices');
        $this->forge->dropColumn('inspection_values', ['choices', 'choice']);
    }
}
