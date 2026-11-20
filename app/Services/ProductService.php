<?php

namespace App\Services;

use App\Models\ProductModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Master as MasterConfig;

/**
 * Logika bisnis UC-04 Mengelola Produk (VR-03, VR-04, VR-05, VR-15, VR-21).
 *
 * Setiap method mengembalikan:
 *   ['ok' => bool, 'code' => 'ok'|'invalid'|'not_found'|'in_use'|'failed',
 *    'errors' => [field => pesan], 'message' => string, 'warning' => string]
 *
 * Teks pesan dibaca dari Config\Master::$messages; kunci yang belum ada memakai DEFAULTS di bawah
 * (teks mengikuti Bab 6 SRS), jadi modul tetap jalan sebelum config dilengkapi.
 */
class ProductService
{
    public const DEFAULTS = [
        'product_code_required'     => 'Kode produk wajib diisi.',                                              // VR-03
        'product_code_taken'        => 'Kode produk sudah digunakan.',                                          // VR-03
        'product_code_max'          => 'Kode produk maksimal 30 karakter.',
        'product_name_required'     => 'Nama produk wajib diisi.',                                              // VR-04
        'product_name_max'          => 'Nama produk maksimal 150 karakter.',
        'product_unit_required'     => 'Satuan wajib diisi.',                                                   // VR-04
        'product_unit_max'          => 'Satuan maksimal 20 karakter.',
        'product_category_required' => 'Kategori wajib diisi.',                                                 // VR-04
        'product_category_invalid'  => 'Kategori tidak valid.',                                                 // VR-04, A6
        'product_price_invalid'     => 'Harga harus berupa angka dan tidak boleh negatif.',                     // VR-05
        'product_code_locked'       => 'Kode produk tidak dapat diubah karena sudah digunakan pada transaksi.', // VR-21
        'product_created'           => 'Produk berhasil ditambahkan.',
        'product_updated'           => 'Produk berhasil diperbarui.',
        'product_deleted'           => 'Produk berhasil dihapus.',
        'in_use'                    => 'Data tidak dapat dihapus karena sudah digunakan pada %s.',              // VR-15
        'not_found'                 => 'Data tidak ditemukan.',                                                 // A8
        'save_failed'               => 'Data gagal disimpan karena terjadi kesalahan sistem. Silakan coba lagi.', // A10
    ];

    private ProductModel $products;
    private array $msg;

    public function __construct(?ProductModel $products = null, ?MasterConfig $cfg = null)
    {
        $this->products = $products ?? new ProductModel();
        $this->msg      = array_merge(self::DEFAULTS, ($cfg ?? config('Master'))->messages ?? []);
    }

    /** @return array{rows: array, pager: mixed} */
    public function listPage(string $search = ''): array
    {
        $rows = $this->products->listPage($search, 10);

        return ['rows' => $rows, 'pager' => $this->products->pager];
    }

    /** Produk + penanda kode terkunci (sudah dipakai transaksi, VR-21). */
    public function find(int $id): ?array
    {
        $p = $this->products->findById($id);
        if ($p === null) {
            return null;
        }
        $p['code_locked'] = $this->products->isUsed($id);

        return $p;
    }

    public function categoryOptions(): array
    {
        return $this->products->categoryOptions();
    }

    /** Tambah produk (UC-04 langkah 3-6). Produk baru selalu berstok 0. */
    public function create(array $input): array
    {
        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);

        if (! isset($errors['category_id']) && ! $this->products->categoryExists($in['category_id'])) {
            $errors['category_id'] = $this->msg['product_category_invalid'];     // A6
        }
        if (! isset($errors['code']) && $this->products->codeExists($in['code'])) {
            $errors['code'] = $this->msg['product_code_taken'];                  // VR-03
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $this->products->insertProduct($this->payload($in));
        } catch (DatabaseException $e) {
            // Dua Admin menyimpan kode sama bersamaan, atau kategori dihapus di sela proses.
            return $this->afterWriteFailure($in, null, $e);
        }

