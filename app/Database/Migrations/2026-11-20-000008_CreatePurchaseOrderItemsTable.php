<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchaseOrderItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'purchase_order_id' => ['type' => 'INT'],
            'product_id'        => ['type' => 'INT'],
            'quantity'          => ['type' => 'INT'],
            'unit_price'        => ['type' => 'NUMERIC', 'constraint' => '15,2'],
            'subtotal'          => ['type' => 'NUMERIC', 'constraint' => '15,2'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['purchase_order_id', 'product_id'], 'uq_po_items_po_product');
        $this->forge->addKey('product_id', false, false, 'idx_po_items_product_id');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'RESTRICT', 'RESTRICT', 'fk_po_items_po');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT', 'fk_po_items_product');
        $this->forge->createTable('purchase_order_items');

        $this->db->query('ALTER TABLE purchase_order_items ADD CONSTRAINT chk_po_items_qty CHECK (quantity > 0)');
        $this->db->query('ALTER TABLE purchase_order_items ADD CONSTRAINT chk_po_items_price CHECK (unit_price >= 0)');
        $this->db->query('ALTER TABLE purchase_order_items ADD CONSTRAINT chk_po_items_subtotal CHECK (subtotal >= 0)');
    }

    public function down()
    {
        $this->forge->dropTable('purchase_order_items', true);
    }
}
