<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Extends the vehicles table into a full vehicle profile (Condition-Report
 * style) plus licence-disc fields captured by the PDF417 scanner.
 */
class ExtendVehicles extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('vehicles', [
            // Classification / spec
            'vehicle_type'         => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'model'],
            'derivative'           => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true, 'after' => 'vehicle_type'],
            'body_type'            => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'derivative'],
            'doors'                => ['type' => 'TINYINT', 'constraint' => 3, 'null' => true, 'after' => 'body_type'],
            'engine_number'        => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'doors'],
            'power_displacement'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'engine_number'],
            'fuel'                 => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'power_displacement'],
            'transmission'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'fuel'],
            'paint_finish'         => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'transmission'],
            'options'              => ['type' => 'TEXT', 'null' => true, 'after' => 'paint_finish'],

            // Registration / licence (disc)
            'registered_owner'     => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'options'],
            'register_number'      => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'registered_owner'],
            'licence_no'           => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'register_number'],
            'disc_control_no'      => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'licence_no'],
            'date_first_licensing' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'disc_control_no'],
            'last_cor'             => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'date_first_licensing'],
            'licence_expiry'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'last_cor'],
            'gvm'                  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'licence_expiry'],
            'tare'                 => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'gvm'],
            'seating'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'tare'],
            'date_of_test'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'seating'],

            // Assignment / context
            'client'               => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'date_of_test'],
            'inspection_location'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'client'],

            // Raw decoded barcode (never lose data)
            'disc_raw'             => ['type' => 'TEXT', 'null' => true, 'after' => 'inspection_location'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('vehicles', [
            'vehicle_type', 'derivative', 'body_type', 'doors', 'engine_number',
            'power_displacement', 'fuel', 'transmission', 'paint_finish', 'options',
            'registered_owner', 'register_number', 'licence_no', 'disc_control_no',
            'date_first_licensing', 'last_cor', 'licence_expiry', 'gvm', 'tare',
            'seating', 'date_of_test', 'client', 'inspection_location', 'disc_raw',
        ]);
    }
}
