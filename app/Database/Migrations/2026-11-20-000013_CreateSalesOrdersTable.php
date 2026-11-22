<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/** Header Sales Order (ERD: sales_orders). Status: Open | Dibatalkan. SO tidak mereservasi stok. */
class CreateSalesOrdersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'so_number'     => ['type' => 'VARCHAR', 'constraint' => 20],               // otomatis, format SO-YYYYMM-0001
            'so_date'       => ['type' => 'DATE', 'default' => new RawSql('CURRENT_DATE')],
            'customer_id'   => ['type' => 'INT'],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'Open'],
            'total_amount'  => ['type' => 'NUMERIC', 'constraint' => '15,2', 'default' => 0], // dihitung server (BR-10)
            'created_by'    => ['type' => 'INT'],
            'created_at'    => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'cancelled_by'  => ['type' => 'INT', 'null' => true],
            'cancelled_at'  => ['type' => 'TIMESTAMPTZ', 'null' => true],
            'cancel_reason' => ['type' => 'TEXT', 'null' => true],                      // wajib saat dibatalkan (VR-13)
            'updated_at'    => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_customer');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_created_by');
        $this->forge->addForeignKey('cancelled_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_cancelled_by');
        $this->forge->createTable('sales_orders');

        $this->db->query('ALTER TABLE sales_orders ADD CONSTRAINT uq_so_number UNIQUE (so_number)');
        $this->db->query('CREATE INDEX idx_so_so_date ON sales_orders (so_date)');
        $this->db->query('CREATE INDEX idx_so_status ON sales_orders (status)');
        $this->db->query('CREATE INDEX idx_so_customer_id ON sales_orders (customer_id)');

        $this->db->query("ALTER TABLE sales_orders ADD CONSTRAINT chk_so_status CHECK (status IN ('Open','Dibatalkan'))");
        $this->db->query('ALTER TABLE sales_orders ADD CONSTRAINT chk_so_total CHECK (total_amount >= 0)');
        $this->db->query("ALTER TABLE sales_orders ADD CONSTRAINT chk_so_cancel CHECK (status <> 'Dibatalkan' OR (cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL))");
    }

    public function down()
    {
        $this->forge->dropTable('sales_orders', true);
    }
}
