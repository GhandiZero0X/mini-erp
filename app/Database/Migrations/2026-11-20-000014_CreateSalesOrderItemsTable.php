<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Detail Sales Order (ERD: sales_order_items). Satu produk hanya boleh satu baris per SO. */
class CreateSalesOrderItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'sales_order_id' => ['type' => 'INT'],
            'product_id'     => ['type' => 'INT'],
            'quantity'       => ['type' => 'INT'],                                      // VR-08
            'unit_price'     => ['type' => 'NUMERIC', 'constraint' => '15,2'],          // VR-09
            'subtotal'       => ['type' => 'NUMERIC', 'constraint' => '15,2'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('sales_order_id', 'sales_orders', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_items_so');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_items_product');
        $this->forge->createTable('sales_order_items');

        $this->db->query('CREATE UNIQUE INDEX uq_so_items_so_product ON sales_order_items (sales_order_id, product_id)');
        $this->db->query('CREATE INDEX idx_so_items_product_id ON sales_order_items (product_id)');

        $this->db->query('ALTER TABLE sales_order_items ADD CONSTRAINT chk_so_items_qty CHECK (quantity > 0)');
        $this->db->query('ALTER TABLE sales_order_items ADD CONSTRAINT chk_so_items_price CHECK (unit_price >= 0)');
        $this->db->query('ALTER TABLE sales_order_items ADD CONSTRAINT chk_so_items_subtotal CHECK (subtotal >= 0)');
    }

    public function down()
    {
        $this->forge->dropTable('sales_order_items', true);
    }
}
