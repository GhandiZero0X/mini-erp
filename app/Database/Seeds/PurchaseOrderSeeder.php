<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class PurchaseOrderSeeder extends Seeder
{
    public function run()
    {
        // Item: [kode produk, qty, harga satuan]
        $orders = [
            [
                'number' => 'PO-202611-0001', 'date' => '2026-11-02', 'supplier' => 'PT Synnex Metrodata Indonesia',
                'status' => 'Approved', 'created_by' => 'purchasing', 'approved_by' => 'manajer',
                'items'  => [['CPU-001', 20, 1700000], ['MB-001', 20, 1500000], ['RAM-001', 40, 350000]],
            ],
            [
                'number' => 'PO-202611-0002', 'date' => '2026-11-05', 'supplier' => 'PT Datascrip Komponen',
                'status' => 'Approved', 'created_by' => 'purchasing', 'approved_by' => 'manajer',
                'items'  => [['SSD-001', 30, 600000], ['SSD-002', 15, 1100000], ['HDD-001', 20, 650000], ['PSU-001', 10, 650000]],
            ],
            [
                'number' => 'PO-202611-0003', 'date' => '2026-11-12', 'supplier' => 'CV Mega Komputindo',
                'status' => 'Draft', 'created_by' => 'purchasing',
                'items'  => [['VGA-001', 5, 3200000], ['CS-001', 10, 550000]],
            ],
            [
                'number' => 'PO-202611-0004', 'date' => '2026-11-06', 'supplier' => 'PT Erajaya Hardware Supply',
                'status' => 'Dibatalkan', 'created_by' => 'purchasing',
                'cancelled_by' => 'purchasing', 'cancelled_date' => '2026-11-07', 'cancel_reason' => 'Supplier kehabisan stok',
                'items'  => [['MON-001', 10, 1350000]],
            ],
        ];

        foreach ($orders as $o) {
            // Idempotent
            if ($this->db->table('purchase_orders')->where('po_number', $o['number'])->countAllResults() > 0) {
                continue;
            }

            $this->db->transStart();

            $total = 0;
            foreach ($o['items'] as [, $qty, $price]) {
                $total += $qty * $price;
            }

            $this->db->table('purchase_orders')->insert([
                'po_number'     => $o['number'],
                'po_date'       => $o['date'],
                'supplier_id'   => $this->idByName('suppliers', $o['supplier']),
                'status'        => $o['status'],
                'total_amount'  => $total,
                'created_by'    => $this->userId($o['created_by']),
                'created_at'    => $o['date'] . ' 09:00:00+07',
                'approved_by'   => isset($o['approved_by']) ? $this->userId($o['approved_by']) : null,
                'approved_at'   => isset($o['approved_by']) ? $o['date'] . ' 14:00:00+07' : null,
                'cancelled_by'  => isset($o['cancelled_by']) ? $this->userId($o['cancelled_by']) : null,
                'cancelled_at'  => isset($o['cancelled_by']) ? $o['cancelled_date'] . ' 10:00:00+07' : null,
                'cancel_reason' => $o['cancel_reason'] ?? null,
            ]);
            $poId = $this->db->insertID();

            foreach ($o['items'] as [$code, $qty, $price]) {
                $this->db->table('purchase_order_items')->insert([
                    'purchase_order_id' => $poId,
                    'product_id'        => $this->productId($code),
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'subtotal'          => $qty * $price,
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

    private function idByName(string $table, string $name): int
    {
        $row = $this->db->table($table)->where('name', $name)->get()->getRowArray();
        return $row['id'] ?? throw new RuntimeException("'$name' tidak ada di tabel $table.");
    }
}
