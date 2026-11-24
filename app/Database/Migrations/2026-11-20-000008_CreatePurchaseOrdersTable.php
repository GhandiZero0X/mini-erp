<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePurchaseOrdersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'po_number'    => ['type' => 'VARCHAR', 'constraint' => 20],               // PO-YYYYMM-0001
            'po_date'      => ['type' => 'DATE', 'default' => new RawSql('CURRENT_DATE')],
            'supplier_id'  => ['type' => 'INT'],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'Draft'], // Draft | Approved | Dibatalkan
            'total_amount' => ['type' => 'NUMERIC', 'constraint' => '15,2', 'default' => 0],
            'created_by'   => ['type' => 'INT'],
            'created_at'   => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'approved_by'  => ['type' => 'INT', 'null' => true],
            'approved_at'  => ['type' => 'TIMESTAMPTZ', 'null' => true],
            'cancelled_by' => ['type' => 'INT', 'null' => true],
            'cancelled_at' => ['type' => 'TIMESTAMPTZ', 'null' => true],
            'cancel_reason'=> ['type' => 'TEXT', 'null' => true],
            'updated_at'   => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('po_number', 'uq_purchase_orders_po_number');
        $this->forge->addKey('po_date', false, false, 'idx_po_po_date');
        $this->forge->addKey('status', false, false, 'idx_po_status');
        $this->forge->addKey('supplier_id', false, false, 'idx_po_supplier_id');
        $this->forge->addForeignKey('supplier_id', 'suppliers', 'id', 'RESTRICT', 'RESTRICT', 'fk_po_supplier');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_po_created_by');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_po_approved_by');
        $this->forge->addForeignKey('cancelled_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_po_cancelled_by');
        $this->forge->createTable('purchase_orders');

        $this->db->query("ALTER TABLE purchase_orders ADD CONSTRAINT chk_po_status CHECK (status IN ('Draft','Approved','Dibatalkan'))");
        $this->db->query('ALTER TABLE purchase_orders ADD CONSTRAINT chk_po_total CHECK (total_amount >= 0)');
        $this->db->query("ALTER TABLE purchase_orders ADD CONSTRAINT chk_po_approval CHECK (status <> 'Approved' OR (approved_by IS NOT NULL AND approved_at IS NOT NULL))");
        $this->db->query("ALTER TABLE purchase_orders ADD CONSTRAINT chk_po_cancel CHECK (status <> 'Dibatalkan' OR (cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL))");
    }

    public function down()
    {
        $this->forge->dropTable('purchase_orders', true);
    }
}
