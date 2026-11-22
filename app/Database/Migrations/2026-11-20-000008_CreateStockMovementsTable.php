<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Riwayat mutasi stok (ERD: stock_movements). Append-only: tidak ada UPDATE/DELETE (BR-06, BR-09).
 * SUM(quantity_change) per produk harus sama dengan stock_balances.quantity (NFR-09).
 */
class CreateStockMovementsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'auto_increment' => true],      // -> BIGSERIAL
            'product_id'       => ['type' => 'INT'],
            'movement_type'    => ['type' => 'VARCHAR', 'constraint' => 15],           // masuk | keluar | penyesuaian
            'quantity_change'  => ['type' => 'INT'],                                   // bertanda: + menambah, - mengurangi
            'movement_date'    => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'reference_type'   => ['type' => 'VARCHAR', 'constraint' => 30],           // GOODS_RECEIPT | GOODS_RECEIPT_CANCEL | DELIVERY | DELIVERY_CANCEL | ADJUSTMENT
            'reference_id'     => ['type' => 'INT', 'null' => true],                   // generik, sengaja tanpa FK (NFR-19)
            'reference_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'notes'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'       => ['type' => 'INT'],
            'created_at'       => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT', 'fk_stock_mov_product');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_stock_mov_user');
        $this->forge->createTable('stock_movements');

        $this->db->query('CREATE INDEX idx_stock_mov_product_date ON stock_movements (product_id, movement_date)');
        $this->db->query('CREATE INDEX idx_stock_mov_reference ON stock_movements (reference_type, reference_id)');
        $this->db->query('CREATE INDEX idx_stock_mov_movement_date ON stock_movements (movement_date)');

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
