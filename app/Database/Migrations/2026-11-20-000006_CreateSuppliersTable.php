<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSuppliersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'address'    => ['type' => 'TEXT', 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],   // opsional (VR-23)
            'email'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],  // opsional (VR-24)
            'created_at' => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'updated_at' => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('name', false, false, 'idx_suppliers_name');
        $this->forge->createTable('suppliers');
    }

    public function down()
    {
        $this->forge->dropTable('suppliers', true);
    }
}
