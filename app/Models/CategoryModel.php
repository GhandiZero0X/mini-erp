<?php

namespace App\Models;

use CodeIgniter\Model;

/** Akses data kategori. Semua query memakai Query Builder / query parameterized (NFR-02). */
class CategoryModel extends Model
{
    protected $table         = 'categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // updated_at diisi manual (now() dari PostgreSQL)

    /**
     * Daftar kategori + jumlah produk, 10 baris per halaman, urut nama (NFR-11). Pager di $this->pager.
     * $search = potongan nama kategori (tanpa membedakan huruf besar/kecil); '' = semua kategori.
     */
    public function listPage(int $perPage = 10, string $search = ''): array
    {
        $this->select(
            'categories.id, categories.name,
             (SELECT COUNT(*) FROM products p WHERE p.category_id = categories.id) AS product_count',
            false
        );

        if ($search !== '') {
            // like(): karakter % dan _ pada kata kunci di-escape; argumen terakhir true = ILIKE (PostgreSQL)
            $this->like('categories.name', $search, 'both', true, true);
        }

        $rows = $this
            ->orderBy('lower(categories.name)', 'ASC', false)
            ->orderBy('categories.id', 'ASC')
            ->paginate($perPage);

        foreach ($rows as &$row) {
            $row['id']            = (int) $row['id'];
            $row['product_count'] = (int) $row['product_count'];
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('categories')->select('id, name')->where('id', $id)->get()->getRowArray();

        return $row ? ['id' => (int) $row['id'], 'name' => $row['name']] : null;
    }

    /**
     * Nama sudah dipakai kategori lain? Tanpa membedakan huruf besar/kecil.
     * $exceptId diisi saat ubah agar nama yang tidak diganti tidak dianggap duplikat (VR-20).
     */
    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $row = $this->db->query('SELECT 1 FROM categories WHERE lower(name) = lower(?) LIMIT 1', [$name])->getRowArray();
        } else {
            $row = $this->db->query('SELECT 1 FROM categories WHERE lower(name) = lower(?) AND id <> ? LIMIT 1', [$name, $exceptId])->getRowArray();
        }

        return ! empty($row);
    }

    public function insertCategory(string $name): bool
    {
        return (bool) $this->db->table('categories')->insert(['name' => $name]);
    }

    /** @return int jumlah baris terubah (0 = kategori sudah tidak ada) */
    public function updateName(int $id, string $name): int
    {
        $this->db->table('categories')
            ->set('name', $name)
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();

        return $this->db->affectedRows();
    }

    public function productCount(int $id): int
    {
        return $this->db->table('products')->where('category_id', $id)->countAllResults();
    }

    /** @return int jumlah baris terhapus */
    public function deleteById(int $id): int
    {
        $this->db->table('categories')->where('id', $id)->delete();

        return $this->db->affectedRows();
    }
}