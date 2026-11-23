<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Histori mutasi stok (UI-15, UC-16). Read-only, selalu urut dari yang terbaru.
 * Filter: produk (opsional), rentang tanggal (inklusif; end + 1 hari sebagai batas eksklusif).
 */
class StockMovementModel extends Model
{
    protected $table         = 'stock_movements';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public function listFiltered(?int $productId, ?string $startDate, ?string $endDate, int $perPage = 10): array
    {
        $this->select(
            'stock_movements.id, stock_movements.movement_date, stock_movements.movement_type,
             stock_movements.quantity_change, stock_movements.reference_type,
             stock_movements.reference_id, stock_movements.reference_number, stock_movements.notes,
             p.code, p.name AS product_name, p.unit,
             u.full_name AS created_by_name',
            false
        )
            ->join('products p', 'p.id = stock_movements.product_id')
            ->join('users u', 'u.id = stock_movements.created_by');

        if ($productId !== null && $productId > 0) {
            $this->where('stock_movements.product_id', $productId);
        }
        if ($startDate !== null && $startDate !== '') {
            $this->where('stock_movements.movement_date >=', $startDate);
        }
        if ($endDate !== null && $endDate !== '') {
            // End inklusif sampai akhir hari → batas eksklusif = end + 1 hari
            $endNext = date('Y-m-d', strtotime($endDate . ' +1 day'));
            $this->where('stock_movements.movement_date <', $endNext);
        }

        $rows = $this->orderBy('stock_movements.movement_date', 'DESC')
            ->orderBy('stock_movements.id', 'DESC')
            ->paginate($perPage);

        foreach ($rows as &$r) {
            $r['id']              = (int) $r['id'];
            $r['quantity_change'] = (int) $r['quantity_change'];
            $r['reference_id']    = $r['reference_id'] !== null ? (int) $r['reference_id'] : null;
        }
        return $rows;
    }

    /** Pilihan produk untuk filter (opsional di UI-15). */
    public function productOptions(): array
    {
        return $this->db->table('products')->select('id, code, name')
            ->orderBy('lower(name)', 'ASC', false)->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }
}