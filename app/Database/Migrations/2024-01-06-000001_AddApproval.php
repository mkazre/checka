<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApproval extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('inspections', [
            'client_status'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],  // pending|approved|declined
            'client_name'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'client_signature' => ['type' => 'TEXT', 'null' => true],                          // data URL
            'client_note'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'client_signed_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        // Per-item repair decision for the quote: null = undecided, 1 = approve, 0 = decline.
        $this->forge->addColumn('inspection_values', [
            'repair_approved' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('inspections', 'client_status client_name client_signature client_note client_signed_at');
        $this->forge->dropColumn('inspection_values', 'repair_approved');
    }
}
