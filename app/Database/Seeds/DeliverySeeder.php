<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class DeliverySeeder extends Seeder
{
    public function run()
    {
        // Item: [kode produk, qty dikirim]
        $deliveries = [
            // SO-0001 terkirim penuh lewat dua kali pengiriman
            ['number' => 'DL-202611-0001', 'so' => 'SO-202611-0001', 'date' => '2026-11-14', 'items' => [['CPU-001', 3], ['MB-001', 3], ['RAM-001', 6]]],
            ['number' => 'DL-202611-0002', 'so' => 'SO-202611-0001', 'date' => '2026-11-16', 'items' => [['CPU-001', 2], ['MB-001', 2], ['RAM-001', 4]]],
            // SO-0002 baru terkirim sebagian
            ['number' => 'DL-202611-0003', 'so' => 'SO-202611-0002', 'date' => '2026-11-17', 'items' => [['SSD-001', 6], ['SSD-002', 5]]],
            // Delivery yang dibatalkan: stok keluar lalu dibalik dengan movement pembalik
            [
                'number' => 'DL-202611-0004', 'so' => 'SO-202611-0002', 'date' => '2026-11-18', 'items' => [['HDD-001', 8]],
                'cancelled_date' => '2026-11-19', 'cancel_reason' => 'Alamat pengiriman salah, barang dikembalikan ke gudang',
            ],
        ];

        $gudang = $this->userId('gudang');

        foreach ($deliveries as $d) {
            if ($this->db->table('deliveries')->where('delivery_number', $d['number'])->countAllResults() > 0) {
                continue;
            }

            $so = $this->db->table('sales_orders')->where('so_number', $d['so'])->get()->getRowArray();
            if (! $so || $so['status'] !== 'Open') {
                throw new RuntimeException("{$d['so']} tidak ada / tidak Open. Jalankan SalesOrderSeeder dulu.");
            }

            $cancelled = isset($d['cancelled_date']);
            $when      = $d['date'] . ' 10:00:00+07';

            $this->db->transStart();

            $this->db->table('deliveries')->insert([
                'delivery_number' => $d['number'],
                'sales_order_id'  => $so['id'],
                'delivery_date'   => $d['date'],
                'status'          => $cancelled ? 'Dibatalkan' : 'Aktif',
                'created_by'      => $gudang,
                'created_at'      => $when,
                'cancelled_by'    => $cancelled ? $gudang : null,
                'cancelled_at'    => $cancelled ? $d['cancelled_date'] . ' 10:00:00+07' : null,
                'cancel_reason'   => $d['cancel_reason'] ?? null,
            ]);
            $dlId = $this->db->insertID();

            foreach ($d['items'] as [$code, $qty]) {
                $item = $this->db->query(
                    'SELECT soi.id, soi.product_id FROM sales_order_items soi
                     JOIN products p ON p.id = soi.product_id
                     WHERE soi.sales_order_id = ? AND lower(p.code) = ?',
                    [$so['id'], strtolower($code)]
                )->getRowArray();
                if (! $item) {
                    throw new RuntimeException("Produk $code tidak ada di {$d['so']}.");
                }

                $this->db->table('delivery_items')->insert([
                    'delivery_id'         => $dlId,
                    'sales_order_item_id' => $item['id'],
                    'quantity_delivered'  => $qty,
                ]);

                $this->moveStock($item['product_id'], 'keluar', -$qty, $when, 'DELIVERY', $dlId, $d['number'], "Pengiriman barang {$d['number']}", $gudang);

                if ($cancelled) {
                    $this->moveStock(
                        $item['product_id'], 'masuk', $qty, $d['cancelled_date'] . ' 10:00:00+07',
                        'DELIVERY_CANCEL', $dlId, $d['number'], "Pembatalan {$d['number']}", $gudang
                    );
                }
            }

            $this->db->transComplete();
        }
    }

    /** Tulis stock_movements dan perbarui stock_balances (selalu berpasangan). */
    private function moveStock(int $productId, string $type, int $change, string $when, string $refType, int $refId, string $refNumber, string $notes, int $userId): void
    {
        $this->db->query('INSERT INTO stock_balances (product_id, quantity) VALUES (?, 0) ON CONFLICT (product_id) DO NOTHING', [$productId]);

        $this->db->table('stock_movements')->insert([
            'product_id'       => $productId,
            'movement_type'    => $type,
            'quantity_change'  => $change,
            'movement_date'    => $when,
            'reference_type'   => $refType,
            'reference_id'     => $refId,
            'reference_number' => $refNumber,
            'notes'            => $notes,
            'created_by'       => $userId,
            'created_at'       => $when,
        ]);

        // CHECK chk_stock_balances_qty_nonneg akan menolak bila stok tidak cukup
        $this->db->query('UPDATE stock_balances SET quantity = quantity + ?, updated_at = ? WHERE product_id = ?', [$change, $when, $productId]);
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->where('username', $username)->get()->getRowArray();
        return $row['id'] ?? throw new RuntimeException("User '$username' tidak ada. Jalankan UserSeeder dulu.");
    }
}
