<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Daftar produk + saldo stok terkini (UI-14, UC-15).
 * LEFT JOIN ke stock_balances → produk tanpa baris saldo tetap tampil dengan quantity = 0.
 */
class StockListModel extends Model
{
    protected $table         = 'products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    /** @return array<int, array<string, mixed>> */
    public function listWithStock(string $search = '', int $categoryId = 0, int $perPage = 10): array
    {
        $this->select(
            'products.id, products.code, products.name, u.code AS unit,
             c.name AS category_name,
             COALESCE(sb.quantity, 0) AS quantity',
            false
        )
            ->join('units u', 'u.id = products.unit_id', 'left')             // ← BARU
            ->join('stock_balances sb', 'sb.product_id = products.id', 'left')
            ->join('categories c', 'c.id = products.category_id', 'left');

        if ($search !== '') {
            $this->groupStart()
                ->like('products.code', $search, 'both', null, true)
                ->orLike('products.name', $search, 'both', null, true)
                ->groupEnd();
        }
        if ($categoryId > 0) {
            $this->where('products.category_id', $categoryId);
        }

        $rows = $this->orderBy('lower(products.name)', 'ASC', false)
            ->orderBy('products.id', 'ASC')
            ->paginate($perPage);

        foreach ($rows as &$r) {
            $r['id']       = (int) $r['id'];
            $r['quantity'] = (int) $r['quantity'];
        }
        return $rows;
    }

    /** Untuk filter kategori di form. */
    public function categoryOptions(): array
    {
        return $this->db->table('categories')->select('id, name')
            ->orderBy('lower(name)', 'ASC', false)->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }
}