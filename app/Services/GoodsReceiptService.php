<?php

namespace App\Services;

use App\Models\GoodsReceiptModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * Logika bisnis UC-09 Mencatat Goods Receipt (VR-10, VR-11, VR-26, BR-02, BR-03, BR-06, BR-08).
 *
 * Setiap method aksi mengembalikan:
 *   ['ok' => bool, 'code' => 'ok'|'invalid'|'not_found'|'not_receivable'|'failed',
 *    'errors' => [...], 'message' => string, 'id' => ?int]
 *
 * Bentuk 'errors' untuk form GR:
 *   ['receipt_date' => pesan, 'items' => pesan umum (VR-26), 'quantities' => [idItemPO => pesan (VR-11)]]
 *
 * Validasi di sini memberi pesan cepat; pemeriksaan yang menentukan (status PO dan sisa kuantitas)
 * diulang di dalam transaction oleh model setelah PO dikunci, sehingga aman dari dua GR bersamaan.
 * Penambahan stok dilakukan model lewat StockService dalam transaction yang sama.
 */
class GoodsReceiptService
{
    private GoodsReceiptModel $receipts;
    private PurchaseOrderService $orders;
    private array $msg;

    public function __construct(?GoodsReceiptModel $receipts = null, ?PurchaseOrderService $orders = null, ?\Config\Purchasing $cfg = null)
    {
        $this->receipts = $receipts ?? new GoodsReceiptModel();
        $this->orders   = $orders ?? new PurchaseOrderService();
        $this->msg      = ($cfg ?? config('Purchasing'))->messages;
    }

    /** @return array{rows: array, pager: mixed} */
    public function listPage(string $search = ''): array
    {
        $rows = $this->receipts->listPage($search, 10);

        return ['rows' => $rows, 'pager' => $this->receipts->pager];
    }

    /** Header GR + item (UI-21). */
    public function find(int $id): ?array
    {
        $gr = $this->receipts->findById($id);
        if ($gr === null) {
            return null;
        }
        $gr['items']     = $this->receipts->itemsOf($id);
        $gr['total_qty'] = array_sum(array_column($gr['items'], 'quantity_received'));

        return $gr;
    }

    /** PO beserta item, kuantitas diterima, sisa, dan penanda 'receivable' untuk form GR (UI-10). */
    public function formData(int $poId): ?array
    {
        return $this->orders->find($poId);
    }

