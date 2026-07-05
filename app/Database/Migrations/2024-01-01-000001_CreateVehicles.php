<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVehicles extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'make'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'model'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'year'         => ['type' => 'SMALLINT', 'constraint' => 6, 'null' => true],
            'colour'       => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'vin'          => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'plate'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'odometer'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'seller'       => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'service_date' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'service_km'   => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'created_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('vin');
        $this->forge->addKey('plate');
        $this->forge->createTable('vehicles', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('vehicles', true);
    }
}
