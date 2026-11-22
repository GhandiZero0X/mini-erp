<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Detail Purchase Order (ERD: purchase_order_items). Satu produk hanya boleh satu baris per PO. */
class CreatePurchaseOrderItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'purchase_order_id' => ['type' => 'INT'],
            'product_id'        => ['type' => 'INT'],
            'quantity'          => ['type' => 'INT'],                                   // VR-08, bilangan bulat > 0
            'unit_price'        => ['type' => 'NUMERIC', 'constraint' => '15,2'],       // harga saat transaksi (VR-09)
            'subtotal'          => ['type' => 'NUMERIC', 'constraint' => '15,2'],       // quantity x unit_price
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'RESTRICT', 'RESTRICT', 'fk_po_items_po');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT', 'fk_po_items_product');
        $this->forge->createTable('purchase_order_items');

        $this->db->query('CREATE UNIQUE INDEX uq_po_items_po_product ON purchase_order_items (purchase_order_id, product_id)');
        $this->db->query('CREATE INDEX idx_po_items_product_id ON purchase_order_items (product_id)');

        $this->db->query('ALTER TABLE purchase_order_items ADD CONSTRAINT chk_po_items_qty CHECK (quantity > 0)');
        $this->db->query('ALTER TABLE purchase_order_items ADD CONSTRAINT chk_po_items_price CHECK (unit_price >= 0)');
        $this->db->query('ALTER TABLE purchase_order_items ADD CONSTRAINT chk_po_items_subtotal CHECK (subtotal >= 0)');
    }

    public function down()
    {
        $this->forge->dropTable('purchase_order_items', true);
    }
}
