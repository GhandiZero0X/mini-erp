<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class SalesOrderSeeder extends Seeder
{
    public function run()
    {
        // Item: [kode produk, qty, harga satuan]
        $orders = [
            [
                'number' => 'SO-202611-0001', 'date' => '2026-11-13', 'customer' => 'Toko Komputer Maju Jaya', 'status' => 'Open',
                'items'  => [['CPU-001', 5, 1950000], ['MB-001', 5, 1750000], ['RAM-001', 10, 420000]],
            ],
            [
                'number' => 'SO-202611-0002', 'date' => '2026-11-15', 'customer' => 'CV Cipta Solusi Digital', 'status' => 'Open',
                'items'  => [['SSD-001', 10, 720000], ['SSD-002', 5, 1300000], ['HDD-001', 8, 770000]],
            ],
            [
                'number' => 'SO-202611-0003', 'date' => '2026-11-18', 'customer' => 'PT Mitra Sekolah Teknologi', 'status' => 'Open',
                'items'  => [['CPU-001', 10, 1950000], ['RAM-001', 20, 420000]],
            ],
            [
                'number' => 'SO-202611-0004', 'date' => '2026-11-19', 'customer' => 'Warnet Bintang Net', 'status' => 'Dibatalkan',
                'cancelled_date' => '2026-11-20', 'cancel_reason' => 'Pelanggan membatalkan pesanan',
                'items'  => [['HDD-001', 4, 770000]],
            ],
        ];

        $sales = $this->userId('sales');

        foreach ($orders as $o) {
            if ($this->db->table('sales_orders')->where('so_number', $o['number'])->countAllResults() > 0) {
                continue;
            }

            $this->db->transStart();

            $total = 0;
            foreach ($o['items'] as [, $qty, $price]) {
                $total += $qty * $price;
            }

            $cancelled = $o['status'] === 'Dibatalkan';

            $this->db->table('sales_orders')->insert([
                'so_number'     => $o['number'],
                'so_date'       => $o['date'],
                'customer_id'   => $this->customerId($o['customer']),
                'status'        => $o['status'],
                'total_amount'  => $total,
                'created_by'    => $sales,
                'created_at'    => $o['date'] . ' 09:00:00+07',
                'cancelled_by'  => $cancelled ? $sales : null,
                'cancelled_at'  => $cancelled ? $o['cancelled_date'] . ' 10:00:00+07' : null,
                'cancel_reason' => $o['cancel_reason'] ?? null,
            ]);
            $soId = $this->db->insertID();

            foreach ($o['items'] as [$code, $qty, $price]) {
                $this->db->table('sales_order_items')->insert([
                    'sales_order_id' => $soId,
                    'product_id'     => $this->productId($code),
                    'quantity'       => $qty,
                    'unit_price'     => $price,
                    'subtotal'       => $qty * $price,
                ]);
            }

            $this->db->transComplete();
        }
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->where('username', $username)->get()->getRowArray();
        return $row['id'] ?? throw new RuntimeException("User '$username' tidak ada. Jalankan UserSeeder dulu.");
    }

    private function productId(string $code): int
    {
        $row = $this->db->query('SELECT id FROM products WHERE lower(code) = ?', [strtolower($code)])->getRowArray();
        return $row['id'] ?? throw new RuntimeException("Produk '$code' tidak ada. Jalankan ProductSeeder dulu.");
    }

    private function customerId(string $name): int
    {
        $row = $this->db->table('customers')->where('name', $name)->get()->getRowArray();
        return $row['id'] ?? throw new RuntimeException("Customer '$name' tidak ada. Jalankan CustomerSeeder dulu.");
    }
}
