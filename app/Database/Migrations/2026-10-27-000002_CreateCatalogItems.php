<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Remembered purchase-order items (the "Items" catalogue). */
class CreateCatalogItems extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'part_no'       => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'default_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'use_count'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'last_used_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->createTable('catalog_items', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('catalog_items', true);
    }
}
