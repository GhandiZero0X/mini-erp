<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use Config\Documents;

/**
 * Akses data Purchase Order dan itemnya (UC-07, UC-08).
 * Semua query memakai Query Builder / query parameterized (NFR-02).
 * Model ini TIDAK menyentuh stok: PO Draft/Approved tidak mengubah stok (BR-01).
 */
class PurchaseOrderModel extends Model
{
    protected $table         = 'purchase_orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // updated_at diisi manual (now() dari PostgreSQL)

    /**
     * Daftar PO + nama supplier, 10 baris per halaman, terbaru di atas (NFR-11).
     * $search mencari di nomor PO atau nama supplier; $status memfilter status (NFR-13). Pager di $this->pager.
     */
    public function listPage(string $search = '', string $status = '', int $perPage = 10): array
    {
        $this->select(
            'purchase_orders.id, purchase_orders.po_number, purchase_orders.po_date,
             purchase_orders.status, purchase_orders.total_amount, s.name AS supplier_name',
            false
        )->join('suppliers s', 's.id = purchase_orders.supplier_id');

        if ($status !== '') {
            $this->where('purchase_orders.status', $status);
        }
        if ($search !== '') {
            $this->groupStart()
                ->like('purchase_orders.po_number', $search, 'both', null, true)
                ->orLike('s.name', $search, 'both', null, true)
                ->groupEnd();
        }

        $rows = $this->orderBy('purchase_orders.po_date', 'DESC')
            ->orderBy('purchase_orders.id', 'DESC')
            ->paginate($perPage);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }

