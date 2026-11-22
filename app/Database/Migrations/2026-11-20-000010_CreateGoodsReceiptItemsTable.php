<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGoodsReceiptItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'auto_increment' => true],
            'goods_receipt_id'       => ['type' => 'INT'],
            'purchase_order_item_id' => ['type' => 'INT'],
            'quantity_received'      => ['type' => 'INT'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['goods_receipt_id', 'purchase_order_item_id'], 'uq_gr_items_gr_poitem');
        $this->forge->addKey('purchase_order_item_id', false, false, 'idx_gr_items_po_item_id');
        $this->forge->addForeignKey('goods_receipt_id', 'goods_receipts', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_items_gr');
        $this->forge->addForeignKey('purchase_order_item_id', 'purchase_order_items', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_items_po_item');
        $this->forge->createTable('goods_receipt_items');

        $this->db->query('ALTER TABLE goods_receipt_items ADD CONSTRAINT chk_gr_items_qty CHECK (quantity_received > 0)');
    }

    public function down()
    {
        $this->forge->dropTable('goods_receipt_items', true);
    }
}
