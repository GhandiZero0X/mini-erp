<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'   => ['type' => 'INT', 'auto_increment' => true],        // -> SERIAL di PostgreSQL
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],          // admin, purchasing, gudang, sales, manajer
            'name' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code', 'uq_roles_code');
        $this->forge->createTable('roles');
    }

    public function down()
    {
        $this->forge->dropTable('roles', true);
    }
}