        return $rows;
    }

    /**
     * Daftar PO Approved yang masih memiliki sisa kuantitas belum diterima (UC-09 langkah 1, UI-07 untuk Gudang).
     * Sisa dihitung dari item GR berstatus Aktif. Pager di $this->pager.
     */
    public function listReceivable(string $search = '', int $perPage = 10): array
    {
        $active = Documents::GR_ACTIVE;

        $this->select(
            'purchase_orders.id, purchase_orders.po_number, purchase_orders.po_date,
             purchase_orders.status, purchase_orders.total_amount, s.name AS supplier_name',
            false
        )->join('suppliers s', 's.id = purchase_orders.supplier_id')
            ->where('purchase_orders.status', Documents::PO_APPROVED)
            ->where(
                "EXISTS (SELECT 1 FROM purchase_order_items poi
                         WHERE poi.purchase_order_id = purchase_orders.id
                           AND poi.quantity > COALESCE((SELECT SUM(gri.quantity_received)
                                                        FROM goods_receipt_items gri
                                                        JOIN goods_receipts gr ON gr.id = gri.goods_receipt_id
                                                        WHERE gri.purchase_order_item_id = poi.id
                                                          AND gr.status = '{$active}'), 0))",
                null,
                false
            );

        if ($search !== '') {
            $this->groupStart()
                ->like('purchase_orders.po_number', $search, 'both', null, true)
                ->orLike('s.name', $search, 'both', null, true)
                ->groupEnd();
        }

        $rows = $this->orderBy('purchase_orders.po_date', 'ASC') // yang paling lama menunggu di atas
            ->orderBy('purchase_orders.id', 'ASC')
            ->paginate($perPage);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }

        return $rows;
    }

    /** Riwayat GR sebuah PO (nomor, tanggal, status, total kuantitas). Kosong bila tabel GR belum ada. */
    public function receiptsOf(int $poId): array
    {
        if (! $this->db->tableExists('goods_receipts') || ! $this->db->tableExists('goods_receipt_items')) {
            return [];
        }

        $rows = $this->db->query(
            'SELECT gr.id, gr.gr_number, gr.receipt_date, gr.status,
                    (SELECT COALESCE(SUM(i.quantity_received), 0) FROM goods_receipt_items i WHERE i.goods_receipt_id = gr.id) AS total_qty
             FROM goods_receipts gr
             WHERE gr.purchase_order_id = ?
             ORDER BY gr.receipt_date DESC, gr.id DESC',
            [$poId]
        )->getResultArray();

        foreach ($rows as &$r) {
            $r['id']        = (int) $r['id'];
            $r['total_qty'] = (int) $r['total_qty'];
        }

        return $rows;
    }

    /** Header PO beserta nama supplier dan pengguna pencatat/penyetuju. */
    public function findById(int $id): ?array
    {
        $row = $this->db->table('purchase_orders po')
            ->select(
                'po.id, po.po_number, po.po_date, po.supplier_id, po.status, po.total_amount,
                 po.created_at, po.approved_at, po.cancelled_at, po.cancel_reason,
                 s.name AS supplier_name, cu.full_name AS created_by_name,
                 au.full_name AS approved_by_name, xu.full_name AS cancelled_by_name',
                false
            )
            ->join('suppliers s', 's.id = po.supplier_id')
            ->join('users cu', 'cu.id = po.created_by')
            ->join('users au', 'au.id = po.approved_by', 'left')
            ->join('users xu', 'xu.id = po.cancelled_by', 'left')
            ->where('po.id', $id)
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }
        $row['id']          = (int) $row['id'];
        $row['supplier_id'] = (int) $row['supplier_id'];

        return $row;
    }

    /**
     * Item PO beserta kuantitas yang sudah diterima (dari GR berstatus Aktif).
     * Sisa tidak disimpan, dihitung dari item GR Aktif. Bila tabel GR belum ada, diterima = 0.
     */
    public function itemsOf(int $poId): array
    {
        $received = '0';
        if ($this->db->tableExists('goods_receipt_items') && $this->db->tableExists('goods_receipts')) {
            // Status GR berasal dari konstanta, bukan input pengguna.
            $active   = Documents::GR_ACTIVE;
            $received = "(SELECT COALESCE(SUM(gri.quantity_received), 0)
                          FROM goods_receipt_items gri
                          JOIN goods_receipts gr ON gr.id = gri.goods_receipt_id
                          WHERE gri.purchase_order_item_id = poi.id AND gr.status = '{$active}')";
        }

        $rows = $this->db->query(
            "SELECT poi.id, poi.product_id, p.code, p.name, p.unit, poi.quantity, poi.unit_price, poi.subtotal,
                    {$received} AS received
             FROM purchase_order_items poi
             JOIN products p ON p.id = poi.product_id
             WHERE poi.purchase_order_id = ?
             ORDER BY poi.id",
            [$poId]
        )->getResultArray();

        foreach ($rows as &$r) {
            $r['id']         = (int) $r['id'];
            $r['product_id'] = (int) $r['product_id'];
            $r['quantity']   = (int) $r['quantity'];
            $r['received']   = (int) $r['received'];
        }

        return $rows;
    }

    /** Pilihan supplier untuk form, urut nama. */
    public function supplierOptions(): array
    {
        return $this->db->table('suppliers')->select('id, name')
            ->orderBy('lower(name)', 'ASC', false)->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    /** Pilihan produk untuk form (beserta satuan dan harga beli default), urut nama. */
    public function productOptions(): array
    {
        return $this->db->table('products')->select('id, code, name, unit, purchase_price')
            ->orderBy('lower(name)', 'ASC', false)->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    public function supplierExists(int $id): bool
    {
        $row = $this->db->table('suppliers')->select('id')->where('id', $id)->get()->getRowArray();

        return ! empty($row);
    }

    /** @return int[] id produk (dari $ids) yang benar-benar ada di master data */
    public function existingProductIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->db->table('products')->select('id')->whereIn('id', $ids)->get()->getResultArray();

        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /**
     * Simpan header + seluruh item dalam satu transaction (BR-08). Nomor PO dibuat di dalam
     * transaction yang sama agar aman dari nomor ganda. Gagal di mana pun = rollback penuh.
     *
     * @param array{po_date:string,supplier_id:int,total_amount:string,created_by:int} $h
     * @param list<array{product_id:int,quantity:int,unit_price:string,subtotal:string}> $items
     *
     * @return array{id:int, po_number:string}
     */
    public function createWithItems(array $h, array $items, ?DocumentNumberService $numbers = null): array
    {
        $numbers ??= new DocumentNumberService();

        $this->db->transBegin();

        try {
            $number = $numbers->next('purchase_order', $h['po_date']);

            $row = $this->run(
                'INSERT INTO purchase_orders (po_number, po_date, supplier_id, status, total_amount, created_by)
                 VALUES (?, ?, ?, ?, ?, ?) RETURNING id',
                [$number, $h['po_date'], $h['supplier_id'], Documents::PO_DRAFT, $h['total_amount'], $h['created_by']]
            )->getRowArray();
            $id = (int) $row['id'];

            foreach ($items as $it) {
                $this->run(
                    'INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price, subtotal)
                     VALUES (?, ?, ?, ?, ?)',
                    [$id, $it['product_id'], $it['quantity'], $it['unit_price'], $it['subtotal']]
                );
            }

            $this->db->transCommit();

            return ['id' => $id, 'po_number' => $number];
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * Setujui PO: Draft menjadi Approved, pengguna dan waktu persetujuan tercatat (UC-08, VR-25).
     * Baris PO dikunci (FOR UPDATE) agar dua persetujuan bersamaan tidak sama-sama lolos.
     * Stok tidak disentuh (BR-01).
     *
     * @return string 'ok' | 'not_found' | 'not_draft' | 'no_items'
     */
    public function approve(int $id, int $userId): string
    {
        $this->db->transBegin();

        try {
            $row = $this->run('SELECT status FROM purchase_orders WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if (! $row) {
                $this->db->transRollback();

                return 'not_found';
            }
            if ($row['status'] !== Documents::PO_DRAFT) {
                $this->db->transRollback();

                return 'not_draft';
            }

            $has = $this->run('SELECT 1 FROM purchase_order_items WHERE purchase_order_id = ? LIMIT 1', [$id])->getRowArray();
            if (! $has) {
                $this->db->transRollback();

                return 'no_items';
            }

            $this->run(
                'UPDATE purchase_orders SET status = ?, approved_by = ?, approved_at = now(), updated_at = now() WHERE id = ?',
                [Documents::PO_APPROVED, $userId, $id]
            );

            $this->db->transCommit();

            return 'ok';
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * Batalkan PO: Draft/Approved → Dibatalkan, dengan syarat tidak ada GR aktif.
     * Baris dikunci FOR UPDATE agar dua pembatalan bersamaan tidak lolos.
     *
     * @return string 'ok' | 'not_found' | 'not_cancellable' | 'has_active_gr'
     */
    public function cancel(int $id, int $userId, string $reason): string
    {
        $this->db->transBegin();

        try {
            $row = $this->run(
                'SELECT status FROM purchase_orders WHERE id = ? FOR UPDATE',
                [$id]
            )->getRowArray();

            if (! $row) {
                $this->db->transRollback();
                return 'not_found';
            }

            if (! in_array($row['status'], [Documents::PO_DRAFT, Documents::PO_APPROVED], true)) {
                $this->db->transRollback();
                return 'not_cancellable';
            }

            // Cegah pembatalan PO jika masih ada GR aktif (BR: stok sudah berubah karena GR)
            $active = Documents::GR_ACTIVE;
            $hasGr  = $this->run(
                'SELECT 1 FROM goods_receipts WHERE purchase_order_id = ? AND status = ? LIMIT 1',
                [$id, $active]
            )->getRowArray();
            if ($hasGr) {
                $this->db->transRollback();
                return 'has_active_gr';
            }

            $this->run(
                'UPDATE purchase_orders
                    SET status = ?, cancelled_by = ?, cancelled_at = now(),
                        cancel_reason = ?, updated_at = now()
                WHERE id = ?',
                [Documents::PO_CANCELLED, $userId, $reason, $id]
            );

            $this->db->transCommit();
            return 'ok';
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** Jalankan query; bila driver mengembalikan false (DBDebug mati), ubah menjadi exception agar rollback. */
    private function run(string $sql, array $binds = [])
    {
        $result = $this->db->query($sql, $binds);
        if ($result === false) {
            throw new DatabaseException($this->db->error()['message'] ?? 'Query gagal dijalankan.');
        }

        return $result;
    }
}