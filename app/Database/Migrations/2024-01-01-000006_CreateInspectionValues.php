<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-inspection checklist rows. These are a SNAPSHOT of the template at
 * capture time (section title, ref, field name/type/role are denormalised),
 * so historical reports stay correct even after an admin edits the template.
 */
class CreateInspectionValues extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'inspection_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'field_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'section_idx'   => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'section_title' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'is_critical'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'ref'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'field_name'    => ['type' => 'VARCHAR', 'constraint' => 200],
            'field_type'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'rating'],
            'role'          => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'measure'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'is_sub'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'rating'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true], // ok|minor|lesser|major
            'value_text'    => ['type' => 'TEXT', 'null' => true],
            'value_number'  => ['type' => 'DECIMAL', 'constraint' => '12,3', 'null' => true],
            'comment'       => ['type' => 'TEXT', 'null' => true],
            'cost'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'sort'          => ['type' => 'INT', 'constraint' => 6, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('inspection_id');
        $this->forge->addKey('role');
        $this->forge->addForeignKey('inspection_id', 'inspections', 'id', '', 'CASCADE');
        $this->forge->createTable('inspection_values', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('inspection_values', true);
    }
}
