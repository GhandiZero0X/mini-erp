<?php

namespace App\Services;

use App\Models\CustomerModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Master as MasterConfig;

/**
 * Logika bisnis UC-06 Mengelola Customer (VR-22, VR-23, VR-24, VR-15).
 *
 * Setiap method mengembalikan:
 *   ['ok' => bool, 'code' => 'ok'|'invalid'|'not_found'|'in_use'|'failed',
 *    'errors' => [field => pesan], 'message' => string]
 *
 * Teks pesan dibaca dari Config\Master::$messages; kunci yang belum ada memakai DEFAULTS (teks Bab 6 SRS).
 */
class CustomerService
{
    public const DEFAULTS = [
        'customer_name_required'  => 'Nama wajib diisi.',                        // VR-22
        'customer_name_max'       => 'Nama maksimal 150 karakter.',              // *
        'customer_address_max'    => 'Alamat maksimal 500 karakter.',            // *
        'customer_phone_invalid'  => 'Format nomor telepon tidak valid.',        // VR-23
        'customer_email_invalid'  => 'Format email tidak valid.',                // VR-24
        'customer_created'        => 'Customer berhasil ditambahkan.',
        'customer_updated'        => 'Customer berhasil diperbarui.',
        'customer_deleted'        => 'Customer berhasil dihapus.',
        'in_use'                  => 'Data tidak dapat dihapus karena sudah digunakan pada %s.', // VR-15
        'not_found'               => 'Data tidak ditemukan.',                    // A6
        'save_failed'             => 'Data gagal disimpan. Silakan coba lagi.',  // A8
    ];

    private CustomerModel $customers;
    private array $msg;

    public function __construct(?CustomerModel $customers = null, ?MasterConfig $cfg = null)
    {
        $this->customers = $customers ?? new CustomerModel();
        $this->msg       = array_merge(self::DEFAULTS, ($cfg ?? config('Master'))->messages ?? []);
    }

    /**
     * Daftar halaman ini; $search = kata kunci nama (boleh kosong).
     * 'search' pada hasil adalah kata kunci yang sudah dibersihkan, dipakai lagi oleh view & redirect.
     *
     * @return array{rows: array, pager: mixed, search: string}
     */
    public function listPage(string $search = ''): array
    {
        $search = self::cleanSearch($search);
        $rows   = $this->customers->listPage(10, $search);

        return ['rows' => $rows, 'pager' => $this->customers->pager, 'search' => $search];
    }

    /** Kata kunci pencarian: ringkas spasi, maksimal 100 karakter (sama dengan maxlength di form). */
    public static function cleanSearch(mixed $q): string
    {
        $q = trim((string) preg_replace('/\s+/u', ' ', is_string($q) ? $q : ''));

        return mb_substr($q, 0, 100);
    }

    public function find(int $id): ?array
    {
        return $this->customers->findById($id);
    }

    /** Tambah customer (UC-06 langkah 3-6). */
    public function create(array $input): array
    {
        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $saved = $this->customers->insertCustomer($in);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal menyimpan customer: ' . $e->getMessage());

            return $this->failure('save_failed');                                // A8
        }

        return $saved ? $this->success('customer_created') : $this->failure('save_failed');
    }

    /** Ubah customer (UC-06 langkah 7-8). */
    public function update(int $id, array $input): array
    {
        if ($this->customers->findById($id) === null) {
            return $this->failure('not_found', 'not_found');                     // A6
        }

        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg);
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $affected = $this->customers->updateCustomer($id, $in);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal mengubah customer: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        // 0 baris terubah: customer dihapus Admin lain di sela proses
        return $affected > 0 ? $this->success('customer_updated') : $this->failure('not_found', 'not_found');
    }

    /** Hapus customer (UC-06 langkah 9-10). Ditolak bila sudah dipakai SO (VR-15, BR-09). */
    public function delete(int $id): array
    {
        try {
            $status = $this->customers->deleteIfUnused($id);
        } catch (DatabaseException $e) {
            // Foreign key RESTRICT adalah pengaman terakhir.
            log_message('warning', 'Hapus customer ditolak database: ' . $e->getMessage());

            return $this->customers->isUsed($id) ? $this->inUse() : $this->failure('save_failed');
        }

        return match ($status) {
            'ok'        => $this->success('customer_deleted'),
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
            $e['name'] = $msg['customer_name_required'];                         // VR-22
        } elseif (mb_strlen($in['name']) > 150) {
            $e['name'] = $msg['customer_name_max'];
        }

        if ($in['address'] !== null && mb_strlen($in['address']) > 500) {
            $e['address'] = $msg['customer_address_max'];
        }

        if ($in['phone'] !== null && ! self::isValidPhone($in['phone'])) {
            $e['phone'] = $msg['customer_phone_invalid'];                        // VR-23
        }

        if ($in['email'] !== null && ! self::isValidEmail($in['email'])) {
            $e['email'] = $msg['customer_email_invalid'];                        // VR-24
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
        return ['ok' => false, 'code' => 'in_use', 'errors' => [], 'message' => sprintf($this->msg['in_use'], 'Sales Order')];
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