    /** Simpan GR dari PO Approved dan tambah stok (UC-09 langkah 5-7). */
    public function create(int $poId, array $input, int $userId): array
    {
        $po = $this->orders->find($poId);
        if ($po === null) {
            return $this->failure('not_found', 'not_found');
        }
        if (! $po['receivable']) {
            return $this->failure('gr_not_receivable', 'not_receivable');            // A1, VR-10
        }

        $in     = self::clean($input);
        $errors = self::validateFields($in, $po['items'], $this->msg);
        if ($errors !== []) {
            return $this->invalid($errors);                                          // A2, A3, A4
        }

        $qtyByItem = [];
        foreach ($po['items'] as $it) {
            $qtyByItem[$it['id']] = (int) ($in['quantities'][$it['id']] ?? 0);
        }

        try {
            $r = $this->receipts->createFromPo($poId, $in['receipt_date'], $qtyByItem, $userId);
        } catch (DatabaseException $e) {
            // Rollback penuh sudah dilakukan model: stok dan stock movement tidak berubah (A5, TC-04)
            log_message('error', 'Gagal menyimpan Goods Receipt: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        switch ($r['status']) {
            case 'ok':
                $res       = $this->success('gr_created');
                $res['id'] = $r['id'];

                return $res;

            case 'not_found':
                return $this->failure('not_found', 'not_found');

            case 'po_not_receivable':
                return $this->failure('gr_not_receivable', 'not_receivable');

            case 'exceeds': // sisa berubah di sela proses karena GR lain
                $q = [];
                foreach ($qtyByItem as $itemId => $qty) {
                    if ($qty > ($r['remaining'][$itemId] ?? 0)) {
                        $q[$itemId] = self::qtyMessage($this->msg, $r['remaining'][$itemId] ?? 0);
                    }
                }

                return $this->invalid(['quantities' => $q]);

            case 'nothing':
                return $this->invalid(['items' => $this->msg['gr_nothing']]);
        }

        return $this->failure('save_failed');
    }

    public function cancel(int $id, array $input, int $userId): array
    {
        $reason = trim((string) ($input['cancel_reason'] ?? ''));
        if ($reason === '') {
            return $this->invalid(['cancel_reason' => $this->msg['cancel_reason_required']]);
        }
        if (mb_strlen($reason) > 500) {
            return $this->invalid(['cancel_reason' => $this->msg['cancel_reason_max']]);
        }

        try {
            $r = $this->receipts->cancel($id, $userId, $reason);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal membatalkan GR: ' . $e->getMessage());
            return $this->failure('save_failed');
        }

        return match ($r['status']) {
            'ok'                  => $this->success('gr_cancelled'),
            'not_found'           => $this->failure('not_found', 'not_found'),
            'not_active'          => $this->failure('gr_cancel_not_allowed', 'not_cancellable'),
            'insufficient_stock'  => $this->failure(strtr($this->msg['gr_cancel_insufficient_stock'], [
                ':produk' => $r['product'],
                ':stok'   => (string) $r['stock'],
                ':butuh'  => (string) $r['needed'],
            ]), 'insufficient_stock'),
            default               => $this->failure('save_failed'),
        };
    }

    // ------------------------------------------------------------------
    // Validasi (murni, tanpa database, mudah diuji)
    // ------------------------------------------------------------------

    /**
     * @return array{receipt_date:string, quantities: array<int,string>}
     */
    public static function clean(array $input): array
    {
        $q = [];
        if (is_array($input['quantities'] ?? null)) {
            foreach ($input['quantities'] as $itemId => $v) {
                if (is_scalar($v) && ctype_digit((string) $itemId)) {
                    $q[(int) $itemId] = trim((string) $v);
                }
            }
        }

        return [
            'receipt_date' => trim((string) ($input['receipt_date'] ?? '')),
            'quantities'   => $q,
        ];
    }

    /**
     * @param list<array{id:int, remaining:int}> $poItems item PO beserta sisa
     *
     * @return array<string,mixed> pesan error (kosong bila valid)
     */
    public static function validateFields(array $in, array $poItems, array $msg): array
    {
        $e = [];

        if (! PurchaseOrderService::isValidDate($in['receipt_date'])) {
            $e['receipt_date'] = $msg['gr_date_invalid'];
        }

        $sum    = 0;
        $errors = [];
        foreach ($poItems as $it) {
            $raw = $in['quantities'][$it['id']] ?? '';

            // VR-11: wajib, bilangan bulat >= 0, dan <= sisa
            $ok = (bool) preg_match('/^(0|[1-9]\d{0,9})$/', $raw) && (int) $raw <= $it['remaining'];
            if (! $ok) {
                $errors[$it['id']] = self::qtyMessage($msg, (int) $it['remaining']);
                continue;
            }
            $sum += (int) $raw;
        }

        if ($errors !== []) {
            $e['quantities'] = $errors;
        } elseif ($sum === 0) {
            $e['items'] = $msg['gr_nothing'];                                        // VR-26
        }

        return $e;
    }

    private static function qtyMessage(array $msg, int $remaining): string
    {
        return str_replace('{sisa}', (string) $remaining, $msg['gr_qty_invalid']);
    }

    private function success(string $key): array
    {
        return ['ok' => true, 'code' => 'ok', 'errors' => [], 'message' => $this->msg[$key], 'id' => null];
    }

    private function failure(string $key, string $code = 'failed'): array
    {
        return ['ok' => false, 'code' => $code, 'errors' => [], 'message' => $this->msg[$key], 'id' => null];
    }

    private function invalid(array $errors): array
    {
        return ['ok' => false, 'code' => 'invalid', 'errors' => $errors, 'message' => '', 'id' => null];
    }
}