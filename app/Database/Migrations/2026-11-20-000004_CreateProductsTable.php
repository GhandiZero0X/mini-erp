<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateProductsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'code'           => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'unit'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'purchase_price' => ['type' => 'NUMERIC', 'constraint' => '15,2', 'default' => 0],
            'selling_price'  => ['type' => 'NUMERIC', 'constraint' => '15,2', 'default' => 0],
            'category_id'    => ['type' => 'INT'],
            'created_at'     => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'updated_at'     => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code', 'uq_products_code');
        $this->forge->addKey('category_id', false, false, 'idx_products_category_id');
        $this->forge->addKey('name', false, false, 'idx_products_name');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'RESTRICT', 'RESTRICT', 'fk_products_category');
        $this->forge->createTable('products');

        // Forge tidak mendukung functional index & CHECK, jadi pakai SQL langsung.
        $this->db->query('CREATE UNIQUE INDEX uq_products_code_lower ON products (lower(code))');
        $this->db->query('ALTER TABLE products ADD CONSTRAINT chk_products_purchase_price CHECK (purchase_price >= 0)');
        $this->db->query('ALTER TABLE products ADD CONSTRAINT chk_products_selling_price CHECK (selling_price >= 0)');
    }

    public function down()
    {
        $this->forge->dropTable('products', true);
    }
}
