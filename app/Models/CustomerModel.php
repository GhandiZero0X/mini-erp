<?php

namespace App\Models;

use CodeIgniter\Model;

/** Akses data customer (UC-06). Semua query memakai Query Builder / query parameterized (NFR-02). */
class CustomerModel extends Model
{
    protected $table         = 'customers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // updated_at diisi manual (now() dari PostgreSQL)

    /**
     * Daftar customer, 10 baris per halaman, urut nama (NFR-11). Pager di $this->pager.
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
        $row = $this->db->table('customers')->select('id, name, address, phone, email')
            ->where('id', $id)->get()->getRowArray();

        if (! $row) {
            return null;
        }
        $row['id'] = (int) $row['id'];

        return $row;
    }

    /**
     * Customer sudah dipakai Sales Order? (VR-15, BR-09)
     * Tabel yang belum dibuat (modul SO belum selesai) dilewati: pasti belum ada pemakaian.
     */
    public function isUsed(int $id): bool
    {
        if (! $this->db->tableExists('sales_orders')) {
            return false;
        }

        $row = $this->db->query('SELECT 1 FROM sales_orders WHERE customer_id = ? LIMIT 1', [$id])->getRowArray();

        return ! empty($row);
    }

    /** @param array{name:string,address:?string,phone:?string,email:?string} $d */
    public function insertCustomer(array $d): bool
    {
        return (bool) $this->db->table('customers')->insert([
            'name'    => $d['name'],
            'address' => $d['address'],
            'phone'   => $d['phone'],
            'email'   => $d['email'],
        ]);
    }

    /** @return int jumlah baris terubah (0 = customer sudah tidak ada) */
    public function updateCustomer(int $id, array $d): int
    {
        $this->db->table('customers')
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
     * Hapus customer bila belum dipakai SO. Baris dikunci selama pengecekan (FOR UPDATE).
     *
     * @return string 'ok' | 'not_found' | 'in_use'
     */
    public function deleteIfUnused(int $id): string
    {
        $this->db->transBegin();

        try {
            $row = $this->db->query('SELECT id FROM customers WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if (! $row) {
                $this->db->transRollback();

                return 'not_found';
            }

            if ($this->isUsed($id)) {
                $this->db->transRollback();

                return 'in_use';
            }

            $this->db->query('DELETE FROM customers WHERE id = ?', [$id]);
            $this->db->transCommit();

            return 'ok';
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }
}