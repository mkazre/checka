<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStockImage extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('vehicles', [
            'stock_image_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'after'      => 'disc_raw',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('vehicles', 'stock_image_url');
    }
}
