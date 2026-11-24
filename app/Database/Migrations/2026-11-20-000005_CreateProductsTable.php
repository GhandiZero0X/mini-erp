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
            'unit_id'        => ['type' => 'INT'],
            'purchase_price' => ['type' => 'NUMERIC', 'constraint' => '15,2', 'default' => 0],
            'selling_price'  => ['type' => 'NUMERIC', 'constraint' => '15,2', 'default' => 0],
            'category_id'    => ['type' => 'INT'],
            'min_stock'      => ['type' => 'INT', 'default' => 0],
            'max_stock'      => ['type' => 'INT', 'null' => true],
            'created_at'     => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
            'updated_at'     => ['type' => 'TIMESTAMPTZ', 'default' => new RawSql('now()')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code', 'uq_products_code');
        $this->forge->addKey('category_id', false, false, 'idx_products_category_id');
        $this->forge->addKey('unit_id', false, false, 'idx_products_unit_id');
        $this->forge->addKey('name', false, false, 'idx_products_name');

        $this->forge->addForeignKey('category_id', 'categories', 'id', 'RESTRICT', 'RESTRICT', 'fk_products_category');
        $this->forge->addForeignKey('unit_id', 'units', 'id', 'RESTRICT', 'RESTRICT', 'fk_products_unit');

        $this->forge->createTable('products');

        // Constraint & index non-forge (functional index, CHECK)
        $this->db->query('CREATE UNIQUE INDEX uq_products_code_lower ON products (lower(code))');
        $this->db->query('ALTER TABLE products ADD CONSTRAINT chk_products_purchase_price CHECK (purchase_price >= 0)');
        $this->db->query('ALTER TABLE products ADD CONSTRAINT chk_products_selling_price CHECK (selling_price >= 0)');
        $this->db->query('ALTER TABLE products ADD CONSTRAINT chk_products_min_stock CHECK (min_stock >= 0)');
        $this->db->query('ALTER TABLE products ADD CONSTRAINT chk_products_max_stock CHECK (max_stock IS NULL OR max_stock >= min_stock)');
    }

    public function down()
    {
        $this->forge->dropTable('products', true);
    }
}