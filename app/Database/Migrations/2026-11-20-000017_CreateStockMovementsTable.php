<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateStockMovementsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'auto_increment' => true],     // -> BIGSERIAL
            'product_id'       => ['type' => 'INT'],
            'movement_type'    => ['type' => 'VARCHAR', 'constraint' => 15],           // masuk | keluar | penyesuaian
            'quantity_change'  => ['type' => 'INT'],                                   // bertanda (+/-)
            'movement_date'    => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'reference_type'   => ['type' => 'VARCHAR', 'constraint' => 30],           // GOODS_RECEIPT, DELIVERY, dst.
            'reference_id'     => ['type' => 'INT', 'null' => true],                   // generik, tanpa FK (NFR-19)
            'reference_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'notes'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'       => ['type' => 'INT'],
            'created_at'       => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['product_id', 'movement_date'], false, false, 'idx_stock_mov_product_date');
        $this->forge->addKey(['reference_type', 'reference_id'], false, false, 'idx_stock_mov_reference');
        $this->forge->addKey('movement_date', false, false, 'idx_stock_mov_date');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT', 'fk_stock_mov_product');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_stock_mov_user');
        $this->forge->createTable('stock_movements');

        $this->db->query("ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_mov_type CHECK (movement_type IN ('masuk','keluar','penyesuaian'))");
        $this->db->query('ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_mov_nonzero CHECK (quantity_change <> 0)');
        $this->db->query("ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_mov_masuk CHECK (movement_type <> 'masuk' OR quantity_change > 0)");
        $this->db->query("ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_mov_keluar CHECK (movement_type <> 'keluar' OR quantity_change < 0)");
    }

    public function down()
    {
        $this->forge->dropTable('stock_movements', true);
    }
}
