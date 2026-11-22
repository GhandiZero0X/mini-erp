<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeliveryItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'auto_increment' => true],
            'delivery_id'         => ['type' => 'INT'],
            'sales_order_item_id' => ['type' => 'INT'],
            'quantity_delivered'  => ['type' => 'INT'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['delivery_id', 'sales_order_item_id'], 'uq_dl_items_dl_soitem');
        $this->forge->addKey('sales_order_item_id', false, false, 'idx_dl_items_so_item_id');
        $this->forge->addForeignKey('delivery_id', 'deliveries', 'id', 'RESTRICT', 'RESTRICT', 'fk_dl_items_dl');
        $this->forge->addForeignKey('sales_order_item_id', 'sales_order_items', 'id', 'RESTRICT', 'RESTRICT', 'fk_dl_items_so_item');
        $this->forge->createTable('delivery_items');

        $this->db->query('ALTER TABLE delivery_items ADD CONSTRAINT chk_dl_items_qty CHECK (quantity_delivered > 0)');
    }

    public function down()
    {
        $this->forge->dropTable('delivery_items', true);
    }
}
