<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Vendors (suppliers), purchase orders and their line items. */
class CreatePurchasing extends Migration
{
    public function up(): void
    {
        $str = static fn (int $n) => ['type' => 'VARCHAR', 'constraint' => $n, 'null' => true];
        $ts  = [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ];

        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'uid'                 => ['type' => 'VARCHAR', 'constraint' => 40],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 160],
            'contact_person'      => $str(120),
            'phone'               => $str(40),
            'email'               => $str(160),
            'address'             => ['type' => 'TEXT', 'null' => true],
            'vat_number'          => $str(40),
            'registration_number' => $str(60),
            'account_number'      => $str(60),
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'created_by'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ] + $ts);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uid');
        $this->forge->createTable('vendors', true);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'uid'           => ['type' => 'VARCHAR', 'constraint' => 40],
            'po_no'         => ['type' => 'VARCHAR', 'constraint' => 40],
            'vendor_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'draft'],
            'order_date'    => ['type' => 'DATE'],
            'description'   => $str(255),
            'reference'     => $str(80),
            'show_pricing'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'show_vehicle'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'vehicle_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'veh_make'      => $str(80),
            'veh_model'     => $str(120),
            'veh_year'      => $str(10),
            'veh_vin'       => $str(40),
            'veh_plate'     => $str(30),
            'veh_colour'    => $str(40),
            'veh_engine'    => $str(80),
            'veh_odometer'  => $str(30),
            'vat_rate'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 15],
            'subtotal'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'vat_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'total'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'sent_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_by'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ] + $ts);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uid');
        $this->forge->addUniqueKey('po_no');
        $this->forge->addKey('vendor_id');
        $this->forge->addKey('order_date');
        $this->forge->createTable('purchase_orders', true);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'po_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'part_no'     => $str(60),
            'qty'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1],
            'unit_price'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'sort'        => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('po_id');
        $this->forge->createTable('purchase_order_items', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('purchase_order_items', true);
        $this->forge->dropTable('purchase_orders', true);
        $this->forge->dropTable('vendors', true);
    }
}
