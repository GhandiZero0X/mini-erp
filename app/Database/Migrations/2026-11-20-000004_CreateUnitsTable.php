<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateUnitsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'created_at' => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'updated_at' => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code', 'uq_units_code');
        $this->forge->addKey('name', false, false, 'idx_units_name');
        $this->forge->createTable('units');

        // Seed satuan default (agar produk lama punya referensi)
        $now = date('Y-m-d H:i:s');
        $this->db->table('units')->insertBatch([
            ['code' => 'PCS', 'name' => 'Pieces',  'created_at' => $now, 'updated_at' => $now],
            ['code' => 'KG',  'name' => 'Kilogram','created_at' => $now, 'updated_at' => $now],
            ['code' => 'BOX', 'name' => 'Box',     'created_at' => $now, 'updated_at' => $now],
            ['code' => 'LTR', 'name' => 'Liter',   'created_at' => $now, 'updated_at' => $now],
            ['code' => 'MTR', 'name' => 'Meter',   'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('units', true);
    }
}