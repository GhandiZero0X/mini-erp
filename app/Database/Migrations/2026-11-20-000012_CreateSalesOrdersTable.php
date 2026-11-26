<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSalesOrdersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'so_number'     => ['type' => 'VARCHAR', 'constraint' => 20],               // SO-YYYYMM-0001
            'so_date'       => ['type' => 'DATE', 'default' => new RawSql('CURRENT_DATE')],
            'customer_id'   => ['type' => 'INT'],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'Open'], // Open | Dibatalkan
            'total_amount'  => ['type' => 'NUMERIC', 'constraint' => '15,2', 'default' => 0],
            'created_by'    => ['type' => 'INT'],
            'created_at'    => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'cancelled_by'  => ['type' => 'INT', 'null' => true],
            'cancelled_at'  => ['type' => 'TIMESTAMPTZ', 'null' => true],
            'cancel_reason' => ['type' => 'TEXT', 'null' => true],
            'updated_at'    => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('so_number', 'uq_sales_orders_so_number');
        $this->forge->addKey('so_date', false, false, 'idx_so_so_date');
        $this->forge->addKey('status', false, false, 'idx_so_status');
        $this->forge->addKey('customer_id', false, false, 'idx_so_customer_id');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_customer');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_created_by');
        $this->forge->addForeignKey('cancelled_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_so_cancelled_by');
        $this->forge->createTable('sales_orders');

        $this->db->query("ALTER TABLE sales_orders ADD CONSTRAINT chk_so_status CHECK (status IN ('Open','Dibatalkan','Selesai'))");
        $this->db->query('ALTER TABLE sales_orders ADD CONSTRAINT chk_so_total CHECK (total_amount >= 0)');
        $this->db->query("ALTER TABLE sales_orders ADD CONSTRAINT chk_so_cancel CHECK (status <> 'Dibatalkan' OR (cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL))");
    }

    public function down()
    {
        $this->forge->dropTable('sales_orders', true);
    }
}