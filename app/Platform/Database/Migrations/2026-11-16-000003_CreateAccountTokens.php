<?php

namespace Platform\Database\Migrations;

use CodeIgniter\Database\Migration;

/** One-time tokens for customer password resets (sent by email from the central sign-in page). */
class CreateAccountTokens extends Migration
{
    protected $DBGroup = 'platform';

    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 160],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'purpose'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'reset'],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'ip'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('email');
        $this->forge->createTable('account_tokens', true);
    }

    public function down()
    {
        $this->forge->dropTable('account_tokens', true);
    }
}
