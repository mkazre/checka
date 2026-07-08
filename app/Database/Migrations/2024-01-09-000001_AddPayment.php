<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayment extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('inspections', [
            'payment_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],  // unpaid|paid
            'payment_ref'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'payment_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'paid_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('inspections', 'payment_status payment_ref payment_amount paid_at');
    }
}
