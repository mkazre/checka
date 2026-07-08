<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClientContact extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('vehicles', [
            'client_email' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'client'],
            'client_phone' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'client'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('vehicles', 'client_email client_phone');
    }
}
