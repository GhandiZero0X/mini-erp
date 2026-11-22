<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateGoodsReceiptsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'gr_number'         => ['type' => 'VARCHAR', 'constraint' => 20],              // GR-YYYYMM-0001
            'purchase_order_id' => ['type' => 'INT'],
            'receipt_date'      => ['type' => 'DATE', 'default' => new RawSql('CURRENT_DATE')],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'Aktif'], // Aktif | Dibatalkan
            'created_by'        => ['type' => 'INT'],
            'created_at'        => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'cancelled_by'      => ['type' => 'INT', 'null' => true],
            'cancelled_at'      => ['type' => 'TIMESTAMPTZ', 'null' => true],
            'cancel_reason'     => ['type' => 'TEXT', 'null' => true],
            'updated_at'        => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('gr_number', 'uq_goods_receipts_gr_number');
        $this->forge->addKey('purchase_order_id', false, false, 'idx_gr_purchase_order_id');
        $this->forge->addKey('receipt_date', false, false, 'idx_gr_receipt_date');
        $this->forge->addKey('status', false, false, 'idx_gr_status');
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_po');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_created_by');
        $this->forge->addForeignKey('cancelled_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_cancelled_by');
        $this->forge->createTable('goods_receipts');

        $this->db->query("ALTER TABLE goods_receipts ADD CONSTRAINT chk_gr_status CHECK (status IN ('Aktif','Dibatalkan'))");
        $this->db->query("ALTER TABLE goods_receipts ADD CONSTRAINT chk_gr_cancel CHECK (status <> 'Dibatalkan' OR (cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL))");
    }

    public function down()
    {
        $this->forge->dropTable('goods_receipts', true);
    }
}
