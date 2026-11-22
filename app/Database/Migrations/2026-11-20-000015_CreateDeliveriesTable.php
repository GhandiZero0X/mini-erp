<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/** Header Delivery (ERD: deliveries). Satu SO dapat memiliki banyak Delivery (pengiriman bertahap). */
class CreateDeliveriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'delivery_number' => ['type' => 'VARCHAR', 'constraint' => 20],             // otomatis, format DL-YYYYMM-0001
            'sales_order_id'  => ['type' => 'INT'],                                     // harus SO berstatus Open (VR-29)
            'delivery_date'   => ['type' => 'DATE', 'default' => new RawSql('CURRENT_DATE')],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'Aktif'], // Aktif | Dibatalkan
            'created_by'      => ['type' => 'INT'],
            'created_at'      => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'cancelled_by'    => ['type' => 'INT', 'null' => true],
            'cancelled_at'    => ['type' => 'TIMESTAMPTZ', 'null' => true],
            'cancel_reason'   => ['type' => 'TEXT', 'null' => true],
            'updated_at'      => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('sales_order_id', 'sales_orders', 'id', 'RESTRICT', 'RESTRICT', 'fk_dl_so');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_dl_created_by');
        $this->forge->addForeignKey('cancelled_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_dl_cancelled_by');
        $this->forge->createTable('deliveries');

        $this->db->query('ALTER TABLE deliveries ADD CONSTRAINT uq_delivery_number UNIQUE (delivery_number)');
        $this->db->query('CREATE INDEX idx_dl_sales_order_id ON deliveries (sales_order_id)');
        $this->db->query('CREATE INDEX idx_dl_delivery_date ON deliveries (delivery_date)');
        $this->db->query('CREATE INDEX idx_dl_status ON deliveries (status)');

        $this->db->query("ALTER TABLE deliveries ADD CONSTRAINT chk_dl_status CHECK (status IN ('Aktif','Dibatalkan'))");
        $this->db->query("ALTER TABLE deliveries ADD CONSTRAINT chk_dl_cancel CHECK (status <> 'Dibatalkan' OR (cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL))");
    }

    public function down()
    {
        $this->forge->dropTable('deliveries', true);
    }
}
