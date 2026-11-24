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
        $this->call('ProductSeeder');
        $this->call('StockBalanceSeeder');
        $this->call('SupplierSeeder');
        $this->call('CustomerSeeder');

        // Transaksi (data contoh). Urutan: PO -> GR (stok masuk) -> SO -> Delivery (stok keluar)
        $this->call('PurchaseOrderSeeder');
        $this->call('GoodsReceiptSeeder');
        $this->call('SalesOrderSeeder');
        $this->call('DeliverySeeder');
    }
}