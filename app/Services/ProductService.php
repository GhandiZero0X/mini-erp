<?php

namespace App\Services;

use App\Models\ProductModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Master as MasterConfig;

class ProductService
{
    public const DEFAULTS = [
        'product_code_required'     => 'Kode produk wajib diisi.',
        'product_code_taken'        => 'Kode produk sudah digunakan.',
        'product_code_max'          => 'Kode produk maksimal 30 karakter.',
        'product_name_required'     => 'Nama produk wajib diisi.',
        'product_name_max'          => 'Nama produk maksimal 150 karakter.',
        'product_unit_required'     => 'Satuan wajib dipilih.',
        'product_unit_invalid'      => 'Satuan tidak valid.',
        'product_category_required' => 'Kategori wajib dipilih.',
        'product_category_invalid'  => 'Kategori tidak valid.',
        'product_price_invalid'     => 'Harga harus berupa angka dan tidak boleh negatif.',

        // ← BARU: pesan validasi batas stok
        'product_min_stock_invalid'       => 'Batas bawah harus berupa bilangan bulat ≥ 0.',
        'product_max_stock_invalid'       => 'Batas atas harus berupa bilangan bulat ≥ 0, atau dikosongkan.',
        'product_max_stock_less_than_min' => 'Batas atas tidak boleh kurang dari batas bawah.',

        'product_code_locked'       => 'Kode produk tidak dapat diubah karena sudah digunakan pada transaksi.',
        'product_created'           => 'Produk berhasil ditambahkan.',
        'product_updated'           => 'Produk berhasil diperbarui.',
        'product_deleted'           => 'Produk berhasil dihapus.',
        'in_use'                    => 'Data tidak dapat dihapus karena sudah digunakan pada %s.',
        'not_found'                 => 'Data tidak ditemukan.',
        'save_failed'               => 'Data gagal disimpan karena terjadi kesalahan sistem. Silakan coba lagi.',
    ];

    private ProductModel $products;
    private array $msg;

    public function __construct(?ProductModel $products = null, ?MasterConfig $cfg = null)
    {
        $this->products = $products ?? new ProductModel();
        $this->msg      = array_merge(self::DEFAULTS, ($cfg ?? config('Master'))->messages ?? []);
    }

    public function listPage(string $search = ''): array
    {
        $rows = $this->products->listPage($search, 10);

        return ['rows' => $rows, 'pager' => $this->products->pager];
    }

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

    public function unitOptions(): array
    {
        return $this->products->unitOptions();
    }

    public function create(array $input): array
    {
        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);

        if (! isset($errors['category_id']) && ! $this->products->categoryExists($in['category_id'])) {
            $errors['category_id'] = $this->msg['product_category_invalid'];
        }
        if (! isset($errors['unit_id']) && ! $this->products->unitExists($in['unit_id'])) {
            $errors['unit_id'] = $this->msg['product_unit_invalid'];
        }
        if (! isset($errors['code']) && $this->products->codeExists($in['code'])) {
            $errors['code'] = $this->msg['product_code_taken'];
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $this->products->insertProduct($this->payload($in));
        } catch (DatabaseException $e) {
            return $this->afterWriteFailure($in, null, $e);
        }

