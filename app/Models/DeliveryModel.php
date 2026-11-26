<?php

namespace App\Models;

use App\Exceptions\InsufficientStockException;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use Config\Documents;

class DeliveryModel extends Model
{
    protected $table         = 'deliveries';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public function listPage(string $search = '', int $perPage = 10): array
    {
        $this->select(
            'deliveries.id, deliveries.delivery_number, deliveries.delivery_date, deliveries.status,
             so.so_number, c.name AS customer_name,
             (SELECT COALESCE(SUM(i.quantity_delivered), 0) FROM delivery_items i
               WHERE i.delivery_id = deliveries.id) AS total_qty',
            false
        )
            ->join('sales_orders so', 'so.id = deliveries.sales_order_id')
            ->join('customers c', 'c.id = so.customer_id');

        if ($search !== '') {
            $this->groupStart()
                ->like('deliveries.delivery_number', $search, 'both', null, true)
                ->orLike('so.so_number', $search, 'both', null, true)
                ->orLike('c.name', $search, 'both', null, true)
                ->groupEnd();
        }

        $rows = $this->orderBy('deliveries.delivery_date', 'DESC')
            ->orderBy('deliveries.id', 'DESC')
            ->paginate($perPage);
        foreach ($rows as &$r) {
            $r['id']        = (int) $r['id'];
            $r['total_qty'] = (int) $r['total_qty'];
        }
        return $rows;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('deliveries d')
            ->select(
                'd.id, d.delivery_number, d.sales_order_id, d.delivery_date, d.status, d.created_at,
                 d.cancelled_at, d.cancel_reason, so.so_number, c.name AS customer_name,
                 cu.full_name AS created_by_name, xu.full_name AS cancelled_by_name',
                false
            )
            ->join('sales_orders so', 'so.id = d.sales_order_id')
            ->join('customers c', 'c.id = so.customer_id')
            ->join('users cu', 'cu.id = d.created_by')
            ->join('users xu', 'xu.id = d.cancelled_by', 'left')
            ->where('d.id', $id)
            ->get()->getRowArray();
        if (! $row) { return null; }
        $row['id']             = (int) $row['id'];
        $row['sales_order_id'] = (int) $row['sales_order_id'];
        return $row;
    }

    public function itemsOf(int $deliveryId): array
    {
        $rows = $this->db->query(
            'SELECT di.id, p.code, p.name, u.code AS unit, soi.quantity AS ordered, di.quantity_delivered
            FROM delivery_items di
            JOIN sales_order_items soi ON soi.id = di.sales_order_item_id
            JOIN products p ON p.id = soi.product_id
            JOIN units u ON u.id = p.unit_id
            WHERE di.delivery_id = ?
            ORDER BY di.id',
            [$deliveryId]
        )->getResultArray();

        foreach ($rows as &$r) {
            $r['ordered']            = (int) $r['ordered'];
            $r['quantity_delivered'] = (int) $r['quantity_delivered'];
        }
        return $rows;
    }