        return $this->success('product_created');
    }

    /**
     * Ubah produk (UC-04 langkah 7-8). Bila kode sudah terkunci dan dicoba diubah, kode lama
     * dipertahankan, perubahan lain tetap disimpan, dan pesan VR-21 tampil sebagai peringatan (A5).
     */
    public function update(int $id, array $input): array
    {
        $current = $this->products->findById($id);
        if ($current === null) {
            return $this->failure('not_found', 'not_found');                     // A8
        }

        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);

        if (! isset($errors['category_id']) && ! $this->products->categoryExists($in['category_id'])) {
            $errors['category_id'] = $this->msg['product_category_invalid'];     // A6
        }

        $warning    = '';
        $changeCode = $in['code'] !== $current['code'];
        if (! isset($errors['code']) && $changeCode) {
            if ($this->products->isUsed($id)) {
                $changeCode = false;                                             // VR-21
                $warning    = $this->msg['product_code_locked'];
            } elseif ($this->products->codeExists($in['code'], $id)) {
                $errors['code'] = $this->msg['product_code_taken'];              // VR-03, kode sendiri dikecualikan
            }
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        $data = $this->payload($in);
        if (! $changeCode) {
            unset($data['code']);                                                // kode lama dipertahankan
        }

        try {
            $affected = $this->products->updateProduct($id, $data);
        } catch (DatabaseException $e) {
            return $this->afterWriteFailure($in, $id, $e);
        }

        if ($affected === 0) {
            return $this->failure('not_found', 'not_found');                     // dihapus Admin lain di sela proses
        }

        $r            = $this->success('product_updated');
        $r['warning'] = $warning;

        return $r;
    }

    /** Hapus produk (UC-04 langkah 9-10). Ditolak bila sudah dipakai (VR-15, BR-09). */
    public function delete(int $id): array
    {
        try {
            $status = $this->products->deleteIfUnused($id);
        } catch (DatabaseException $e) {
            // Foreign key RESTRICT adalah pengaman terakhir.
            log_message('warning', 'Hapus produk ditolak database: ' . $e->getMessage());

            return $this->products->isUsed($id) ? $this->inUse() : $this->failure('save_failed');
        }

        return match ($status) {
            'ok'        => $this->success('product_deleted'),
            'not_found' => $this->failure('not_found', 'not_found'),             // A8
            default     => $this->inUse(),                                       // A4
        };
    }

    /**
     * Validasi field (murni, tanpa database, mudah diuji).
     *
     * @return array<string,string> field => pesan
     */
    public static function validateFields(array $in, array $msg): array
    {
        $e = [];

        if ($in['code'] === '') {
            $e['code'] = $msg['product_code_required'];                          // VR-03
        } elseif (mb_strlen($in['code']) > 30) {
            $e['code'] = $msg['product_code_max'];
        }

        if ($in['name'] === '') {
            $e['name'] = $msg['product_name_required'];                          // VR-04
        } elseif (mb_strlen($in['name']) > 150) {
            $e['name'] = $msg['product_name_max'];
        }

        if ($in['unit'] === '') {
            $e['unit'] = $msg['product_unit_required'];                          // VR-04
        } elseif (mb_strlen($in['unit']) > 20) {
            $e['unit'] = $msg['product_unit_max'];
        }

        if ($in['category_id'] <= 0) {
            $e['category_id'] = $msg['product_category_required'];               // VR-04
        }

        foreach (['purchase_price', 'selling_price'] as $f) {                     // VR-05
            if (! self::isValidPrice($in[$f])) {
                $e[$f] = $msg['product_price_invalid'];
            }
        }

        return $e;
    }

    /** Wajib, numerik, >= 0, maksimal 2 desimal (titik sebagai pemisah desimal), muat di NUMERIC(15,2). */
    public static function isValidPrice(string $v): bool
    {
        return (bool) preg_match('/^\d{1,13}(\.\d{1,2})?$/', $v);
    }

    /** Samakan tipe, buang spasi di tepi, dan ringkas spasi ganda pada nama. */
    public static function clean(array $input): array
    {
        return [
            'code'           => trim((string) ($input['code'] ?? '')),
            'name'           => trim((string) preg_replace('/\s+/u', ' ', (string) ($input['name'] ?? ''))),
            'unit'           => trim((string) ($input['unit'] ?? '')),
            'purchase_price' => trim((string) ($input['purchase_price'] ?? '')),
            'selling_price'  => trim((string) ($input['selling_price'] ?? '')),
            'category_id'    => (int) ($input['category_id'] ?? 0),
        ];
    }

    private function payload(array $in): array
    {
        return [
            'code'           => $in['code'],
            'name'           => $in['name'],
            'unit'           => $in['unit'],
            'purchase_price' => $in['purchase_price'],
            'selling_price'  => $in['selling_price'],
            'category_id'    => $in['category_id'],
        ];
    }

    /** Penulisan gagal: cek dulu apakah penyebabnya kode kembar atau kategori hilang, kalau bukan baru kesalahan sistem (A10). */
    private function afterWriteFailure(array $in, ?int $exceptId, \Throwable $e): array
    {
        if ($this->products->codeExists($in['code'], $exceptId)) {
            return $this->invalid(['code' => $this->msg['product_code_taken']]);
        }
        if (! $this->products->categoryExists($in['category_id'])) {
            return $this->invalid(['category_id' => $this->msg['product_category_invalid']]);
        }
        log_message('error', 'Gagal menyimpan produk: ' . $e->getMessage());

        return $this->failure('save_failed');
    }

    private function inUse(): array
    {
        return [
            'ok' => false, 'code' => 'in_use', 'errors' => [], 'warning' => '',
            'message' => sprintf($this->msg['in_use'], 'transaksi atau catatan stok'),
        ];
    }

    private function success(string $key): array
    {
        return ['ok' => true, 'code' => 'ok', 'errors' => [], 'message' => $this->msg[$key], 'warning' => ''];
    }

    private function failure(string $key, string $code = 'failed'): array
    {
        return ['ok' => false, 'code' => $code, 'errors' => [], 'message' => $this->msg[$key], 'warning' => ''];
    }

    private function invalid(array $errors): array
    {
        return ['ok' => false, 'code' => 'invalid', 'errors' => $errors, 'message' => '', 'warning' => ''];
    }
}
