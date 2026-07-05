<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInspectionPhotos extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'inspection_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'value_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ref'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'path'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'thumb_path'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'mime'          => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'caption'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'is_video'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'taken_at'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'sort'          => ['type' => 'INT', 'constraint' => 6, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('inspection_id');
        $this->forge->addKey('value_id');
        $this->forge->addForeignKey('inspection_id', 'inspections', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('value_id', 'inspection_values', 'id', '', 'SET NULL');
        $this->forge->createTable('inspection_photos', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('inspection_photos', true);
    }
}
