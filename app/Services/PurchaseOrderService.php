<?php

namespace App\Services;

use App\Models\PurchaseOrderModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Documents;

/**
 * Logika bisnis UC-07 Membuat Purchase Order dan UC-08 Menyetujui Purchase Order
 * (VR-06 s.d. VR-09, VR-25, BR-01, BR-08, BR-10).
 *
 * Setiap method aksi mengembalikan:
 *   ['ok' => bool, 'code' => 'ok'|'invalid'|'not_found'|'not_draft'|'failed',
 *    'errors' => [...], 'message' => string, 'id' => ?int]
 *
 * Bentuk 'errors' untuk form PO:
 *   ['supplier_id' => pesan, 'po_date' => pesan, 'items' => pesan umum,
 *    'item_rows' => [indeksBaris => ['product_id' => pesan, 'quantity' => pesan, 'unit_price' => pesan]]]
 *
 * Perhitungan uang memakai bilangan bulat sen (tanpa float) agar total persis (BR-10).
 */
class PurchaseOrderService
{
    /** Batas NUMERIC(15,2) dalam sen: 9.999.999.999.999,99. */
    private const MAX_CENTS = 999_999_999_999_999;

    private const MAX_ROWS = 100;

    private PurchaseOrderModel $orders;
    private array $msg;

    public function __construct(?PurchaseOrderModel $orders = null, ?\Config\Purchasing $cfg = null)
    {
        $this->orders = $orders ?? new PurchaseOrderModel();
        $this->msg    = ($cfg ?? config('Purchasing'))->messages;
    }

    /**
     * @param bool $receivableOnly true untuk Staf Gudang: hanya PO Approved yang belum diterima penuh (UI-07)
     *
     * @return array{rows: array, pager: mixed}
     */
    public function listPage(string $search = '', string $status = '', bool $receivableOnly = false): array
    {
        if ($receivableOnly) {
            $rows = $this->orders->listReceivable($search, 10);

            return ['rows' => $rows, 'pager' => $this->orders->pager];
        }

        if (! in_array($status, config('Documents')->poStatuses, true)) {
            $status = '';
        }

        $rows = $this->orders->listPage($search, $status, 10);

        return ['rows' => $rows, 'pager' => $this->orders->pager];
    }

    /** Header + item + sisa kuantitas (dipesan - diterima) untuk halaman detail (UI-09). */
    public function find(int $id): ?array
    {
        $po = $this->orders->findById($id);
        if ($po === null) {
            return null;
        }

        $items     = $this->orders->itemsOf($id);
        $remaining = 0;
        foreach ($items as &$it) {
            $it['remaining'] = max(0, $it['quantity'] - $it['received']);
            $remaining      += $it['remaining'];
        }
        unset($it);

        $po['items']      = $items;
        $po['receipts']   = $this->orders->receiptsOf($id);
        // Dapat diterima barangnya: Approved dan masih ada sisa (BR-02, VR-10)
        $po['receivable'] = $po['status'] === Documents::PO_APPROVED && $remaining > 0;

        return $po;
    }

    /** Pilihan supplier dan produk untuk form. */
    public function formData(): array
    {
        return [
            'suppliers' => $this->orders->supplierOptions(),
            'products'  => $this->orders->productOptions(),
        ];
    }

    /** Buat PO Draft beserta itemnya (UC-07 langkah 6-9). */
    public function create(array $input, int $userId): array
    {
        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);
        $errors = $this->checkReferences($in, $errors);

        if ($errors !== []) {
            return $this->invalid($errors);
        }

        [$header, $items] = self::buildPayload($in, $userId);