    /**
     * Catat Delivery dari SO Open. Kunci SO, hitung ulang sisa, insert header+item,
     * kurangi stok via StockService (yang mencatat stock_movement 'keluar' & mengunci saldo).
     * Bila stok tidak cukup → InsufficientStockException → rollback penuh (BR-06, BR-07, BR-08).
     *
     * @param array<int,int> $qtyByItem id item SO => kuantitas dikirim
     * @return array{status:string, id?:int, delivery_number?:string, product?:string, stock?:int, needed?:int, remaining?:array}
     */
    public function createFromSo(
        int $soId,
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
            $so = $this->run('SELECT status FROM sales_orders WHERE id = ? FOR UPDATE', [$soId])->getRowArray();
            if (! $so) { $this->db->transRollback(); return ['status' => 'not_found']; }
            if ($so['status'] !== Documents::SO_OPEN) { $this->db->transRollback(); return ['status' => 'so_not_shippable']; }

            $active = Documents::DL_ACTIVE;
            $items  = $this->run(
                "SELECT soi.id, soi.product_id, p.name AS product_name, soi.quantity,
                        COALESCE((SELECT SUM(di.quantity_delivered)
                                  FROM delivery_items di
                                  JOIN deliveries d ON d.id = di.delivery_id
                                  WHERE di.sales_order_item_id = soi.id AND d.status = '{$active}'), 0) AS delivered
                 FROM sales_order_items soi
                 JOIN products p ON p.id = soi.product_id
                 WHERE soi.sales_order_id = ?
                 ORDER BY soi.product_id",
                [$soId]
            )->getResultArray();

            $remaining = [];
            $totalLeft = 0;
            foreach ($items as $it) {
                $left = max(0, (int) $it['quantity'] - (int) $it['delivered']);
                $remaining[(int) $it['id']] = $left;
                $totalLeft += $left;
            }
            if ($totalLeft === 0) { $this->db->transRollback(); return ['status' => 'so_not_shippable']; }

            $lines   = [];
            $exceeds = false;
            $isFullyDelivered = true; // <-- Tambahkan variabel ini

            foreach ($items as $it) {
                $itemId = (int) $it['id'];
                $qty    = (int) ($qtyByItem[$itemId] ?? 0);
                
                if ($qty > $remaining[$itemId]) { $exceeds = true; }
                if ($qty > 0) {
                    $lines[] = [
                        'item_id'      => $itemId,
                        'product_id'   => (int) $it['product_id'],
                        'product_name' => $it['product_name'],
                        'qty'          => $qty,
                    ];
                }

                // <-- TAMBAHKAN PENGECEKAN INI -->
                if (($remaining[$itemId] - $qty) > 0) {
                    $isFullyDelivered = false;
                }
            }
            if ($exceeds) { $this->db->transRollback(); return ['status' => 'exceeds', 'remaining' => $remaining]; }
            if ($lines === []) { $this->db->transRollback(); return ['status' => 'nothing']; }

            $number = $numbers->next('delivery', $date);
            $row    = $this->run(
                'INSERT INTO deliveries (delivery_number, sales_order_id, delivery_date, status, created_by)
                 VALUES (?, ?, ?, ?, ?) RETURNING id',
                [$number, $soId, $date, Documents::DL_ACTIVE, $userId]
            )->getRowArray();
            $deliveryId = (int) $row['id'];

            foreach ($lines as $l) {
                $this->run(
                    'INSERT INTO delivery_items (delivery_id, sales_order_item_id, quantity_delivered) VALUES (?, ?, ?)',
                    [$deliveryId, $l['item_id'], $l['qty']]
                );
                $stock->decrease(
                    $l['product_id'], $l['qty'], 'DELIVERY', $deliveryId, $number,
                    'Pengiriman barang ' . $number, $userId
                );
            }

            // <-- TAMBAHKAN BLOK INI UNTUK MENGUBAH STATUS DI DATABASE -->
            if ($isFullyDelivered) {
                $this->run('UPDATE sales_orders SET status = ?, updated_at = now() WHERE id = ?', [Documents::SO_CLOSED, $soId]);
            }

            $this->db->transCommit();
            return ['status' => 'ok', 'id' => $deliveryId, 'delivery_number' => $number];
        } catch (InsufficientStockException $e) {
            $this->db->transRollback();
            $name = null;
            if (isset($lines)) {
                foreach ($lines as $l) {
                    if ($l['product_id'] === $e->productId) { $name = $l['product_name']; break; }
                }
            }
            return [
                'status'  => 'insufficient_stock',
                'product' => $name ?? ('#' . $e->productId),
                'stock'   => $e->available,
                'needed'  => $e->requested,
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

        public function cancel(int $id, int $userId, string $reason): array
    {
        $this->db->transBegin();
        try {
            // 1. Ambil data DO beserta sales_order_id (TAMBAHKAN sales_order_id)
            $dl = $this->run(
                'SELECT status, delivery_number, sales_order_id FROM deliveries WHERE id = ? FOR UPDATE',
                [$id]
            )->getRowArray();
            
            if (! $dl) { $this->db->transRollback(); return ['status' => 'not_found']; }
            if ($dl['status'] !== Documents::DL_ACTIVE) { $this->db->transRollback(); return ['status' => 'not_active']; }

            // 2. Ambil item untuk mengembalikan stok
            $items = $this->run(
                'SELECT soi.product_id, di.quantity_delivered
                 FROM delivery_items di
                 JOIN sales_order_items soi ON soi.id = di.sales_order_item_id
                 WHERE di.delivery_id = ?
                 ORDER BY soi.product_id',
                [$id]
            )->getResultArray();

            // 3. Ubah status Delivery menjadi Dibatalkan
            $this->run(
                'UPDATE deliveries
                 SET status = ?, cancelled_by = ?, cancelled_at = now(), cancel_reason = ?, updated_at = now()
                 WHERE id = ?',
                [Documents::DL_CANCELLED, $userId, $reason, $id]
            );

            // 4. KEMBALIKAN STATUS SO MENJADI OPEN
            $this->run(
                'UPDATE sales_orders SET status = ?, updated_at = now() WHERE id = ?', 
                [Documents::SO_OPEN, $dl['sales_order_id']]
            );

            // 5. Kembalikan stok produk
            $stock = new StockService();
            foreach ($items as $it) {
                $stock->increase(
                    (int) $it['product_id'],
                    (int) $it['quantity_delivered'],
                    'DELIVERY_CANCEL',
                    $id,
                    $dl['delivery_number'],
                    'Pembatalan pengiriman barang ' . $dl['delivery_number'],
                    $userId
                );
            }

            $this->db->transCommit();
            return ['status' => 'ok'];
            
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function run(string $sql, array $binds = [])
    {
        $result = $this->db->query($sql, $binds);
        if ($result === false) {
            throw new DatabaseException($this->db->error()['message'] ?? 'Query gagal dijalankan.');
        }
        return $result;
    }
}