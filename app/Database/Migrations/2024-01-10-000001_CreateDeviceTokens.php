<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeviceTokens extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'token'      => ['type' => 'VARCHAR', 'constraint' => 300],
            'platform'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->createTable('device_tokens', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('device_tokens', true);
    }
}
