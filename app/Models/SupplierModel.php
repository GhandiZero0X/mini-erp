<?php

namespace App\Models;

use CodeIgniter\Model;

/** Akses data supplier (UC-05). Semua query memakai Query Builder / query parameterized (NFR-02). */
class SupplierModel extends Model
{
    protected $table         = 'suppliers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // updated_at diisi manual (now() dari PostgreSQL)

    /**
     * Daftar supplier, 10 baris per halaman, urut nama (NFR-11). Pager di $this->pager.
     * $search = potongan nama (tanpa membedakan huruf besar/kecil); '' = semua data.
     */
    public function listPage(int $perPage = 10, string $search = ''): array
    {
        $this->select('id, name, address, phone, email');

        if ($search !== '') {
            // like(): karakter % dan _ pada kata kunci di-escape; argumen terakhir true = ILIKE (PostgreSQL)
            $this->like('name', $search, 'both', true, true);
        }

        $rows = $this
            ->orderBy('lower(name)', 'ASC', false)
            ->orderBy('id', 'ASC')
            ->paginate($perPage);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('suppliers')->select('id, name, address, phone, email')
            ->where('id', $id)->get()->getRowArray();

        if (! $row) {
            return null;
        }
        $row['id'] = (int) $row['id'];

        return $row;
    }

    /**
     * Supplier sudah dipakai Purchase Order? (VR-15, BR-09)
     * Tabel yang belum dibuat (modul PO belum selesai) dilewati: pasti belum ada pemakaian.
     */
    public function isUsed(int $id): bool
    {
        if (! $this->db->tableExists('purchase_orders')) {
            return false;
        }

        $row = $this->db->query('SELECT 1 FROM purchase_orders WHERE supplier_id = ? LIMIT 1', [$id])->getRowArray();

        return ! empty($row);
    }

    /** @param array{name:string,address:?string,phone:?string,email:?string} $d */
    public function insertSupplier(array $d): bool
    {
        return (bool) $this->db->table('suppliers')->insert([
            'name'    => $d['name'],
            'address' => $d['address'],
            'phone'   => $d['phone'],
            'email'   => $d['email'],
        ]);
    }

    /** @return int jumlah baris terubah (0 = supplier sudah tidak ada) */
    public function updateSupplier(int $id, array $d): int
    {
        $this->db->table('suppliers')
            ->set('name', $d['name'])
            ->set('address', $d['address'])
            ->set('phone', $d['phone'])
            ->set('email', $d['email'])
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();

        return $this->db->affectedRows();
    }

    /**
     * Hapus supplier bila belum dipakai PO. Baris dikunci selama pengecekan (FOR UPDATE).
     *
     * @return string 'ok' | 'not_found' | 'in_use'
     */
    public function deleteIfUnused(int $id): string
    {
        $this->db->transBegin();

        try {
            $row = $this->db->query('SELECT id FROM suppliers WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if (! $row) {
                $this->db->transRollback();

                return 'not_found';
            }

            if ($this->isUsed($id)) {
                $this->db->transRollback();

                return 'in_use';
            }

            $this->db->query('DELETE FROM suppliers WHERE id = ?', [$id]);
            $this->db->transCommit();

            return 'ok';
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }
}