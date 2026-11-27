<?php

namespace App\Models;

use CodeIgniter\Model;

/** Akses data satuan produk (Master Units). Semua query memakai Query Builder / query parameterized (NFR-02). */
class UnitModel extends Model
{
    protected $table         = 'units';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // updated_at diisi manual (now() dari PostgreSQL)

    /**
     * Daftar satuan + jumlah produk yang memakainya, 10 baris per halaman, urut nama. Pager di $this->pager.
     * $search = potongan kode ATAU nama (tanpa membedakan huruf besar/kecil); '' = semua satuan.
     */
    public function listPage(int $perPage = 10, string $search = ''): array
    {
        $this->select(
            'units.id, units.code, units.name,
             (SELECT COUNT(*) FROM products p WHERE p.unit_id = units.id) AS product_count',
            false
        );

        if ($search !== '') {
            // like(): karakter % dan _ pada kata kunci di-escape; argumen terakhir true = ILIKE (PostgreSQL)
            $this->groupStart()
                ->like('units.code', $search, 'both', true, true)
                ->orLike('units.name', $search, 'both', true, true)
                ->groupEnd();
        }

        $rows = $this
            ->orderBy('lower(units.name)', 'ASC', false)
            ->orderBy('units.id', 'ASC')
            ->paginate($perPage);

        foreach ($rows as &$row) {
            $row['id']            = (int) $row['id'];
            $row['product_count'] = (int) $row['product_count'];
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('units')->select('id, code, name')->where('id', $id)->get()->getRowArray();

        return $row ? ['id' => (int) $row['id'], 'code' => $row['code'], 'name' => $row['name']] : null;
    }

    /** Kode sudah dipakai satuan lain? Tanpa membedakan huruf besar/kecil. $exceptId diisi saat ubah. */
    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $row = $this->db->query('SELECT 1 FROM units WHERE lower(code) = lower(?) LIMIT 1', [$code])->getRowArray();
        } else {
            $row = $this->db->query('SELECT 1 FROM units WHERE lower(code) = lower(?) AND id <> ? LIMIT 1', [$code, $exceptId])->getRowArray();
        }

        return ! empty($row);
    }

    /** Nama sudah dipakai satuan lain? Tanpa membedakan huruf besar/kecil. */
    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $row = $this->db->query('SELECT 1 FROM units WHERE lower(name) = lower(?) LIMIT 1', [$name])->getRowArray();
        } else {
            $row = $this->db->query('SELECT 1 FROM units WHERE lower(name) = lower(?) AND id <> ? LIMIT 1', [$name, $exceptId])->getRowArray();
        }

        return ! empty($row);
    }

    /** Satuan sudah dipakai produk? (VR-15, BR-09) */
    public function isUsed(int $id): bool
    {
        $row = $this->db->query('SELECT 1 FROM products WHERE unit_id = ? LIMIT 1', [$id])->getRowArray();

        return ! empty($row);
    }

    /** @param array{code:string,name:string} $d */
    public function insertUnit(array $d): bool
    {
        return (bool) $this->db->table('units')->insert([
            'code' => $d['code'],
            'name' => $d['name'],
        ]);
    }

    /** @return int jumlah baris terubah (0 = satuan sudah tidak ada) */
    public function updateUnit(int $id, array $d): int
    {
        $this->db->table('units')
            ->set('code', $d['code'])
            ->set('name', $d['name'])
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();

        return $this->db->affectedRows();
    }

    /**
     * Hapus satuan bila belum dipakai produk. Baris dikunci selama pengecekan (FOR UPDATE),
     * sehingga produk yang dibuat bersamaan (FK menahan lock baris ini) tidak lolos.
     *
     * @return string 'ok' | 'not_found' | 'in_use'
     */
    public function deleteIfUnused(int $id): string
    {
        $this->db->transBegin();

        try {
            $row = $this->db->query('SELECT id FROM units WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if (! $row) {
                $this->db->transRollback();

                return 'not_found';
            }

            if ($this->isUsed($id)) {
                $this->db->transRollback();

                return 'in_use';
            }

            $this->db->query('DELETE FROM units WHERE id = ?', [$id]);
            $this->db->transCommit();

            return 'ok';
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }
}
