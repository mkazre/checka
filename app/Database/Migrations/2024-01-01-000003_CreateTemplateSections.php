<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateSections extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'idx'         => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 160],
            'is_critical' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort'        => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('template_id');
        $this->forge->addForeignKey('template_id', 'templates', 'id', '', 'CASCADE');
        $this->forge->createTable('template_sections', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('template_sections', true);
    }
}