        return $this->success('product_created');
    }

    public function update(int $id, array $input): array
    {
        $current = $this->products->findById($id);
        if ($current === null) {
            return $this->failure('not_found', 'not_found');
        }

        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);

        if (! isset($errors['category_id']) && ! $this->products->categoryExists($in['category_id'])) {
            $errors['category_id'] = $this->msg['product_category_invalid'];
        }
        if (! isset($errors['unit_id']) && ! $this->products->unitExists($in['unit_id'])) {
            $errors['unit_id'] = $this->msg['product_unit_invalid'];
        }

        $warning    = '';
        $changeCode = $in['code'] !== $current['code'];
        if (! isset($errors['code']) && $changeCode) {
            if ($this->products->isUsed($id)) {
                $changeCode = false;
                $warning    = $this->msg['product_code_locked'];
            } elseif ($this->products->codeExists($in['code'], $id)) {
                $errors['code'] = $this->msg['product_code_taken'];
            }
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        $data = $this->payload($in);
        if (! $changeCode) {
            unset($data['code']);
        }

        try {
            $affected = $this->products->updateProduct($id, $data);
        } catch (DatabaseException $e) {
            return $this->afterWriteFailure($in, $id, $e);
        }

        if ($affected === 0) {
            return $this->failure('not_found', 'not_found');
        }

        $r            = $this->success('product_updated');
        $r['warning'] = $warning;

        return $r;
    }

    public function delete(int $id): array
    {
        try {
            $status = $this->products->deleteIfUnused($id);
        } catch (DatabaseException $e) {
            log_message('warning', 'Hapus produk ditolak database: ' . $e->getMessage());

            return $this->products->isUsed($id) ? $this->inUse() : $this->failure('save_failed');
        }

        return match ($status) {
            'ok'        => $this->success('product_deleted'),
            'not_found' => $this->failure('not_found', 'not_found'),
            default     => $this->inUse(),
        };
    }

    // ------------------------------------------------------------------
    // Validasi & normalisasi (murni, mudah diuji)
    // ------------------------------------------------------------------

    public static function validateFields(array $in, array $msg): array
    {
        $e = [];

        if ($in['code'] === '') {
            $e['code'] = $msg['product_code_required'];
        } elseif (mb_strlen($in['code']) > 30) {
            $e['code'] = $msg['product_code_max'];
        }

        if ($in['name'] === '') {
            $e['name'] = $msg['product_name_required'];
        } elseif (mb_strlen($in['name']) > 150) {
            $e['name'] = $msg['product_name_max'];
        }

        if ($in['unit_id'] <= 0) {
            $e['unit_id'] = $msg['product_unit_required'];
        }

        if ($in['category_id'] <= 0) {
            $e['category_id'] = $msg['product_category_required'];
        }

        foreach (['purchase_price', 'selling_price'] as $f) {
            if (! self::isValidPrice($in[$f])) {
                $e[$f] = $msg['product_price_invalid'];
            }
        }

        // ← BARU: validasi min_stock
        // clean() sudah memastikan min_stock bertipe int (dari cast), jadi tinggal cek >= 0
        if ($in['min_stock'] < 0) {
            $e['min_stock'] = $msg['product_min_stock_invalid'];
        }

        // ← BARU: validasi max_stock (boleh kosong = tanpa batas atas)
        if ($in['max_stock'] !== '') {
            if (! ctype_digit((string) $in['max_stock'])) {
                $e['max_stock'] = $msg['product_max_stock_invalid'];
            } elseif ((int) $in['max_stock'] < $in['min_stock']) {
                $e['max_stock'] = $msg['product_max_stock_less_than_min'];
            }
        }

        return $e;
    }

    public static function isValidPrice(string $v): bool
    {
        return (bool) preg_match('/^\d{1,13}(\.\d{1,2})?$/', $v);
    }

    /** Samakan tipe dan buang spasi di tepi; nama dirapatkan spasinya. */
    public static function clean(array $input): array
    {
        return [
            'code'           => trim((string) ($input['code'] ?? '')),
            'name'           => trim((string) preg_replace('/\s+/u', ' ', (string) ($input['name'] ?? ''))),
            'unit_id'        => (int) ($input['unit_id'] ?? 0),
            'purchase_price' => trim((string) ($input['purchase_price'] ?? '')),
            'selling_price'  => trim((string) ($input['selling_price'] ?? '')),
            'category_id'    => (int) ($input['category_id'] ?? 0),
            'min_stock'      => max(0, (int) ($input['min_stock'] ?? 0)),
            'max_stock'      => trim((string) ($input['max_stock'] ?? '')),
        ];
    }

    /** ← BARU: payload sekarang mengirim min_stock & max_stock ke Model. */
    private function payload(array $in): array
    {
        return [
            'code'           => $in['code'],
            'name'           => $in['name'],
            'unit_id'        => $in['unit_id'],
            'purchase_price' => $in['purchase_price'],
            'selling_price'  => $in['selling_price'],
            'category_id'    => $in['category_id'],
            'min_stock'      => $in['min_stock'],
            'max_stock'      => $in['max_stock'] === '' ? null : (int) $in['max_stock'],
        ];
    }

    private function afterWriteFailure(array $in, ?int $exceptId, \Throwable $e): array
    {
        if ($this->products->codeExists($in['code'], $exceptId)) {
            return $this->invalid(['code' => $this->msg['product_code_taken']]);
        }
        if (! $this->products->categoryExists($in['category_id'])) {
            return $this->invalid(['category_id' => $this->msg['product_category_invalid']]);
        }
        if (! $this->products->unitExists($in['unit_id'])) {
            return $this->invalid(['unit_id' => $this->msg['product_unit_invalid']]);
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