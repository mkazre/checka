<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiSummary extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('inspections', [
            'ai_summary' => ['type' => 'TEXT', 'null' => true], // JSON: {summary, triage[]}
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('inspections', 'ai_summary');
    }
}
