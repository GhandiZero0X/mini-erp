<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use App\Exceptions\InsufficientStockException;
use App\Services\StockService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use Config\Documents;

/**
 * Akses data Goods Receipt (UC-09). Query memakai Query Builder / query parameterized (NFR-02).
 * Penyimpanan GR selalu satu transaction: header, item, stock movement, dan saldo stok (BR-06, BR-08).
 */
class GoodsReceiptModel extends Model
{
    protected $table         = 'goods_receipts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    /** Daftar GR + PO + supplier + total kuantitas, 10 baris per halaman, terbaru di atas. Pager di $this->pager. */
    public function listPage(string $search = '', int $perPage = 10): array
    {
        $this->select(
            'goods_receipts.id, goods_receipts.gr_number, goods_receipts.receipt_date, goods_receipts.status,
             po.po_number, s.name AS supplier_name,
             (SELECT COALESCE(SUM(i.quantity_received), 0) FROM goods_receipt_items i
               WHERE i.goods_receipt_id = goods_receipts.id) AS total_qty',
            false
        )
            ->join('purchase_orders po', 'po.id = goods_receipts.purchase_order_id')
            ->join('suppliers s', 's.id = po.supplier_id');

        if ($search !== '') {
            $this->groupStart()
                ->like('goods_receipts.gr_number', $search, 'both', null, true)
                ->orLike('po.po_number', $search, 'both', null, true)
                ->orLike('s.name', $search, 'both', null, true)
                ->groupEnd();
        }

        $rows = $this->orderBy('goods_receipts.receipt_date', 'DESC')
            ->orderBy('goods_receipts.id', 'DESC')
            ->paginate($perPage);

        foreach ($rows as &$row) {
            $row['id']        = (int) $row['id'];
            $row['total_qty'] = (int) $row['total_qty'];
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('goods_receipts gr')
            ->select(
                'gr.id, gr.gr_number, gr.purchase_order_id, gr.receipt_date, gr.status, gr.created_at,
                 gr.cancelled_at, gr.cancel_reason, po.po_number, s.name AS supplier_name,
                 cu.full_name AS created_by_name, xu.full_name AS cancelled_by_name',
                false
            )
            ->join('purchase_orders po', 'po.id = gr.purchase_order_id')
            ->join('suppliers s', 's.id = po.supplier_id')
            ->join('users cu', 'cu.id = gr.created_by')
            ->join('users xu', 'xu.id = gr.cancelled_by', 'left')
            ->where('gr.id', $id)
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }
        $row['id']                = (int) $row['id'];
        $row['purchase_order_id'] = (int) $row['purchase_order_id'];

        return $row;
    }

    /** Item GR beserta produk dan kuantitas dipesan pada PO. */
    /** Item GR beserta produk dan kuantitas dipesan pada PO. */
    public function itemsOf(int $grId): array
    {
        $rows = $this->db->query(
            'SELECT gri.id, p.code, p.name, u.code AS unit, poi.quantity AS ordered, gri.quantity_received
            FROM goods_receipt_items gri
            JOIN purchase_order_items poi ON poi.id = gri.purchase_order_item_id
            JOIN products p ON p.id = poi.product_id
            JOIN units u ON u.id = p.unit_id
            WHERE gri.goods_receipt_id = ?
            ORDER BY gri.id',
            [$grId]
        )->getResultArray();

        foreach ($rows as &$r) {
            $r['ordered']           = (int) $r['ordered'];
            $r['quantity_received'] = (int) $r['quantity_received'];
        }

        return $rows;
    }

