<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StockBalanceSeeder extends Seeder
{
    public function run()
    {
        // Satu baris stock_balances (quantity 0) untuk setiap produk yang belum punya.
        // Idempotent: aman dijalankan berulang kali.
        $this->db->query(
            'INSERT INTO stock_balances (product_id, quantity)
             SELECT p.id, 0 FROM products p
             ON CONFLICT (product_id) DO NOTHING'
        );
    }
}
