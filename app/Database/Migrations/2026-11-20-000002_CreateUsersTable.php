<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'auto_increment' => true],
            'full_name'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'username'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'password_hash'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'role_id'            => ['type' => 'INT'],
            'is_active'          => ['type' => 'BOOLEAN', 'default' => new RawSql('true')],
            'failed_login_count' => ['type' => 'SMALLINT', 'default' => 0],
            'locked_until'       => ['type' => 'TIMESTAMPTZ', 'null' => true],
            'last_login_at'      => ['type' => 'TIMESTAMPTZ', 'null' => true],
            'created_at'         => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'updated_at'         => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username', 'uq_users_username');
        $this->forge->addKey('role_id', false, false, 'idx_users_role_id');
        $this->forge->addKey('is_active', false, false, 'idx_users_is_active');
        // addForeignKey(kolom, tabel_ref, kolom_ref, ON UPDATE, ON DELETE, nama)
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'RESTRICT', 'RESTRICT', 'fk_users_role');
        $this->forge->createTable('users');

        $this->db->query('ALTER TABLE users ADD CONSTRAINT chk_users_failed_login CHECK (failed_login_count >= 0)');
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}
