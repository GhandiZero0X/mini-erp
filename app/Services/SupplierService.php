<?php

namespace App\Services;

use App\Models\SupplierModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Master as MasterConfig;

/**
 * Logika bisnis UC-05 Mengelola Supplier (VR-22, VR-23, VR-24, VR-15).
 *
 * Setiap method mengembalikan:
 *   ['ok' => bool, 'code' => 'ok'|'invalid'|'not_found'|'in_use'|'failed',
 *    'errors' => [field => pesan], 'message' => string]
 *
 * Teks pesan dibaca dari Config\Master::$messages; kunci yang belum ada memakai DEFAULTS (teks Bab 6 SRS).
 */
class SupplierService
{
    public const DEFAULTS = [
        'supplier_name_required'  => 'Nama wajib diisi.',                        // VR-22
        'supplier_name_max'       => 'Nama maksimal 150 karakter.',              // *
        'supplier_address_max'    => 'Alamat maksimal 500 karakter.',            // *
        'supplier_phone_invalid'  => 'Format nomor telepon tidak valid.',        // VR-23
        'supplier_email_invalid'  => 'Format email tidak valid.',                // VR-24
        'supplier_created'        => 'Supplier berhasil ditambahkan.',
        'supplier_updated'        => 'Supplier berhasil diperbarui.',
        'supplier_deleted'        => 'Supplier berhasil dihapus.',
        'in_use'                  => 'Data tidak dapat dihapus karena sudah digunakan pada %s.', // VR-15
        'not_found'               => 'Data tidak ditemukan.',                    // A6
        'save_failed'             => 'Data gagal disimpan. Silakan coba lagi.',  // A8
    ];

    private SupplierModel $suppliers;
    private array $msg;

    public function __construct(?SupplierModel $suppliers = null, ?MasterConfig $cfg = null)
    {
        $this->suppliers = $suppliers ?? new SupplierModel();
        $this->msg       = array_merge(self::DEFAULTS, ($cfg ?? config('Master'))->messages ?? []);
    }

    /** @return array{rows: array, pager: mixed} */
    public function listPage(): array
    {
        $rows = $this->suppliers->listPage(10);

        return ['rows' => $rows, 'pager' => $this->suppliers->pager];
    }

    public function find(int $id): ?array
    {
        return $this->suppliers->findById($id);
    }

    /** Tambah supplier (UC-05 langkah 3-6). */
    public function create(array $input): array
    {
        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $saved = $this->suppliers->insertSupplier($in);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal menyimpan supplier: ' . $e->getMessage());

            return $this->failure('save_failed');                                // A8
        }

        return $saved ? $this->success('supplier_created') : $this->failure('save_failed');
    }

    /** Ubah supplier (UC-05 langkah 7-8). */
    public function update(int $id, array $input): array
    {
        if ($this->suppliers->findById($id) === null) {
            return $this->failure('not_found', 'not_found');                     // A6
        }

        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $affected = $this->suppliers->updateSupplier($id, $in);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal mengubah supplier: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        // 0 baris terubah: supplier dihapus Admin lain di sela proses
        return $affected > 0 ? $this->success('supplier_updated') : $this->failure('not_found', 'not_found');
    }

    /** Hapus supplier (UC-05 langkah 9-10). Ditolak bila sudah dipakai PO (VR-15, BR-09). */
    public function delete(int $id): array
    {
        try {
            $status = $this->suppliers->deleteIfUnused($id);
        } catch (DatabaseException $e) {
            // Foreign key RESTRICT adalah pengaman terakhir.
            log_message('warning', 'Hapus supplier ditolak database: ' . $e->getMessage());

            return $this->suppliers->isUsed($id) ? $this->inUse() : $this->failure('save_failed');
        }

        return match ($status) {
            'ok'        => $this->success('supplier_deleted'),
            'not_found' => $this->failure('not_found', 'not_found'),             // A6
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

        if ($in['name'] === '') {
            $e['name'] = $msg['supplier_name_required'];                         // VR-22
        } elseif (mb_strlen($in['name']) > 150) {
            $e['name'] = $msg['supplier_name_max'];
        }

        if ($in['address'] !== null && mb_strlen($in['address']) > 500) {
            $e['address'] = $msg['supplier_address_max'];
        }

        if ($in['phone'] !== null && ! self::isValidPhone($in['phone'])) {
            $e['phone'] = $msg['supplier_phone_invalid'];                        // VR-23
        }

        if ($in['email'] !== null && ! self::isValidEmail($in['email'])) {
            $e['email'] = $msg['supplier_email_invalid'];                        // VR-24
        }

        return $e;
    }

    /** VR-23: hanya angka, spasi, "+" dan "-", 8-15 digit; total panjang muat di kolom varchar(20). */
    public static function isValidPhone(string $v): bool
    {
        if (! preg_match('/^[0-9 +\-]+$/', $v) || strlen($v) > 20) {
            return false;
        }
        $digits = strlen(preg_replace('/\D/', '', $v));

        return $digits >= 8 && $digits <= 15;
    }

    /** VR-24: format email valid; panjang muat di kolom varchar(100). */
    public static function isValidEmail(string $v): bool
    {
        return mb_strlen($v) <= 100 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Samakan tipe & buang spasi di tepi. Kolom opsional yang kosong menjadi null (disimpan NULL). */
    public static function clean(array $input): array
    {
        $opt = static function (mixed $v): ?string {
            $v = trim((string) $v);

            return $v === '' ? null : $v;
        };

        return [
            'name'    => trim((string) preg_replace('/\s+/u', ' ', (string) ($input['name'] ?? ''))),
            'address' => $opt($input['address'] ?? ''),
            'phone'   => $opt($input['phone'] ?? ''),
            'email'   => $opt($input['email'] ?? ''),
        ];
    }

    private function inUse(): array
    {
        return ['ok' => false, 'code' => 'in_use', 'errors' => [], 'message' => sprintf($this->msg['in_use'], 'Purchase Order')];
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
