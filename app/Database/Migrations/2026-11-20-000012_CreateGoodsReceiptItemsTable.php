<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Detail Goods Receipt (ERD: goods_receipt_items). Produk diturunkan dari item PO; hanya baris > 0 disimpan (VR-26). */
class CreateGoodsReceiptItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'auto_increment' => true],
            'goods_receipt_id'       => ['type' => 'INT'],
            'purchase_order_item_id' => ['type' => 'INT'],
            'quantity_received'      => ['type' => 'INT'],                              // VR-11
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('goods_receipt_id', 'goods_receipts', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_items_gr');
        $this->forge->addForeignKey('purchase_order_item_id', 'purchase_order_items', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_items_po_item');
        $this->forge->createTable('goods_receipt_items');

        $this->db->query('CREATE UNIQUE INDEX uq_gr_items_gr_poitem ON goods_receipt_items (goods_receipt_id, purchase_order_item_id)');
        $this->db->query('CREATE INDEX idx_gr_items_po_item ON goods_receipt_items (purchase_order_item_id)');

        $this->db->query('ALTER TABLE goods_receipt_items ADD CONSTRAINT chk_gr_items_qty CHECK (quantity_received > 0)');
    }

    public function down()
    {
        $this->forge->dropTable('goods_receipt_items', true);
    }
}
