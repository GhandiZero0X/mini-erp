<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Akses data produk (UC-04). Semua query memakai Query Builder / query parameterized (NFR-02).
 * Penulisan produk selalu bersama baris stock_balances (stok awal 0) dalam satu transaction (BR-08).
 */
class ProductModel extends Model
{
    protected $table         = 'products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    /**
     * Daftar produk + kategori + satuan, 10 baris per halaman, urut nama (NFR-11).
     * $search mencari di kode atau nama tanpa membedakan huruf besar/kecil (NFR-13).
     */
    public function listPage(string $search = '', int $perPage = 10): array
    {
        $this->select(
            'products.id, products.code, products.name,
             products.purchase_price, products.selling_price,
             c.name AS category_name,
             u.code AS unit_code, u.name AS unit_name',
            false
        )
            ->join('categories c', 'c.id = products.category_id', 'left')
            ->join('units u', 'u.id = products.unit_id', 'left');

        if ($search !== '') {
            $this->groupStart()
                ->like('products.code', $search, 'both', null, true)
                ->orLike('products.name', $search, 'both', null, true)
                ->groupEnd();
        }

        $rows = $this->orderBy('lower(products.name)', 'ASC', false)
            ->orderBy('products.id', 'ASC')
            ->paginate($perPage);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('products p')
            ->select('p.id, p.code, p.name, p.unit_id, p.purchase_price, p.selling_price, p.category_id,
                      u.code AS unit_code, u.name AS unit_name')
            ->join('units u', 'u.id = p.unit_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }
        $row['id']          = (int) $row['id'];
        $row['category_id'] = (int) $row['category_id'];
        $row['unit_id']     = (int) $row['unit_id'];

        return $row;
    }

    /** Pilihan kategori untuk form, urut nama. */
    public function categoryOptions(): array
    {
        return $this->db->table('categories')->select('id, name')
            ->orderBy('lower(name)', 'ASC', false)->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    /** Pilihan satuan untuk form, urut nama. */
    public function unitOptions(): array
    {
        return $this->db->table('units')->select('id, code, name')
            ->orderBy('name', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    public function categoryExists(int $id): bool
    {
        $row = $this->db->table('categories')->select('id')->where('id', $id)->get()->getRowArray();

        return ! empty($row);
    }

    public function unitExists(int $id): bool
    {
        $row = $this->db->table('units')->select('id')->where('id', $id)->get()->getRowArray();

        return ! empty($row);
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $row = $this->db->query('SELECT 1 FROM products WHERE lower(code) = lower(?) LIMIT 1', [$code])->getRowArray();
        } else {
            $row = $this->db->query('SELECT 1 FROM products WHERE lower(code) = lower(?) AND id <> ? LIMIT 1', [$code, $exceptId])->getRowArray();
        }

        return ! empty($row);
    }

    public function isUsed(int $id): bool
    {
        foreach (['purchase_order_items', 'sales_order_items', 'stock_movements'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $row = $this->db->query("SELECT 1 FROM {$table} WHERE product_id = ? LIMIT 1", [$id])->getRowArray();
            if (! empty($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Simpan produk baru + baris stok awal 0 dalam satu transaction.
     *
     * @param array{code:string,name:string,unit_id:int,purchase_price:string,selling_price:string,category_id:int} $d
     */
    public function insertProduct(array $d): int
    {
        $this->db->transBegin();
        try {
            $row = $this->db->query(
                'INSERT INTO products (code, name, unit_id, purchase_price, selling_price, category_id)
                 VALUES (?, ?, ?, ?, ?, ?) RETURNING id',
                [$d['code'], $d['name'], $d['unit_id'], $d['purchase_price'], $d['selling_price'], $d['category_id']]
            )->getRowArray();

            $id = (int) $row['id'];

            $this->db->query('INSERT INTO stock_balances (product_id, quantity) VALUES (?, 0)', [$id]);
            $this->db->transCommit();

            return $id;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Ubah produk. Kolom kode hanya ikut diubah bila $d['code'] ada (tidak diisi bila terkunci, VR-21).
     * Stok tidak pernah disentuh di sini (BR-06).
     *
     * @return int jumlah baris terubah (0 = produk sudah tidak ada)
     */
    public function updateProduct(int $id, array $d): int
    {
        $b = $this->db->table('products')
            ->set('name', $d['name'])
            ->set('unit_id', $d['unit_id'])
            ->set('purchase_price', $d['purchase_price'])
            ->set('selling_price', $d['selling_price'])
            ->set('category_id', $d['category_id'])
            ->set('updated_at', 'now()', false);

        if (array_key_exists('code', $d)) {
            $b->set('code', $d['code']);
        }

        $b->where('id', $id)->update();

        return $this->db->affectedRows();
    }

    /**
     * Hapus produk bila belum dipakai (VR-15, BR-09).
     *
     * @return string 'ok' | 'not_found' | 'in_use'
     */
    public function deleteIfUnused(int $id): string
    {
        $this->db->transBegin();
        try {
            $row = $this->db->query('SELECT id FROM products WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if (! $row) {
                $this->db->transRollback();
                return 'not_found';
            }
            if ($this->isUsed($id)) {
                $this->db->transRollback();
                return 'in_use';
            }

            $this->db->query('DELETE FROM stock_balances WHERE product_id = ?', [$id]);
            $this->db->query('DELETE FROM products WHERE id = ?', [$id]);
            $this->db->transCommit();

            return 'ok';
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}