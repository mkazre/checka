<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInspections extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'report_uid'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'template_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'vehicle_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'mechanic_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'], // draft | completed
            'inspection_date' => ['type' => 'DATE', 'null' => true],
            'conclusion'      => ['type' => 'TEXT', 'null' => true],
            'signature_path'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'score'           => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'verdict_status'  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'verdict_text'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'meta'            => ['type' => 'TEXT', 'null' => true], // JSON: dealer, mechanic name, km before/after…
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('report_uid');
        $this->forge->addKey('vehicle_id');
        $this->forge->addKey('mechanic_id');
        $this->forge->addKey('status');
        // FK to vehicles only (users table belongs to Shield's namespace; kept as plain index to avoid migration-order coupling).
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', '', 'SET NULL');
        $this->forge->createTable('inspections', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('inspections', true);
    }
}
