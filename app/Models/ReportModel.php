<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Documents;

class ReportModel extends Model
{
    // --- Laporan Pembelian ---
    public function purchases(string $start, string $end): array
    {
        return $this->db->table('purchase_orders po')
            ->select('po.po_number, po.po_date, po.status, po.total_amount, s.name AS supplier_name')
            ->join('suppliers s', 's.id = po.supplier_id')
            ->where('po.po_date >=', $start)
            ->where('po.po_date <=', $end)
            ->orderBy('po.po_date', 'DESC')->orderBy('po.id', 'DESC')
            ->get()->getResultArray();
    }

    // --- Laporan Penjualan ---
    public function sales(string $start, string $end): array
    {
        return $this->db->table('sales_orders so')
            ->select('so.so_number, so.so_date, so.status, so.total_amount, c.name AS customer_name')
            ->join('customers c', 'c.id = so.customer_id')
            ->where('so.so_date >=', $start)
            ->where('so.so_date <=', $end)
            ->orderBy('so.so_date', 'DESC')->orderBy('so.id', 'DESC')
            ->get()->getResultArray();
    }

    // --- Laporan Stok ---
    public function stock(string $start, string $end, int $categoryId = 0): array
    {
        $startDate = $this->db->escape($start);
        $endDate   = $this->db->escape(date('Y-m-d', strtotime($end . ' +1 day')));

        $builder = $this->db->table('products p')
            ->select("p.code, p.name,
                    u.code AS unit,
                    c.name AS category_name,
                    COALESCE((SELECT SUM(sm.quantity_change) FROM stock_movements sm WHERE sm.product_id = p.id AND sm.movement_date < {$startDate}), 0) AS stok_awal,
                    COALESCE((SELECT SUM(sm.quantity_change) FROM stock_movements sm WHERE sm.product_id = p.id AND sm.movement_type = 'masuk' AND sm.movement_date >= {$startDate} AND sm.movement_date < {$endDate}), 0) AS total_masuk,
                    ABS(COALESCE((SELECT SUM(sm.quantity_change) FROM stock_movements sm WHERE sm.product_id = p.id AND sm.movement_type = 'keluar' AND sm.movement_date >= {$startDate} AND sm.movement_date < {$endDate}), 0)) AS total_keluar,
                    COALESCE((SELECT SUM(sm.quantity_change) FROM stock_movements sm WHERE sm.product_id = p.id AND sm.movement_date < {$endDate}), 0) AS stock_akhir")
            ->join('units u', 'u.id = p.unit_id', 'left')
            ->join('categories c', 'c.id = p.category_id', 'left');

        if ($categoryId > 0) {
            $builder->where('p.category_id', $categoryId);
        }

        return $builder->orderBy('p.name', 'ASC')->get()->getResultArray();
    }

    public function categoryOptions(): array
    {
        return $this->db->table('categories')->select('id, name')->orderBy('name')->get()->getResultArray();
    }

    // --- Laporan Produk Terlaris ---
    public function topProducts(string $start, string $end): array
    {
        $cancelled = Documents::SO_CANCELLED;
        
        return $this->db->table('sales_order_items soi')
            ->select('p.code, p.name, SUM(soi.quantity) AS total_qty, SUM(soi.subtotal) AS total_revenue')
            ->join('products p', 'p.id = soi.product_id')
            ->join('sales_orders so', 'so.id = soi.sales_order_id')
            ->where('so.status !=', $cancelled)
            ->where('so.so_date >=', $start)
            ->where('so.so_date <=', $end)
            ->groupBy(['soi.product_id', 'p.code', 'p.name']) // <-- FIX DI SINI
            ->orderBy('total_qty', 'DESC')
            ->limit(10)
            ->get()->getResultArray();
    }
}