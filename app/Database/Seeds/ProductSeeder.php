<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // [kode, nama, KODE SATUAN, harga beli, harga jual, nama kategori]
        $products = [
            ['CPU-001', 'Intel Core i5-12400F',                 'PCS', 1700000, 1950000, 'Processor'],
            ['CPU-002', 'AMD Ryzen 5 5600',                     'PCS', 1650000, 1900000, 'Processor'],
            ['MB-001',  'ASUS PRIME B660M-K D4',                'PCS', 1500000, 1750000, 'Motherboard'],
            ['MB-002',  'MSI B550M PRO-VDH WiFi',               'PCS', 1450000, 1700000, 'Motherboard'],
            ['RAM-001', 'Kingston Fury Beast DDR4 8GB 3200',    'PCS', 350000,  420000,  'Memory (RAM)'],
            ['RAM-002', 'Corsair Vengeance DDR4 16GB 3200',     'PCS', 700000,  820000,  'Memory (RAM)'],
            ['SSD-001', 'WD Blue SN570 NVMe 500GB',             'PCS', 600000,  720000,  'Storage'],
            ['SSD-002', 'Samsung 980 NVMe 1TB',                 'PCS', 1100000, 1300000, 'Storage'],
            ['HDD-001', 'Seagate Barracuda 1TB 7200RPM',        'PCS', 650000,  770000,  'Storage'],
            ['VGA-001', 'ASUS Dual GeForce RTX 3050 8GB',       'PCS', 3200000, 3650000, 'VGA Card'],
            ['PSU-001', 'Corsair CV550 550W 80+ Bronze',        'PCS', 650000,  770000,  'Power Supply'],
            ['CS-001',  'Cooler Master MasterBox Q300L',        'PCS', 550000,  650000,  'Casing'],
            ['MON-001', 'LG 24MP400 24 inch IPS',               'PCS', 1350000, 1550000, 'Monitor'],
            ['PER-001', 'Logitech MK235 Wireless Combo',        'PCS', 280000,  340000,  'Periferal'],
            ['NET-001', 'TP-Link Archer C6 AC1200',             'PCS', 450000,  540000,  'Jaringan'],
            ['NET-002', 'Kabel UTP Cat6 305m',                  'BOX', 1400000, 1650000, 'Jaringan'],
        ];

        // Build lookup map SEKALI di awal (hindari N+1 query di dalam loop)
        $unitMap = [];
        foreach ($this->db->table('units')->get()->getResultArray() as $u) {
            $unitMap[$u['code']] = (int) $u['id'];
        }

        $categoryMap = [];
        foreach ($this->db->table('categories')->get()->getResultArray() as $c) {
            $categoryMap[$c['name']] = (int) $c['id'];
        }

        $now = date('Y-m-d H:i:s');

        foreach ($products as [$code, $name, $unitCode, $buy, $sell, $categoryName]) {

            // Validasi referensi — kalau seeder unit/kategori belum jalan, skip + warning
            if (! isset($unitMap[$unitCode])) {
                echo "SKIP {$code}: unit '{$unitCode}' tidak ada. Jalankan UnitSeeder dulu.\n";
                continue;
            }
            if (! isset($categoryMap[$categoryName])) {
                echo "SKIP {$code}: kategori '{$categoryName}' tidak ada. Jalankan CategorySeeder dulu.\n";
                continue;
            }

            // Cek idempoten (code unik case-insensitive)
            $exists = $this->db
                ->query('SELECT 1 FROM products WHERE lower(code) = ? LIMIT 1', [strtolower($code)])
                ->getRowArray();
            if ($exists) {
                continue;
            }

            $this->db->table('products')->insert([
                'code'           => $code,
                'name'           => $name,
                'unit_id'        => $unitMap[$unitCode],   // ← int FK, bukan varchar
                'purchase_price' => $buy,
                'selling_price'  => $sell,
                'category_id'    => $categoryMap[$categoryName],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            // TODO tahap Inventory: buat juga baris stock_balances (quantity 0) per produk.
        }
    }
}