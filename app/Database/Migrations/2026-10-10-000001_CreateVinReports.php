<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVinReports extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'report_uid'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'vin'              => ['type' => 'VARCHAR', 'constraint' => 32],
            'vehicle_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'requested_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'headline'         => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'make'             => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'model'            => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'year'             => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'providers_used'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'providers_failed' => ['type' => 'TEXT', 'null' => true],
            'data_json'        => ['type' => 'LONGTEXT'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('report_uid');
        $this->forge->addKey('vin');
        $this->forge->addKey('vehicle_id');
        $this->forge->createTable('vin_reports', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('vin_reports', true);
    }
}