        try {
            $saved = $this->orders->createWithItems($header, $items);
        } catch (DatabaseException $e) {
            // Rollback penuh sudah dilakukan model (A6). Cek dulu apakah supplier/produk dihapus di sela proses.
            $errors = $this->checkReferences($in, []);
            if ($errors !== []) {
                return $this->invalid($errors);
            }
            log_message('error', 'Gagal menyimpan Purchase Order: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        $r       = $this->success('po_created');
        $r['id'] = $saved['id'];

        return $r;
    }

    /** Setujui PO Draft (UC-08). */
    public function approve(int $id, int $userId): array
    {
        try {
            $status = $this->orders->approve($id, $userId);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal menyetujui Purchase Order: ' . $e->getMessage());

            return $this->failure('save_failed');                                // A5
        }

        return match ($status) {
            'ok'        => $this->success('po_approved'),
            'not_found' => $this->failure('not_found', 'not_found'),             // A3
            'not_draft' => $this->failure('approve_not_draft', 'not_draft'),     // A1, VR-25
            'no_items'  => $this->failure('items_required', 'invalid'),
            default     => $this->failure('save_failed'),
        };
    }

    /** Batalkan PO Draft atau Approved tanpa penerimaan barang aktif (UC-10). */
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
            $status = $this->orders->cancel($id, $userId, $reason);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal membatalkan PO: ' . $e->getMessage());
            return $this->failure('save_failed');
        }

