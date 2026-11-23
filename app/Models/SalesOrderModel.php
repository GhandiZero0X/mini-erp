<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use Config\Documents;

class SalesOrderModel extends Model
{
    protected $table         = 'sales_orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public function listPage(string $search = '', string $status = '', int $perPage = 10): array
    {
        $this->select(
            'sales_orders.id, sales_orders.so_number, sales_orders.so_date,
             sales_orders.status, sales_orders.total_amount, c.name AS customer_name',
            false
        )->join('customers c', 'c.id = sales_orders.customer_id');

        if ($status !== '') { $this->where('sales_orders.status', $status); }
        if ($search !== '') {
            $this->groupStart()
                ->like('sales_orders.so_number', $search, 'both', null, true)
                ->orLike('c.name', $search, 'both', null, true)
                ->groupEnd();
        }

        $rows = $this->orderBy('sales_orders.so_date', 'DESC')
            ->orderBy('sales_orders.id', 'DESC')
            ->paginate($perPage);
        foreach ($rows as &$r) { $r['id'] = (int) $r['id']; }
        return $rows;
    }

    /** SO Open yang masih memiliki sisa belum dikirim (untuk Gudang di UI-11). */
    public function listShippable(string $search = '', int $perPage = 10): array
    {
        $active = Documents::DL_ACTIVE;
        $this->select(
            'sales_orders.id, sales_orders.so_number, sales_orders.so_date,
             sales_orders.status, sales_orders.total_amount, c.name AS customer_name',
            false
        )->join('customers c', 'c.id = sales_orders.customer_id')
            ->where('sales_orders.status', Documents::SO_OPEN)
            ->where(
                "EXISTS (SELECT 1 FROM sales_order_items soi
                         WHERE soi.sales_order_id = sales_orders.id
                           AND soi.quantity > COALESCE((SELECT SUM(di.quantity_delivered)
                                                        FROM delivery_items di
                                                        JOIN deliveries d ON d.id = di.delivery_id
                                                        WHERE di.sales_order_item_id = soi.id
                                                          AND d.status = '{$active}'), 0))",
                null,
                false
            );

        if ($search !== '') {
            $this->groupStart()
                ->like('sales_orders.so_number', $search, 'both', null, true)
                ->orLike('c.name', $search, 'both', null, true)
                ->groupEnd();
        }

