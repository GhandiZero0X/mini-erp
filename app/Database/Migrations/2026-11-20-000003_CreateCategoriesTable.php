<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'updated_at' => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name', 'uq_categories_name');
        $this->forge->createTable('categories');
    }

    public function down()
    {
        $this->forge->dropTable('categories', true);
    }
}
