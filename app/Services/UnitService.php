<?php

namespace App\Services;

use App\Models\UnitModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Master as MasterConfig;

/**
 * Logika bisnis Master Satuan (Units): kode & nama wajib dan unik, satuan yang sudah dipakai produk tidak boleh dihapus (VR-15).
 *
 * Setiap method mengembalikan:
 *   ['ok' => bool, 'code' => 'ok'|'invalid'|'not_found'|'in_use'|'failed',
 *    'errors' => [field => pesan], 'message' => string]
 *
 * Teks pesan dibaca dari Config\Master::$messages; kunci yang belum ada memakai DEFAULTS.
 */
class UnitService
{
    public const DEFAULTS = [
        'unit_code_required' => 'Kode satuan wajib diisi.',
        'unit_code_taken'    => 'Kode satuan sudah digunakan.',
        'unit_code_max'      => 'Kode satuan maksimal 20 karakter.',
        'unit_code_format'   => 'Kode satuan hanya boleh berisi huruf dan angka, tanpa spasi.',
        'unit_name_required' => 'Nama satuan wajib diisi.',
        'unit_name_taken'    => 'Nama satuan sudah digunakan.',
        'unit_name_max'      => 'Nama satuan maksimal 50 karakter.',
        'unit_created'       => 'Satuan berhasil ditambahkan.',
        'unit_updated'       => 'Satuan berhasil diperbarui.',
        'unit_deleted'       => 'Satuan berhasil dihapus.',
        'in_use'             => 'Data tidak dapat dihapus karena sudah digunakan pada %s.', // VR-15
        'not_found'          => 'Data tidak ditemukan.',
        'save_failed'        => 'Data gagal disimpan. Silakan coba lagi.',
    ];

    private UnitModel $units;
    private array $msg;

    public function __construct(?UnitModel $units = null, ?MasterConfig $cfg = null)
    {
        $this->units = $units ?? new UnitModel();
        $this->msg   = array_merge(self::DEFAULTS, ($cfg ?? config('Master'))->messages ?? []);
    }

    /**
     * Daftar halaman ini; $search = kata kunci kode/nama (boleh kosong).
     * 'search' pada hasil adalah kata kunci yang sudah dibersihkan, dipakai lagi oleh view & redirect.
     *
     * @return array{rows: array, pager: mixed, search: string}
     */
    public function listPage(string $search = ''): array
    {
        $search = self::cleanSearch($search);
        $rows   = $this->units->listPage(10, $search);

        return ['rows' => $rows, 'pager' => $this->units->pager, 'search' => $search];
    }

    public function find(int $id): ?array
    {
        return $this->units->findById($id);
    }

    /** Tambah satuan. */
    public function create(array $input): array
    {
        $in     = self::clean($input);
        $errors = $this->validate($in, null);
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $saved = $this->units->insertUnit($in);
        } catch (DatabaseException $e) {
            // Dua Admin menyimpan kode sama bersamaan: pemeriksaan di atas lolos, UNIQUE di database menolak
            return $this->afterWriteFailure($in, null, $e);
        }

        return $saved ? $this->success('unit_created') : $this->afterWriteFailure($in, null);
    }

    /** Ubah satuan. Produk memakai id satuan, jadi mengubah kode/nama aman bagi data yang sudah ada. */
    public function update(int $id, array $input): array
    {
        if ($this->units->findById($id) === null) {
            return $this->failure('not_found', 'not_found');
        }

        $in     = self::clean($input);
        $errors = $this->validate($in, $id);
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $affected = $this->units->updateUnit($id, $in);
        } catch (DatabaseException $e) {
            return $this->afterWriteFailure($in, $id, $e);
        }

        // 0 baris terubah: satuan dihapus Admin lain di sela proses
        return $affected > 0 ? $this->success('unit_updated') : $this->failure('not_found', 'not_found');
    }

    /** Hapus satuan. Ditolak bila masih dipakai produk (VR-15, BR-09). */
    public function delete(int $id): array
    {
        try {
            $status = $this->units->deleteIfUnused($id);
        } catch (DatabaseException $e) {
            // Foreign key RESTRICT adalah pengaman terakhir.
            log_message('warning', 'Hapus satuan ditolak database: ' . $e->getMessage());

            return $this->units->isUsed($id) ? $this->inUse() : $this->failure('save_failed');
        }

        return match ($status) {
            'ok'        => $this->success('unit_deleted'),
            'not_found' => $this->failure('not_found', 'not_found'),
            default     => $this->inUse(),
        };
    }

    /** Validasi format (murni, tanpa database, mudah diuji). @return array<string,string> field => pesan */
    public static function validateFields(array $in, array $msg): array
    {
        $e = [];

        if ($in['code'] === '') {
            $e['code'] = $msg['unit_code_required'];
        } elseif (mb_strlen($in['code']) > 20) {
            $e['code'] = $msg['unit_code_max'];
        } elseif (! preg_match('/^[A-Z0-9]+$/', $in['code'])) {
            $e['code'] = $msg['unit_code_format'];
        }

        if ($in['name'] === '') {
            $e['name'] = $msg['unit_name_required'];
        } elseif (mb_strlen($in['name']) > 50) {
            $e['name'] = $msg['unit_name_max'];
        }

        return $e;
    }

    /** Format + keunikan kode & nama (keunikan hanya dicek untuk field yang formatnya sudah benar). */
    private function validate(array $in, ?int $exceptId): array
    {
        $errors = self::validateFields($in, $this->msg);

        if (! isset($errors['code']) && $this->units->codeExists($in['code'], $exceptId)) {
            $errors['code'] = $this->msg['unit_code_taken'];
        }
        if (! isset($errors['name']) && $this->units->nameExists($in['name'], $exceptId)) {
            $errors['name'] = $this->msg['unit_name_taken'];
        }

        return $errors;
    }

    /** Kode dijadikan huruf kapital; nama: spasi ganda diringkas ("Lusin  Kecil" menjadi "Lusin Kecil"). */
    public static function clean(array $input): array
    {
        return [
            'code' => mb_strtoupper(trim((string) ($input['code'] ?? ''))),
            'name' => trim((string) preg_replace('/\s+/u', ' ', (string) ($input['name'] ?? ''))),
        ];
    }

    /** Kata kunci pencarian: ringkas spasi, maksimal 100 karakter (sama dengan maxlength di form). */
    public static function cleanSearch(mixed $q): string
    {
        $q = trim((string) preg_replace('/\s+/u', ' ', is_string($q) ? $q : ''));

        return mb_substr($q, 0, 100);
    }

    /** Penulisan gagal: pastikan dulu apakah penyebabnya kode/nama kembar, kalau bukan baru dianggap kesalahan sistem. */
    private function afterWriteFailure(array $in, ?int $exceptId, ?\Throwable $e = null): array
    {
        $errors = [];
        if ($this->units->codeExists($in['code'], $exceptId)) {
            $errors['code'] = $this->msg['unit_code_taken'];
        }
        if ($this->units->nameExists($in['name'], $exceptId)) {
            $errors['name'] = $this->msg['unit_name_taken'];
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }
        if ($e !== null) {
            log_message('error', 'Gagal menyimpan satuan: ' . $e->getMessage());
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

    private function invalid(array $errors): array
    {
        return ['ok' => false, 'code' => 'invalid', 'errors' => $errors, 'message' => ''];
    }
}
