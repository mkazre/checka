<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateFields extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'section_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'ref'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 200],
            // rating | text | textarea | number | radio | dropdown | checkbox | photo | signature | date
            'type'         => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'rating'],
            // semantic role: rating | tread_depth | dot_code | body_zone_* | odometer | service_date | service_km ...
            'role'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'options'      => ['type' => 'TEXT', 'null' => true], // JSON array for radio/dropdown/checkbox
            'measure'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true], // e.g. mm, size, week/yr
            'is_sub'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_required'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'allow_photos' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort'         => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('section_id');
        $this->forge->addForeignKey('section_id', 'template_sections', 'id', '', 'CASCADE');
        $this->forge->createTable('template_fields', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('template_fields', true);
    }
}
