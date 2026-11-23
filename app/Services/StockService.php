<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * Satu-satunya pintu perubahan stok (NFR-09, BR-06).
 *
 * Setiap perubahan selalu menulis dua hal sekaligus: baris stock_movements (append-only) dan
 * saldo stock_balances. Keduanya harus berada di transaction milik pemanggil (Goods Receipt,
 * Delivery, pembatalan), sehingga dokumen, movement, dan saldo commit atau rollback bersama (BR-08).
 *
 * Baris saldo dikunci dengan SELECT ... FOR UPDATE sebelum dibaca (NFR-07). Bila pemanggil memproses
 * beberapa produk, urutkan menurut product_id naik agar dua transaction tidak saling menunggu (deadlock).
 * Saldo tidak boleh negatif (BR-07): decrease() melempar InsufficientStockException.
 */
class StockService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect(); // koneksi bersama: sama dengan yang dipakai Model
    }

    /** Stok masuk (movement 'masuk', quantity_change positif). */
    public function increase(int $productId, int $quantity, string $refType, ?int $refId, ?string $refNumber, ?string $notes, int $userId): void
    {
        $this->apply($productId, 'masuk', $quantity, $refType, $refId, $refNumber, $notes, $userId);
    }

    /**
     * Stok keluar (movement 'keluar', quantity_change negatif).
     *
     * @throws InsufficientStockException bila saldo tidak cukup
     */
    public function decrease(int $productId, int $quantity, string $refType, ?int $refId, ?string $refNumber, ?string $notes, int $userId): void
    {
        $this->apply($productId, 'keluar', -$quantity, $refType, $refId, $refNumber, $notes, $userId);
    }

    /** Saldo saat ini tanpa mengunci (untuk tampilan). Produk tanpa baris saldo dianggap 0. */
    public function available(int $productId): int
    {
        $row = $this->db->table('stock_balances')->select('quantity')->where('product_id', $productId)->get()->getRowArray();

        return (int) ($row['quantity'] ?? 0);
    }

    private function apply(int $productId, string $type, int $change, string $refType, ?int $refId, ?string $refNumber, ?string $notes, int $userId): void
    {
        if ($this->db->transDepth < 1) {
            throw new \LogicException('StockService harus dipanggil di dalam transaction.');
        }
        if ($change === 0) {
            throw new \InvalidArgumentException('Perubahan stok tidak boleh 0.');
        }

        // Produk lama tanpa baris saldo: buat dulu (stok awal 0), lalu kunci barisnya
        $this->run('INSERT INTO stock_balances (product_id, quantity) VALUES (?, 0) ON CONFLICT (product_id) DO NOTHING', [$productId]);
        $row     = $this->run('SELECT quantity FROM stock_balances WHERE product_id = ? FOR UPDATE', [$productId])->getRowArray();
        $current = (int) $row['quantity'];
        $new     = $current + $change;

        if ($new < 0) {
            throw new InsufficientStockException($productId, $current, -$change);
        }

        $this->run(
            'INSERT INTO stock_movements
                (product_id, movement_type, quantity_change, reference_type, reference_id, reference_number, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$productId, $type, $change, $refType, $refId, $refNumber, $notes !== null ? mb_substr($notes, 0, 255) : null, $userId]
        );

        $this->run('UPDATE stock_balances SET quantity = ?, updated_at = now() WHERE product_id = ?', [$new, $productId]);
    }

    /** Bila driver mengembalikan false (DBDebug mati), ubah menjadi exception agar transaction pemanggil rollback. */
    private function run(string $sql, array $binds = [])
    {
        $result = $this->db->query($sql, $binds);
        if ($result === false) {
            throw new DatabaseException($this->db->error()['message'] ?? 'Query gagal dijalankan.');
        }

        return $result;
    }
}