        return match ($status) {
            'ok'              => $this->success('po_cancelled'),
            'not_found'       => $this->failure('not_found', 'not_found'),
            'has_active_gr'   => $this->failure('po_cancel_has_gr', 'not_cancellable'),
            'not_cancellable' => $this->failure('po_cancel_not_allowed', 'not_cancellable'),
            default           => $this->failure('save_failed'),
        };
    }

    // ------------------------------------------------------------------
    // Validasi & perhitungan (murni, tanpa database, mudah diuji)
    // ------------------------------------------------------------------

    /**
     * Samakan tipe & buang spasi. Baris yang benar-benar kosong (produk dan kuantitas kosong)
     * diabaikan; indeks baris asli dipertahankan agar pesan error tampil di baris yang tepat.
     *
     * @return array{supplier_id:int, po_date:string, rows: array<int|string, array{product_id:int,quantity:string,unit_price:string}>}
     */
    public static function clean(array $input): array
    {
        $rows = [];
        $raw  = is_array($input['items'] ?? null) ? $input['items'] : [];

        foreach ($raw as $idx => $r) {
            if (! is_array($r)) {
                continue;
            }
            $product = trim((string) ($r['product_id'] ?? ''));
            $qty     = trim((string) ($r['quantity'] ?? ''));
            if ($product === '' && $qty === '') {
                continue; // baris kosong yang tidak diisi
            }
            $rows[$idx] = [
                'product_id' => ctype_digit($product) ? (int) $product : 0,
                'quantity'   => $qty,
                'unit_price' => trim((string) ($r['unit_price'] ?? '')),
            ];
        }

        $supplier = trim((string) ($input['supplier_id'] ?? ''));

        return [
            'supplier_id' => ctype_digit($supplier) ? (int) $supplier : 0,
            'po_date'     => trim((string) ($input['po_date'] ?? '')),
            'rows'        => $rows,
        ];
    }

    /** @return array<string,mixed> pesan error (kosong bila valid) */
    public static function validateFields(array $in, array $msg): array
    {
        $e = [];

        if ($in['supplier_id'] <= 0) {
            $e['supplier_id'] = $msg['supplier_required'];                       // VR-06
        }
        if (! self::isValidDate($in['po_date'])) {
            $e['po_date'] = $msg['date_invalid'];
        }

        if ($in['rows'] === []) {
            $e['items'] = $msg['items_required'];                                // VR-07
        } elseif (count($in['rows']) > self::MAX_ROWS) {
            $e['items'] = $msg['items_max'];
        }

        $rowErrors = [];
        $seen      = [];
        foreach ($in['rows'] as $idx => $r) {
            $re = [];

            if ($r['product_id'] <= 0) {
                $re['product_id'] = $msg['product_required'];
            } elseif (isset($seen[$r['product_id']])) {
                $re['product_id'] = $msg['product_duplicate'];
            } else {
                $seen[$r['product_id']] = true;
            }

            if (! self::isValidQuantity($r['quantity'])) {
                $re['quantity'] = $msg['qty_invalid'];                           // VR-08
            }
            if (! self::isValidPrice($r['unit_price'])) {
                $re['unit_price'] = $msg['price_invalid'];                       // VR-09
            }

            if ($re !== []) {
                $rowErrors[$idx] = $re;
            }
        }

        if ($rowErrors !== []) {
            $e['item_rows'] = $rowErrors;
        } elseif ($in['rows'] !== [] && ! isset($e['items']) && self::totalCents($in['rows']) === null) {
            $e['items'] = $msg['total_too_large'];
        }

        return $e;
    }

    /** Bilangan bulat > 0 dan muat di kolom integer. */
    public static function isValidQuantity(string $v): bool
    {
        return (bool) preg_match('/^[1-9]\d{0,9}$/', $v) && (int) $v <= 2147483647;
    }

    /** Wajib, numerik, >= 0, maksimal 2 desimal (titik), muat di NUMERIC(15,2). */
    public static function isValidPrice(string $v): bool
    {
        return (bool) preg_match('/^\d{1,13}(\.\d{1,2})?$/', $v);
    }

    public static function isValidDate(string $v): bool
    {
        return (bool) preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    /** "1700000.5" menjadi 170000050 (sen). Harus sudah lolos isValidPrice(). */
    public static function toCents(string $price): int
    {
        [$int, $dec] = array_pad(explode('.', $price, 2), 2, '');

        return ((int) $int) * 100 + (int) str_pad($dec, 2, '0');
    }

    public static function centsToString(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /** Subtotal satu baris dalam sen, atau null bila melebihi batas kolom (tanpa risiko overflow). */
    public static function lineCents(int $qty, int $priceCents): ?int
    {
        if ($qty > 0 && $priceCents > intdiv(self::MAX_CENTS, $qty)) {
            return null;
        }

        return $qty * $priceCents;
    }

    /** Total semua baris dalam sen (BR-10), atau null bila ada yang melebihi batas. Baris harus sudah valid. */
    public static function totalCents(array $rows): ?int
    {
        $total = 0;
        foreach ($rows as $r) {
            $line = self::lineCents((int) $r['quantity'], self::toCents($r['unit_price']));
            if ($line === null) {
                return null;
            }
            $total += $line;
            if ($total > self::MAX_CENTS) {
                return null;
            }
        }

        return $total;
    }

    /**
     * Bentuk data siap simpan. Subtotal dan total dihitung ulang di server, bukan dari kiriman form (BR-10).
     *
     * @return array{0: array, 1: list<array>} [header, items]
     */
    public static function buildPayload(array $in, int $userId): array
    {
        $items = [];
        $total = 0;

        foreach ($in['rows'] as $r) {
            $qty   = (int) $r['quantity'];
            $price = self::toCents($r['unit_price']);
            $line  = $qty * $price;
            $total += $line;

            $items[] = [
                'product_id' => $r['product_id'],
                'quantity'   => $qty,
                'unit_price' => self::centsToString($price),
                'subtotal'   => self::centsToString($line),
            ];
        }

        $header = [
            'po_date'      => $in['po_date'],
            'supplier_id'  => $in['supplier_id'],
            'total_amount' => self::centsToString($total),
            'created_by'   => $userId,
        ];

        return [$header, $items];
    }

    // ------------------------------------------------------------------

    /** Pastikan supplier dan produk yang dipilih masih ada di master data (UC-07 A5). */
    private function checkReferences(array $in, array $errors): array
    {
        if (! isset($errors['supplier_id']) && $in['supplier_id'] > 0 && ! $this->orders->supplierExists($in['supplier_id'])) {
            $errors['supplier_id'] = $this->msg['supplier_invalid'];
        }

        $ids      = array_filter(array_column($in['rows'], 'product_id'), static fn ($v) => $v > 0);
        $existing = array_flip($this->orders->existingProductIds($ids));
        foreach ($in['rows'] as $idx => $r) {
            if ($r['product_id'] > 0 && ! isset($existing[$r['product_id']])) {
                $errors['item_rows'][$idx]['product_id'] = $this->msg['product_invalid'];
            }
        }

        return $errors;
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