<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Snapshots each field's "required" flag onto the inspection value, so
 * completion validation and the capture wizard know what must be filled.
 */
class AddValueRequired extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('inspection_values', [
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'is_sub'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('inspection_values', 'is_required');
    }
}
