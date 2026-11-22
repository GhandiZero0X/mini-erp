<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Detail Delivery (ERD: delivery_items). Produk diturunkan dari item SO; hanya baris > 0 disimpan (VR-31). */
class CreateDeliveryItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'auto_increment' => true],
            'delivery_id'         => ['type' => 'INT'],
            'sales_order_item_id' => ['type' => 'INT'],
            'quantity_delivered'  => ['type' => 'INT'],                                 // VR-30
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('delivery_id', 'deliveries', 'id', 'RESTRICT', 'RESTRICT', 'fk_dl_items_dl');
        $this->forge->addForeignKey('sales_order_item_id', 'sales_order_items', 'id', 'RESTRICT', 'RESTRICT', 'fk_dl_items_so_item');
        $this->forge->createTable('delivery_items');

        $this->db->query('CREATE UNIQUE INDEX uq_dl_items_dl_soitem ON delivery_items (delivery_id, sales_order_item_id)');
        $this->db->query('CREATE INDEX idx_dl_items_so_item ON delivery_items (sales_order_item_id)');

        $this->db->query('ALTER TABLE delivery_items ADD CONSTRAINT chk_dl_items_qty CHECK (quantity_delivered > 0)');
    }

    public function down()
    {
        $this->forge->dropTable('delivery_items', true);
    }
}
