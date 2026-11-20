<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // [kode, nama, satuan, harga beli, harga jual, nama kategori]
        $products = [
            ['CPU-001', 'Intel Core i5-12400F', 'pcs', 1700000, 1950000, 'Processor'],
            ['CPU-002', 'AMD Ryzen 5 5600', 'pcs', 1650000, 1900000, 'Processor'],
            ['MB-001', 'ASUS PRIME B660M-K D4', 'pcs', 1500000, 1750000, 'Motherboard'],
            ['MB-002', 'MSI B550M PRO-VDH WiFi', 'pcs', 1450000, 1700000, 'Motherboard'],
            ['RAM-001', 'Kingston Fury Beast DDR4 8GB 3200', 'pcs', 350000, 420000, 'Memory (RAM)'],
            ['RAM-002', 'Corsair Vengeance DDR4 16GB 3200', 'pcs', 700000, 820000, 'Memory (RAM)'],
            ['SSD-001', 'WD Blue SN570 NVMe 500GB', 'pcs', 600000, 720000, 'Storage'],
            ['SSD-002', 'Samsung 980 NVMe 1TB', 'pcs', 1100000, 1300000, 'Storage'],
            ['HDD-001', 'Seagate Barracuda 1TB 7200RPM', 'pcs', 650000, 770000, 'Storage'],
            ['VGA-001', 'ASUS Dual GeForce RTX 3050 8GB', 'pcs', 3200000, 3650000, 'VGA Card'],
            ['PSU-001', 'Corsair CV550 550W 80+ Bronze', 'pcs', 650000, 770000, 'Power Supply'],
            ['CS-001', 'Cooler Master MasterBox Q300L', 'pcs', 550000, 650000, 'Casing'],
            ['MON-001', 'LG 24MP400 24 inch IPS', 'pcs', 1350000, 1550000, 'Monitor'],
            ['PER-001', 'Logitech MK235 Wireless Combo', 'pcs', 280000, 340000, 'Periferal'],
            ['NET-001', 'TP-Link Archer C6 AC1200', 'pcs', 450000, 540000, 'Jaringan'],
            ['NET-002', 'Kabel UTP Cat6 305m', 'box', 1400000, 1650000, 'Jaringan'],
        ];

        foreach ($products as [$code, $name, $unit, $buy, $sell, $categoryName]) {
            // Cek unik tanpa membedakan huruf besar/kecil, sama dengan uq_products_code_lower
            // Pakai query binding (?) agar nilai di-escape otomatis oleh CI4
            $exists = $this->db
                ->query('SELECT 1 FROM products WHERE lower(code) = ? LIMIT 1', [strtolower($code)])
                ->getRowArray();
            if ($exists) {
                continue;
            }

            // Cari category_id lewat nama, jangan hardcode angka
            $category = $this->db->table('categories')->where('name', $categoryName)->get()->getRowArray();
            if (! $category) {
                continue; // jalankan CategorySeeder dulu
            }

            $this->db->table('products')->insert([
                'code'           => $code,
                'name'           => $name,
                'unit'           => $unit,
                'purchase_price' => $buy,
                'selling_price'  => $sell,
                'category_id'    => $category['id'],
            ]);
            // TODO tahap Inventory: buat juga baris stock_balances (quantity 0) per produk.
        }
    }
}