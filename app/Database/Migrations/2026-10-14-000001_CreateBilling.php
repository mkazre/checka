<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Clients, invoices, invoice line items and payments, plus the client link
 * and charge fields on inspections.
 */
class CreateBilling extends Migration
{
    public function up(): void
    {
        $money = ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0];

        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'uid'                 => ['type' => 'VARCHAR', 'constraint' => 40],
            'type'                => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'business'],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 160],
            'contact_person'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'phone'               => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'email'               => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'billing_email'       => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'address'             => ['type' => 'TEXT', 'null' => true],
            'vat_number'          => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'registration_number' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'payment_terms_days'  => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'monthly_statements'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_statement_period' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'created_by'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uid');
        $this->forge->addKey('name');
        $this->forge->createTable('clients', true);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'uid'           => ['type' => 'VARCHAR', 'constraint' => 40],
            'invoice_no'    => ['type' => 'VARCHAR', 'constraint' => 40],
            'client_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'inspection_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'draft'],
            'issue_date'    => ['type' => 'DATE'],
            'due_date'      => ['type' => 'DATE'],
            'subtotal'      => $money,
            'vat_rate'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'vat_amount'    => $money,
            'total'         => $money,
            'amount_paid'   => $money,
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'sent_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_by'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uid');
        $this->forge->addUniqueKey('invoice_no');
        $this->forge->addKey('client_id');
        $this->forge->addKey('inspection_id');
        $this->forge->createTable('invoices', true);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'invoice_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'qty'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1],
            'unit_price'  => $money,
            'line_total'  => $money,
            'sort'        => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('invoice_id');
        $this->forge->createTable('invoice_items', true);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'client_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'invoice_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'amount'      => $money,
            'method'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'eft'],
            'reference'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'gateway_ref' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'paid_at'     => ['type' => 'DATE'],
            'notes'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'recorded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('client_id');
        $this->forge->addKey('invoice_id');
        $this->forge->addKey('gateway_ref');
        $this->forge->createTable('payments', true);

        $this->forge->addColumn('inspections', [
            'client_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'vehicle_id'],
            'charge_description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'charge_amount'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('inspections', ['client_id', 'charge_description', 'charge_amount']);
        $this->forge->dropTable('payments', true);
        $this->forge->dropTable('invoice_items', true);
        $this->forge->dropTable('invoices', true);
        $this->forge->dropTable('clients', true);
    }
}
