<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Urutan penting: tabel induk harus terisi sebelum anaknya.
        // Master data & autentikasi
        $this->call('RoleSeeder');
        $this->call('UserSeeder');
        $this->call('CategorySeeder');
        $this->call('UnitSeeder');
        $this->call('ProductSeeder');       // stok awal dicatat sebagai stock_movements
        $this->call('StockBalanceSeeder');
        $this->call('SupplierSeeder');
        $this->call('CustomerSeeder');

        // Transaksi (data contoh). Urutan: PO -> GR (stok masuk) -> SO -> Delivery (stok keluar)
        $this->call('PurchaseOrderSeeder');
        $this->call('GoodsReceiptSeeder');
        $this->call('SalesOrderSeeder');
        $this->call('DeliverySeeder');

        $this->verifyStockSync();
    }

    /** Saldo (stock_balances) harus sama dengan total stock_movements per produk. */
    private function verifyStockSync(): void
    {
        $rows = $this->db->query(
            'SELECT p.code, sb.quantity AS saldo, COALESCE(SUM(sm.quantity_change), 0) AS total_movement
             FROM stock_balances sb
             JOIN products p ON p.id = sb.product_id
             LEFT JOIN stock_movements sm ON sm.product_id = sb.product_id
             GROUP BY p.code, sb.quantity
             HAVING sb.quantity <> COALESCE(SUM(sm.quantity_change), 0)
             ORDER BY p.code'
        )->getResultArray();

        if ($rows === []) {
            echo "OK: stok di stock_balances sinkron dengan stock_movements.\n";
            return;
        }

        echo "PERINGATAN: stok tidak sinkron:\n";
        foreach ($rows as $r) {
            echo "  {$r['code']}: saldo={$r['saldo']}, total movement={$r['total_movement']}\n";
        }
    }
}