    /**
     * Simpan GR dari PO Approved dalam satu transaction (UC-09 langkah 6-7):
     * kunci PO, hitung ulang sisa, simpan header + item, tambah stok, catat stock movement.
     * Baris PO dikunci (FOR UPDATE) agar dua GR bersamaan untuk PO yang sama tidak bisa
     * sama-sama melewati sisa kuantitas. Gagal di mana pun = rollback penuh (BR-08).
     *
     * @param array<int,int> $qtyByItem id item PO => kuantitas diterima (>= 0), untuk SEMUA item PO
     *
     * @return array{status:string, id?:int, gr_number?:string, remaining?:array<int,int>}
     *         status: 'ok' | 'not_found' | 'po_not_receivable' | 'exceeds' | 'nothing'
     */
    public function createFromPo(
        int $poId,
        string $date,
        array $qtyByItem,
        int $userId,
        ?DocumentNumberService $numbers = null,
        ?StockService $stock = null
    ): array {
        $numbers ??= new DocumentNumberService();
        $stock   ??= new StockService();

        $this->db->transBegin();

        try {
            $po = $this->run('SELECT status FROM purchase_orders WHERE id = ? FOR UPDATE', [$poId])->getRowArray();
            if (! $po) {
                $this->db->transRollback();

                return ['status' => 'not_found'];
            }
            if ($po['status'] !== Documents::PO_APPROVED) {
                $this->db->transRollback();

                return ['status' => 'po_not_receivable'];                         // VR-10, BR-02
            }

            // Sisa dihitung ulang setelah PO terkunci. Urut product_id naik (urutan kunci stok, anti-deadlock).
            $active = Documents::GR_ACTIVE;
            $items  = $this->run(
                "SELECT poi.id, poi.product_id, poi.quantity,
                        COALESCE((SELECT SUM(gri.quantity_received)
                                  FROM goods_receipt_items gri
                                  JOIN goods_receipts gr ON gr.id = gri.goods_receipt_id
                                  WHERE gri.purchase_order_item_id = poi.id AND gr.status = '{$active}'), 0) AS received
                 FROM purchase_order_items poi
                 WHERE poi.purchase_order_id = ?
                 ORDER BY poi.product_id",
                [$poId]
            )->getResultArray();

            $remaining  = [];
            $totalLeft  = 0;
            foreach ($items as $it) {
                $left                 = max(0, (int) $it['quantity'] - (int) $it['received']);
                $remaining[(int) $it['id']] = $left;
                $totalLeft           += $left;
            }
            if ($totalLeft === 0) {
                $this->db->transRollback();

                return ['status' => 'po_not_receivable'];                         // sudah diterima penuh
            }

            $lines = [];
            $exceeds = false;
            foreach ($items as $it) {
                $itemId = (int) $it['id'];
                $qty    = (int) ($qtyByItem[$itemId] ?? 0);
                if ($qty > $remaining[$itemId]) {
                    $exceeds = true;                                              // VR-11
                }
                if ($qty > 0) {
                    $lines[] = ['item_id' => $itemId, 'product_id' => (int) $it['product_id'], 'qty' => $qty];
                }
            }
            if ($exceeds) {
                $this->db->transRollback();

                return ['status' => 'exceeds', 'remaining' => $remaining];
            }
            if ($lines === []) {
                $this->db->transRollback();

                return ['status' => 'nothing'];                                   // VR-26
            }

            $number = $numbers->next('goods_receipt', $date);

            $row = $this->run(
                'INSERT INTO goods_receipts (gr_number, purchase_order_id, receipt_date, status, created_by)
                 VALUES (?, ?, ?, ?, ?) RETURNING id',
                [$number, $poId, $date, Documents::GR_ACTIVE, $userId]
            )->getRowArray();
            $grId = (int) $row['id'];

            foreach ($lines as $l) { // sudah urut product_id
                $this->run(
                    'INSERT INTO goods_receipt_items (goods_receipt_id, purchase_order_item_id, quantity_received) VALUES (?, ?, ?)',
                    [$grId, $l['item_id'], $l['qty']]
                );

                // BR-03, BR-06: stok bertambah sesuai barang yang benar-benar diterima + stock movement 'masuk'
                $stock->increase($l['product_id'], $l['qty'], 'GOODS_RECEIPT', $grId, $number, 'Penerimaan barang ' . $number, $userId);
            }

            $this->db->transCommit();

            return ['status' => 'ok', 'id' => $grId, 'gr_number' => $number];
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * Batalkan GR: Aktif → Dibatalkan, lalu stok dikurangi via StockService::decrease().
     * Stok tidak boleh negatif — decrease() melempar InsufficientStockException bila kurang.
     * Semua dalam satu transaction (BR-06, BR-07, BR-08).
     *
     * @return array{status:string, product?:int|string, stock?:int, needed?:int}
     *         status: 'ok' | 'not_found' | 'not_active' | 'insufficient_stock'
     */
    public function cancel(int $id, int $userId, string $reason): array
    {
        $this->db->transBegin();

        try {
            $gr = $this->run(
                'SELECT status, gr_number FROM goods_receipts WHERE id = ? FOR UPDATE',
                [$id]
            )->getRowArray();
            if (! $gr) {
                $this->db->transRollback();
                return ['status' => 'not_found'];
            }
            if ($gr['status'] !== Documents::GR_ACTIVE) {
                $this->db->transRollback();
                return ['status' => 'not_active'];
            }

            // Item yang pernah menambah stok, urut product_id (anti-deadlock pada decrease berurutan)
            $items = $this->run(
                'SELECT poi.product_id, p.name AS product_name, gri.quantity_received
                FROM goods_receipt_items gri
                JOIN purchase_order_items poi ON poi.id = gri.purchase_order_item_id
                JOIN products p ON p.id = poi.product_id
                WHERE gri.goods_receipt_id = ?
                ORDER BY poi.product_id',
                [$id]
            )->getResultArray();

            // Ubah status GR dulu
            $this->run(
                'UPDATE goods_receipts
                    SET status = ?, cancelled_by = ?, cancelled_at = now(),
                        cancel_reason = ?, updated_at = now()
                WHERE id = ?',
                [Documents::GR_CANCELLED, $userId, $reason, $id]
            );

            // Kembalikan stok. decrease() mengunci saldo, menulis movement 'keluar',
            // dan melempar InsufficientStockException bila saldo tidak cukup.
            $stock = new StockService();
            foreach ($items as $it) {
                $stock->decrease(
                    (int) $it['product_id'],
                    (int) $it['quantity_received'],
                    'GOODS_RECEIPT_CANCEL',
                    $id,
                    $gr['gr_number'],
                    'Pembatalan penerimaan barang ' . $gr['gr_number'],
                    $userId
                );
            }

            $this->db->transCommit();
            return ['status' => 'ok'];
        } catch (InsufficientStockException $e) {
            $this->db->transRollback();

            // Ambil nama produk dari $items agar pesan lebih ramah (pakai product_name di query)
            $productName = null;
            foreach ($items as $it) {
                if ((int) $it['product_id'] === $e->productId) {
                    $productName = $it['product_name'];
                    break;
                }
            }

            return [
                'status'  => 'insufficient_stock',
                'product' => $productName ?? ('#' . $e->productId),
                'stock'   => $e->available,
                'needed'  => $e->requested,   // ← namanya 'requested', bukan 'required'
            ];
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