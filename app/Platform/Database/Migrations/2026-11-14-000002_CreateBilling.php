<?php

namespace Platform\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Subscription billing: invoices, payments, sent-notice log, website leads, plus subscription fields on tenants. */
class CreateBilling extends Migration
{
    protected $DBGroup = 'platform';

    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'uid'          => ['type' => 'VARCHAR', 'constraint' => 40],
            'number'       => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'tenant_id'    => ['type' => 'INT', 'unsigned' => true],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'renewal'], // first | renewal | upgrade | manual
            'description'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'package_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'billing_cycle' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'period_start' => ['type' => 'DATE', 'null' => true],
            'period_end'   => ['type' => 'DATE', 'null' => true],
            'subtotal'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'vat_rate'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'vat'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'total'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'unpaid'], // unpaid | paid | void
            'issue_date'   => ['type' => 'DATE'],
            'due_date'     => ['type' => 'DATE'],
            'paid_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uid');
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->createTable('invoices', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_id' => ['type' => 'INT', 'unsigned' => true],
            'tenant_id'  => ['type' => 'INT', 'unsigned' => true],
            'gateway'    => ['type' => 'VARCHAR', 'constraint' => 20], // paystack | payfast | ozow | eft | manual
            'reference'  => ['type' => 'VARCHAR', 'constraint' => 120],
            'amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'details'    => ['type' => 'TEXT', 'null' => true],
            'actor'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['gateway', 'reference']);
        $this->forge->createTable('payments', true);

        // One row per reminder/notice sent, so the daily job never sends the same one twice.
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'  => ['type' => 'INT', 'unsigned' => true],
            'notice_key' => ['type' => 'VARCHAR', 'constraint' => 120],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'notice_key']);
        $this->forge->createTable('notices', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'source'     => ['type' => 'VARCHAR', 'constraint' => 20], // contact | signup
            'name'       => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'company'    => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'topic'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'message'    => ['type' => 'TEXT', 'null' => true],
            'handled'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'ip'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('leads', true);

        $this->forge->addColumn('tenants', [
            'next_package_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'next_cycle'           => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'cancel_at_period_end' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'paystack_auth'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'paystack_email'       => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'card_label'           => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'signup_token'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
        ]);
    }

    public function down()
    {
        foreach (['next_package_id', 'next_cycle', 'cancel_at_period_end', 'paystack_auth', 'paystack_email', 'card_label', 'signup_token'] as $c) {
            $this->forge->dropColumn('tenants', $c);
        }
        $this->forge->dropTable('leads', true);
        $this->forge->dropTable('notices', true);
        $this->forge->dropTable('payments', true);
        $this->forge->dropTable('invoices', true);
    }
}