        $rows = $this->orderBy('sales_orders.so_date', 'ASC')
            ->orderBy('sales_orders.id', 'ASC')
            ->paginate($perPage);
        foreach ($rows as &$r) { $r['id'] = (int) $r['id']; }
        return $rows;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('sales_orders so')
            ->select(
                'so.id, so.so_number, so.so_date, so.customer_id, so.status, so.total_amount,
                 so.created_at, so.cancelled_at, so.cancel_reason,
                 c.name AS customer_name, cu.full_name AS created_by_name,
                 xu.full_name AS cancelled_by_name',
                false
            )
            ->join('customers c', 'c.id = so.customer_id')
            ->join('users cu', 'cu.id = so.created_by')
            ->join('users xu', 'xu.id = so.cancelled_by', 'left')
            ->where('so.id', $id)
            ->get()->getRowArray();
        if (! $row) { return null; }
        $row['id']          = (int) $row['id'];
        $row['customer_id'] = (int) $row['customer_id'];
        return $row;
    }

    public function itemsOf(int $soId): array
    {
        $active    = Documents::DL_ACTIVE;
        $delivered = "(SELECT COALESCE(SUM(di.quantity_delivered), 0)
                       FROM delivery_items di
                       JOIN deliveries d ON d.id = di.delivery_id
                       WHERE di.sales_order_item_id = soi.id AND d.status = '{$active}')";
        $stock     = 'COALESCE((SELECT sb.quantity FROM stock_balances sb WHERE sb.product_id = soi.product_id), 0)';

        $rows = $this->db->query(
            "SELECT soi.id, soi.product_id, p.code, p.name, p.unit, soi.quantity, soi.unit_price, soi.subtotal,
                    {$delivered} AS delivered, {$stock} AS stock_available
             FROM sales_order_items soi
             JOIN products p ON p.id = soi.product_id
             WHERE soi.sales_order_id = ?
             ORDER BY soi.id",
            [$soId]
        )->getResultArray();

        foreach ($rows as &$r) {
            $r['id']              = (int) $r['id'];
            $r['product_id']      = (int) $r['product_id'];
            $r['quantity']        = (int) $r['quantity'];
            $r['delivered']       = (int) $r['delivered'];
            $r['stock_available'] = (int) $r['stock_available'];
        }
        return $rows;
    }

    public function deliveriesOf(int $soId): array
    {
        $rows = $this->db->query(
            'SELECT d.id, d.delivery_number, d.delivery_date, d.status,
                    (SELECT COALESCE(SUM(i.quantity_delivered), 0) FROM delivery_items i WHERE i.delivery_id = d.id) AS total_qty
             FROM deliveries d
             WHERE d.sales_order_id = ?
             ORDER BY d.delivery_date DESC, d.id DESC',
            [$soId]
        )->getResultArray();
        foreach ($rows as &$r) {
            $r['id']        = (int) $r['id'];
            $r['total_qty'] = (int) $r['total_qty'];
        }
        return $rows;
    }

    public function customerOptions(): array
    {
        return $this->db->table('customers')->select('id, name')
            ->orderBy('lower(name)', 'ASC', false)->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    /** Produk + stok saat ini (LEFT JOIN stock_balances) untuk pilihan di form SO. */
    public function productOptions(): array
    {
        return $this->db->query(
            'SELECT p.id, p.code, p.name, p.unit,
                    p.selling_price AS sale_price,          -- ← fix: alias agar cocok dengan view
                    COALESCE(sb.quantity, 0) AS stock_available
            FROM products p
            LEFT JOIN stock_balances sb ON sb.product_id = p.id
            ORDER BY lower(p.name) ASC, p.id ASC'
        )->getResultArray();
    }

    public function customerExists(int $id): bool
    {
        return ! empty($this->db->table('customers')->select('id')->where('id', $id)->get()->getRowArray());
    }

    public function existingProductIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) { return []; }
        $rows = $this->db->table('products')->select('id')->whereIn('id', $ids)->get()->getResultArray();
        return array_map(static fn($r) => (int) $r['id'], $rows);
    }

    /**
     * @param array{so_date:string,customer_id:int,total_amount:string,created_by:int} $h
     * @param list<array{product_id:int,quantity:int,unit_price:string,subtotal:string}> $items
     */
    public function createWithItems(array $h, array $items, ?DocumentNumberService $numbers = null): array
    {
        $numbers ??= new DocumentNumberService();
        $this->db->transBegin();
        try {
            $number = $numbers->next('sales_order', $h['so_date']);
            $row    = $this->run(
                'INSERT INTO sales_orders (so_number, so_date, customer_id, status, total_amount, created_by)
                 VALUES (?, ?, ?, ?, ?, ?) RETURNING id',
                [$number, $h['so_date'], $h['customer_id'], Documents::SO_OPEN, $h['total_amount'], $h['created_by']]
            )->getRowArray();
            $id = (int) $row['id'];
            foreach ($items as $it) {
                $this->run(
                    'INSERT INTO sales_order_items (sales_order_id, product_id, quantity, unit_price, subtotal)
                     VALUES (?, ?, ?, ?, ?)',
                    [$id, $it['product_id'], $it['quantity'], $it['unit_price'], $it['subtotal']]
                );
            }
            $this->db->transCommit();
            return ['id' => $id, 'so_number' => $number];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Batalkan SO (Open → Dibatalkan). Diblokir bila masih ada Delivery aktif.
     *
     * @return string 'ok'|'not_found'|'not_cancellable'|'has_active_delivery'
     */
    public function cancel(int $id, int $userId, string $reason): string
    {
        $this->db->transBegin();
        try {
            $row = $this->run('SELECT status FROM sales_orders WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if (! $row) { $this->db->transRollback(); return 'not_found'; }
            if ($row['status'] !== Documents::SO_OPEN) { $this->db->transRollback(); return 'not_cancellable'; }

            $active = Documents::DL_ACTIVE;
            $hasDl = $this->run(
                'SELECT 1 FROM deliveries WHERE sales_order_id = ? AND status = ? LIMIT 1',
                [$id, $active]
            )->getRowArray();
            if ($hasDl) { $this->db->transRollback(); return 'has_active_delivery'; }

            $this->run(
                'UPDATE sales_orders
                 SET status = ?, cancelled_by = ?, cancelled_at = now(), cancel_reason = ?, updated_at = now()
                 WHERE id = ?',
                [Documents::SO_CANCELLED, $userId, $reason, $id]
            );
            $this->db->transCommit();
            return 'ok';
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