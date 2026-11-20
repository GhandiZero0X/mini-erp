<?php

namespace App\Services;

use App\Models\CategoryModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Master as MasterConfig;

/**
 * Logika bisnis UC-03 Mengelola Kategori (VR-15, VR-19, VR-20).
 *
 * Setiap method mengembalikan:
 *   ['ok' => bool, 'code' => 'ok'|'invalid'|'not_found'|'in_use'|'failed',
 *    'errors' => [field => pesan], 'message' => string]
 */
class CategoryService
{
    private CategoryModel $categories;
    private array $msg;

    public function __construct(?CategoryModel $categories = null, ?MasterConfig $cfg = null)
    {
        $this->categories = $categories ?? new CategoryModel();
        $this->msg        = ($cfg ?? config('Master'))->messages;
    }

    /** @return array{rows: array, pager: mixed} */
    public function listPage(): array
    {
        $rows = $this->categories->listPage(10);

        return ['rows' => $rows, 'pager' => $this->categories->pager];
    }

    public function find(int $id): ?array
    {
        return $this->categories->findById($id);
    }

    /** Tambah kategori (UC-03 langkah 3-6). */
    public function create(array $input): array
    {
        $name  = self::cleanName($input['name'] ?? '');
        $error = self::validateName($name, $this->msg);

        if ($error === null && $this->categories->nameExists($name)) {
            $error = $this->msg['category_name_taken'];                       // VR-20
        }
        if ($error !== null) {
            return $this->invalid($error);
        }

        try {
            $saved = $this->categories->insertCategory($name);
        } catch (DatabaseException $e) {
            // Dua Admin menyimpan nama sama bersamaan: pemeriksaan di atas lolos, constraint UNIQUE menolak
            return $this->afterWriteFailure($name, null, $e);
        }

        if (! $saved) {
            return $this->afterWriteFailure($name, null);
        }

        return $this->success('category_created');
    }

    /** Ubah nama kategori (UC-03 langkah 7-8). */
    public function update(int $id, array $input): array
    {
        if ($this->categories->findById($id) === null) {
            return $this->failure('not_found', 'not_found');                  // A5
        }

        $name  = self::cleanName($input['name'] ?? '');
        $error = self::validateName($name, $this->msg);

        if ($error === null && $this->categories->nameExists($name, $id)) {
            $error = $this->msg['category_name_taken'];                       // VR-20, nama sendiri dikecualikan
        }
        if ($error !== null) {
            return $this->invalid($error);
        }

        try {
            $affected = $this->categories->updateName($id, $name);
        } catch (DatabaseException $e) {
            return $this->afterWriteFailure($name, $id, $e);
        }

        // 0 baris terubah: kategori dihapus Admin lain di sela proses
        return $affected > 0 ? $this->success('category_updated') : $this->failure('not_found', 'not_found');
    }

    /** Hapus kategori (UC-03 langkah 9-10). Ditolak bila masih dipakai produk (VR-15, BR-09). */
    public function delete(int $id): array
    {
        if ($this->categories->findById($id) === null) {
            return $this->failure('not_found', 'not_found');                  // A5
        }
        if ($this->categories->productCount($id) > 0) {
            return $this->inUse();                                            // A3
        }

        try {
            $deleted = $this->categories->deleteById($id);
        } catch (DatabaseException $e) {
            // Foreign key RESTRICT adalah pengaman terakhir: produk baru memakai kategori ini
            // tepat di antara pemeriksaan dan penghapusan.
            log_message('warning', 'Hapus kategori ditolak database: ' . $e->getMessage());

            return $this->categories->productCount($id) > 0 ? $this->inUse() : $this->failure('save_failed');
        }

        if ($deleted > 0) {
            return $this->success('category_deleted');
        }

        return $this->categories->findById($id) === null
            ? $this->failure('not_found', 'not_found')
            : ($this->categories->productCount($id) > 0 ? $this->inUse() : $this->failure('save_failed'));
    }

    /** VR-19 + batas panjang kolom. Mengembalikan pesan, atau null bila valid. (Murni, mudah diuji.) */
    public static function validateName(string $name, array $msg): ?string
    {
        if ($name === '') {
            return $msg['category_name_required'];                            // VR-19
        }
        if (mb_strlen($name) > 100) {
            return $msg['category_name_max'];
        }

        return null;
    }

    /** Buang spasi di tepi dan ringkas spasi ganda ("Memory  RAM" menjadi "Memory RAM"). */
    public static function cleanName(mixed $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $name));
    }

    /** Penulisan gagal: pastikan dulu apakah penyebabnya nama kembar, kalau bukan baru dianggap kesalahan sistem (A7). */
    private function afterWriteFailure(string $name, ?int $exceptId, ?\Throwable $e = null): array
    {
        if ($this->categories->nameExists($name, $exceptId)) {
            return $this->invalid($this->msg['category_name_taken']);
        }
        if ($e !== null) {
            log_message('error', 'Gagal menyimpan kategori: ' . $e->getMessage());
        }

        return $this->failure('save_failed');
    }

    private function inUse(): array
    {
        return ['ok' => false, 'code' => 'in_use', 'errors' => [], 'message' => sprintf($this->msg['in_use'], 'produk')];
    }

    private function success(string $key): array
    {
        return ['ok' => true, 'code' => 'ok', 'errors' => [], 'message' => $this->msg[$key]];
    }

    private function failure(string $key, string $code = 'failed'): array
    {
        return ['ok' => false, 'code' => $code, 'errors' => [], 'message' => $this->msg[$key]];
    }

    private function invalid(string $nameError): array
    {
        return ['ok' => false, 'code' => 'invalid', 'errors' => ['name' => $nameError], 'message' => ''];
    }
}
