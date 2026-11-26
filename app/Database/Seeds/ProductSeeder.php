<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class ProductSeeder extends Seeder
{
    /** Tanggal stok awal: harus lebih awal dari transaksi pertama (PO-202611-0001 = 2026-11-02). */
    private const INITIAL_STOCK_DATE = '2026-11-01 08:00:00+07';

    public function run()
    {
        // [kode, nama, satuan, harga_beli, harga_jual, kategori, min_stock, max_stock, stok_awal]
        $products = [
            ['CPU-001', 'Intel Core i5-12400F',                 'PCS', 1700000, 1950000, 'Processor',     2,  20,  1], // below min
            ['CPU-002', 'AMD Ryzen 5 5600',                     'PCS', 1650000, 1900000, 'Processor',     2,  20,  8],
            ['MB-001',  'ASUS PRIME B660M-K D4',                'PCS', 1500000, 1750000, 'Motherboard',   2,  20, 18], // near max
            ['MB-002',  'MSI B550M PRO-VDH WiFi',               'PCS', 1450000, 1700000, 'Motherboard',   2,  20, 12],
            ['RAM-001', 'Kingston Fury Beast DDR4 8GB 3200',    'PCS', 350000,  420000,  'Memory (RAM)',  5,  50, 40], // near max
            ['RAM-002', 'Corsair Vengeance DDR4 16GB 3200',     'PCS', 700000,  820000,  'Memory (RAM)',  5,  50, 20],
            ['SSD-001', 'WD Blue SN570 NVMe 500GB',             'PCS', 600000,  720000,  'Storage',       5,  50,  3], // below min
            ['SSD-002', 'Samsung 980 NVMe 1TB',                 'PCS', 1100000, 1300000, 'Storage',       5,  50, 45], // near max
            ['HDD-001', 'Seagate Barracuda 1TB 7200RPM',        'PCS', 650000,  770000,  'Storage',       5,  50, 30],
            ['VGA-001', 'ASUS Dual GeForce RTX 3050 8GB',       'PCS', 3200000, 3650000, 'VGA Card',      1,  10, 10], // FULL
            ['PSU-001', 'Corsair CV550 550W 80+ Bronze',        'PCS', 650000,  770000,  'Power Supply',  3,  30, 15],
            ['CS-001',  'Cooler Master MasterBox Q300L',        'PCS', 550000,  650000,  'Casing',        3,  30,  8],
            ['MON-001', 'LG 24MP400 24 inch IPS',               'PCS', 1350000, 1550000, 'Monitor',       3,  30, 25], // near max
            ['PER-001', 'Logitech MK235 Wireless Combo',        'PCS', 280000,  340000,  'Periferal',     3,  30,  2], // below min
            ['NET-001', 'TP-Link Archer C6 AC1200',             'PCS', 450000,  540000,  'Jaringan',      3,  30, 15],
            ['NET-002', 'Kabel UTP Cat6 305m',                  'BOX', 1400000, 1650000, 'Jaringan',      2,  20,  5],
        ];

        $unitMap = [];
        foreach ($this->db->table('units')->get()->getResultArray() as $u) {
            $unitMap[$u['code']] = (int) $u['id'];
        }

        $categoryMap = [];
        foreach ($this->db->table('categories')->get()->getResultArray() as $c) {
            $categoryMap[$c['name']] = (int) $c['id'];
        }

        $admin = $this->db->table('users')->where('username', 'admin')->get()->getRowArray();
        if (! $admin) {
            throw new RuntimeException("User 'admin' tidak ada. Jalankan UserSeeder dulu.");
        }
        $adminId = (int) $admin['id'];

        $now = date('Y-m-d H:i:s');

        foreach ($products as [$code, $name, $unitCode, $buy, $sell, $categoryName, $min, $max, $initialStock]) {

            if (! isset($unitMap[$unitCode])) {
                echo "SKIP {$code}: unit '{$unitCode}' tidak ada. Jalankan UnitSeeder dulu.\n";
                continue;
            }
            if (! isset($categoryMap[$categoryName])) {
                echo "SKIP {$code}: kategori '{$categoryName}' tidak ada. Jalankan CategorySeeder dulu.\n";
                continue;
            }

            $exists = $this->db
                ->query('SELECT 1 FROM products WHERE lower(code) = ? LIMIT 1', [strtolower($code)])
                ->getRowArray();
            if ($exists) {
                continue;
            }

            $this->db->transStart();

            $row = $this->db->query(
                'INSERT INTO products
                    (code, name, unit_id, purchase_price, selling_price, category_id, min_stock, max_stock, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id',
                [
                    $code, $name, $unitMap[$unitCode],
                    $buy, $sell, $categoryMap[$categoryName],
                    $min, $max,
                    $now, $now,
                ]
            )->getRowArray();

            $productId = (int) $row['id'];

            // Saldo SELALU dibuat dari 0, lalu dinaikkan lewat stock_movements,
            // supaya saldo (menu Inventory) == total movement (Laporan Stok).
            $this->db->query(
                'INSERT INTO stock_balances (product_id, quantity, updated_at) VALUES (?, 0, ?)
                 ON CONFLICT (product_id) DO NOTHING',
                [$productId, $now]
            );

            if ($initialStock > 0) {
                $this->db->table('stock_movements')->insert([
                    'product_id'       => $productId,
                    'movement_type'    => 'masuk',
                    'quantity_change'  => $initialStock,
                    'movement_date'    => self::INITIAL_STOCK_DATE,
                    'reference_type'   => 'INITIAL_STOCK',
                    'reference_id'     => $productId,
                    'reference_number' => 'STOK-AWAL',
                    'notes'            => 'Stok awal',
                    'created_by'       => $adminId,
                    'created_at'       => self::INITIAL_STOCK_DATE,
                ]);

                $this->db->query(
                    'UPDATE stock_balances SET quantity = quantity + ?, updated_at = ? WHERE product_id = ?',
                    [$initialStock, self::INITIAL_STOCK_DATE, $productId]
                );
            }

            $this->db->transComplete();
        }
    }
}