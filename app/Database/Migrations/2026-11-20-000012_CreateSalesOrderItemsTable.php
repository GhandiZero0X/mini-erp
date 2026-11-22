<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSalesOrderItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'sales_order_id' => ['type' => 'INT'],
            'product_id'     => ['type' => 'INT'],
            'quantity'       => ['type' => 'INT'],
            'unit_price'     => ['type' => 'NUMERIC', 'constraint' => '15,2'],
            'subtotal'       => ['type' => 'NUMERIC', 'constraint' => '15,2'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['sales_order_id', 'product_id'], 'uq_so_items_so_product');
        $this->forge->addKey('product_id', false, false, 'idx_so_items_product_id');
        $this->forge->addForeignKey('sales_order_id', 'sales_orders', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_items_so');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_items_product');
        $this->forge->createTable('sales_order_items');

        $this->db->query('ALTER TABLE sales_order_items ADD CONSTRAINT chk_so_items_qty CHECK (quantity > 0)');
        $this->db->query('ALTER TABLE sales_order_items ADD CONSTRAINT chk_so_items_price CHECK (unit_price >= 0)');
        $this->db->query('ALTER TABLE sales_order_items ADD CONSTRAINT chk_so_items_subtotal CHECK (subtotal >= 0)');
    }

    public function down()
    {
        $this->forge->dropTable('sales_order_items', true);
    }
}
