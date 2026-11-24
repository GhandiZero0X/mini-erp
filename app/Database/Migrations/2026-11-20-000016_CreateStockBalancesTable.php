<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateStockBalancesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            // PK sekaligus FK ke products (relasi 1:1), jadi tidak auto_increment
            'product_id' => ['type' => 'INT'],
            'quantity'   => ['type' => 'INT', 'default' => 0],
            'updated_at' => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT', 'fk_stock_balances_product');
        $this->forge->createTable('stock_balances');

        // BR-07: pengaman terakhir agar stok tidak pernah negatif
        $this->db->query('ALTER TABLE stock_balances ADD CONSTRAINT chk_stock_balances_qty_nonneg CHECK (quantity >= 0)');
    }

    public function down()
    {
        $this->forge->dropTable('stock_balances', true);
    }
}
