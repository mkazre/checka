<?php

namespace Platform\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SaaS control-plane tables (prefix pf_ via the 'platform' DB group):
 * platform admins, packages, tenants (workspaces), settings, activity.
 * Run with:  php spark migrate -n Platform
 */
class CreatePlatform extends Migration
{
    protected $DBGroup = 'platform';

    public function up(): void
    {
        $ts = [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
        $str = static fn (int $n, bool $null = true) => ['type' => 'VARCHAR', 'constraint' => $n, 'null' => $null];

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'          => $str(120, false),
            'email'         => $str(160, false),
            'password_hash' => $str(255, false),
            'role'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'owner'],
            'active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $ts);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('admins', true);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'          => $str(80, false),
            'slug'          => $str(80, false),
            'tagline'       => $str(160),
            'description'   => ['type' => 'TEXT', 'null' => true],
            'price_monthly' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'price_annual'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'trial_days'    => ['type' => 'INT', 'constraint' => 4, 'default' => 0],
            'modules'       => ['type' => 'TEXT', 'null' => true],
            'limits'        => ['type' => 'TEXT', 'null' => true],
            'is_public'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'highlight'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort'          => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
        ] + $ts);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('packages', true);

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'slug'               => $str(40, false),
            'company_name'       => $str(160, false),
            'contact_name'       => $str(120),
            'email'              => $str(160),
            'phone'              => $str(40),
            'vat_number'         => $str(40),
            'package_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'billing_cycle'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'monthly'],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'provisioning'],
            'trial_ends_at'      => ['type' => 'DATETIME', 'null' => true],
            'current_period_end' => ['type' => 'DATETIME', 'null' => true],
            'db_mode'            => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'prefix'],
            'db_prefix'          => $str(40),
            'extra_modules'      => ['type' => 'TEXT', 'null' => true],
            'limit_overrides'    => ['type' => 'TEXT', 'null' => true],
            'pending_admin'      => ['type' => 'TEXT', 'null' => true],
            'provisioned_at'     => ['type' => 'DATETIME', 'null' => true],
            'provision_error'    => ['type' => 'TEXT', 'null' => true],
            'suspended_at'       => ['type' => 'DATETIME', 'null' => true],
            'cancelled_at'       => ['type' => 'DATETIME', 'null' => true],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ] + $ts);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->createTable('tenants', true);

        $this->forge->addField([
            'key'        => $str(80, false),
            'value'      => ['type' => 'MEDIUMTEXT', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('key', true);
        $this->forge->createTable('settings', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'type'       => $str(40, false),
            'message'    => ['type' => 'TEXT', 'null' => true],
            'actor'      => $str(120),
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('events', true);
    }

    public function down(): void
    {
        foreach (['events', 'settings', 'tenants', 'packages', 'admins'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
