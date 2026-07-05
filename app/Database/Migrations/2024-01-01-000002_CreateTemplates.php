<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplates extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 160],
            'description' => ['type' => 'TEXT', 'null' => true],
            'version'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '1.0'],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_default'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('templates', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('templates', true);
    }
}
