<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/** Header Goods Receipt (ERD: goods_receipts). Satu PO dapat memiliki banyak GR (penerimaan bertahap). */
class CreateGoodsReceiptsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'gr_number'         => ['type' => 'VARCHAR', 'constraint' => 20],           // otomatis, format GR-YYYYMM-0001
            'purchase_order_id' => ['type' => 'INT'],                                   // harus PO berstatus Approved (VR-10)
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
        $this->forge->addForeignKey('purchase_order_id', 'purchase_orders', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_po');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_created_by');
        $this->forge->addForeignKey('cancelled_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_gr_cancelled_by');
        $this->forge->createTable('goods_receipts');

        $this->db->query('ALTER TABLE goods_receipts ADD CONSTRAINT uq_gr_number UNIQUE (gr_number)');
        $this->db->query('CREATE INDEX idx_gr_purchase_order_id ON goods_receipts (purchase_order_id)');
        $this->db->query('CREATE INDEX idx_gr_receipt_date ON goods_receipts (receipt_date)');
        $this->db->query('CREATE INDEX idx_gr_status ON goods_receipts (status)');

        $this->db->query("ALTER TABLE goods_receipts ADD CONSTRAINT chk_gr_status CHECK (status IN ('Aktif','Dibatalkan'))");
        $this->db->query("ALTER TABLE goods_receipts ADD CONSTRAINT chk_gr_cancel CHECK (status <> 'Dibatalkan' OR (cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL))");
    }

    public function down()
    {
        $this->forge->dropTable('goods_receipts', true);
    }
}
