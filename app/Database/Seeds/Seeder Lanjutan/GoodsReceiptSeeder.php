<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class GoodsReceiptSeeder extends Seeder
{
    public function run()
    {
        // Item: [kode produk, qty diterima]
        $receipts = [
            // PO-0001 diterima penuh lewat dua kali penerimaan
            ['number' => 'GR-202611-0001', 'po' => 'PO-202611-0001', 'date' => '2026-11-04', 'items' => [['CPU-001', 12], ['MB-001', 12], ['RAM-001', 25]]],
            ['number' => 'GR-202611-0002', 'po' => 'PO-202611-0001', 'date' => '2026-11-09', 'items' => [['CPU-001', 8], ['MB-001', 8], ['RAM-001', 15]]],
            // PO-0002 baru diterima sebagian
            ['number' => 'GR-202611-0003', 'po' => 'PO-202611-0002', 'date' => '2026-11-10', 'items' => [['SSD-001', 20], ['SSD-002', 10], ['HDD-001', 20]]],
            // GR yang dibatalkan: stok masuk lalu dibalik dengan movement pembalik
            [
                'number' => 'GR-202611-0004', 'po' => 'PO-202611-0002', 'date' => '2026-11-11', 'items' => [['PSU-001', 5]],
                'cancelled_date' => '2026-11-12', 'cancel_reason' => 'Barang rusak saat diterima, dikembalikan ke supplier',
            ],
        ];

        $gudang = $this->userId('gudang');

        foreach ($receipts as $r) {
            if ($this->db->table('goods_receipts')->where('gr_number', $r['number'])->countAllResults() > 0) {
                continue;
            }

            $po = $this->db->table('purchase_orders')->where('po_number', $r['po'])->get()->getRowArray();
            if (! $po || $po['status'] !== 'Approved') {
                throw new RuntimeException("{$r['po']} tidak ada / belum Approved. Jalankan PurchaseOrderSeeder dulu.");
            }

            $cancelled = isset($r['cancelled_date']);
            $when      = $r['date'] . ' 10:00:00+07';

            $this->db->transStart();

            $this->db->table('goods_receipts')->insert([
                'gr_number'         => $r['number'],
                'purchase_order_id' => $po['id'],
                'receipt_date'      => $r['date'],
                'status'            => $cancelled ? 'Dibatalkan' : 'Aktif',
                'created_by'        => $gudang,
                'created_at'        => $when,
                'cancelled_by'      => $cancelled ? $gudang : null,
                'cancelled_at'      => $cancelled ? $r['cancelled_date'] . ' 10:00:00+07' : null,
                'cancel_reason'     => $r['cancel_reason'] ?? null,
            ]);
            $grId = $this->db->insertID();

            foreach ($r['items'] as [$code, $qty]) {
                $item = $this->db->query(
                    'SELECT poi.id, poi.product_id FROM purchase_order_items poi
                     JOIN products p ON p.id = poi.product_id
                     WHERE poi.purchase_order_id = ? AND lower(p.code) = ?',
                    [$po['id'], strtolower($code)]
                )->getRowArray();
                if (! $item) {
                    throw new RuntimeException("Produk $code tidak ada di {$r['po']}.");
                }

                $this->db->table('goods_receipt_items')->insert([
                    'goods_receipt_id'       => $grId,
                    'purchase_order_item_id' => $item['id'],
                    'quantity_received'      => $qty,
                ]);

                $this->moveStock($item['product_id'], 'masuk', $qty, $when, 'GOODS_RECEIPT', $grId, $r['number'], "Penerimaan barang {$r['number']}", $gudang);

                if ($cancelled) {
                    $this->moveStock(
                        $item['product_id'], 'keluar', -$qty, $r['cancelled_date'] . ' 10:00:00+07',
                        'GOODS_RECEIPT_CANCEL', $grId, $r['number'], "Pembatalan {$r['number']}", $gudang
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

        $this->db->query('UPDATE stock_balances SET quantity = quantity + ?, updated_at = ? WHERE product_id = ?', [$change, $when, $productId]);
    }

    private function userId(string $username): int
    {
        $row = $this->db->table('users')->where('username', $username)->get()->getRowArray();
        return $row['id'] ?? throw new RuntimeException("User '$username' tidak ada. Jalankan UserSeeder dulu.");
    }
}
