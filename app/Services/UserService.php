<?php

namespace App\Services;

use App\Models\RoleModel;
use App\Models\UserModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Auth as AuthConfig;

/**
 * Logika bisnis UC-21 Kelola Akun Pengguna (VR-33 s.d. VR-37).
 * Controller hanya memanggil kelas ini dan menampilkan hasilnya.
 *
 * Semua method mengembalikan array:
 *   ['ok' => bool, 'code' => string, 'errors' => [field => pesan], 'message' => string]
 */
class UserService
{
    private UserModel $users;
    private RoleModel $roles;
    private array $msg;

    public function __construct(?UserModel $users = null, ?RoleModel $roles = null, ?AuthConfig $cfg = null)
    {
        $this->users = $users ?? new UserModel();
        $this->roles = $roles ?? new RoleModel();
        $this->msg   = ($cfg ?? config('Auth'))->userMessages;
    }

    /** @return array{rows: array, pager: mixed} */
    public function listPage(): array
    {
        $rows = $this->users->listPage(10);

        return ['rows' => $rows, 'pager' => $this->users->pager];
    }

    public function find(int $id): ?array
    {
        return $this->users->findById($id);
    }

    public function roleOptions(): array
    {
        return $this->roles->all();
    }

    /** Tambah akun (UC-21 langkah 3-6). */
    public function create(array $input): array
    {
        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg, true, true);

        if (! isset($errors['role_id']) && ! $this->roles->exists($in['role_id'])) {
            $errors['role_id'] = $this->msg['role_required'];
        }
        if (! isset($errors['username']) && $this->users->usernameExists($in['username'])) {
            $errors['username'] = $this->msg['username_taken'];
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $saved = $this->users->insertUser(
                $in['full_name'],
                $in['username'],
                password_hash($in['password'], PASSWORD_DEFAULT), // hanya hash yang disimpan (NFR-01)
                $in['role_id']
            );
        } catch (DatabaseException $e) {
            // Dua Admin menyimpan username sama bersamaan: pemeriksaan di atas lolos, constraint UNIQUE menolak.
            // Dicek ulang ke database (tidak bergantung pada teks/nama constraint dari PostgreSQL).
            if ($this->users->usernameExists($in['username'])) {
                return $this->invalid(['username' => $this->msg['username_taken']]);
            }
            log_message('error', 'Gagal membuat akun: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        if (! $saved) {
            return $this->users->usernameExists($in['username'])
                ? $this->invalid(['username' => $this->msg['username_taken']])
                : $this->failure('save_failed');
        }

        return $this->success('created');
    }

    /** Ubah nama dan peran. Username tidak dapat diubah (VR-34), jadi tidak diproses. */
    public function update(int $id, array $input): array
    {
        $in     = self::clean($input);
        $errors = self::validateFields($in, $this->msg, false, false);

        $roleCode = null;
        if (! isset($errors['role_id'])) {
            $roleCode = $this->roles->codeById($in['role_id']);
            if ($roleCode === null) {
                $errors['role_id'] = $this->msg['role_required'];
            }
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        try {
            $status = $this->users->updateWithAdminGuard($id, $in['full_name'], $in['role_id'], $roleCode);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal mengubah akun: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        return match ($status) {
            'ok'        => $this->success('updated'),
            'not_found' => $this->failure('not_found', 'not_found'),
            'blocked'   => $this->failure('last_admin', 'blocked'),
            default     => $this->failure('save_failed'),
        };
    }

    /** Aktifkan/nonaktifkan akun (VR-37). Hasil 'message' menyebut tindakan yang terjadi. */
    public function toggleActive(int $id, int $actorId): array
    {
        try {
            $r = $this->users->toggleActiveWithGuard($id, $actorId);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal mengubah status akun: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        return match ($r['status']) {
            'ok'        => $this->success($r['active'] ? 'activated' : 'deactivated'),
            'not_found' => $this->failure('not_found', 'not_found'),
            'blocked'   => $this->failure('last_admin', 'blocked'),
            default     => $this->failure('save_failed'),
        };
    }

    /** Reset password oleh Admin (VR-35). */
    public function resetPassword(int $id, string $password): array
    {
        $errors = self::validateFields(['full_name' => 'x', 'username' => '', 'password' => $password, 'role_id' => 1], $this->msg, false, true);
        if (isset($errors['password'])) {
            return $this->invalid(['password' => $errors['password']]);
        }
        if ($this->users->findById($id) === null) {
            return $this->failure('not_found', 'not_found');
        }

        try {
            $this->users->updatePasswordHash($id, password_hash($password, PASSWORD_DEFAULT));
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal reset password: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        return $this->success('password_reset');
    }

    /** Buka kunci manual: reset penghitung gagal dan masa kunci (FR-23). */
    public function unlock(int $id): array
    {
        if ($this->users->findById($id) === null) {
            return $this->failure('not_found', 'not_found');
        }

        try {
            $this->users->resetAttempts($id);
        } catch (DatabaseException $e) {
            log_message('error', 'Gagal membuka kunci: ' . $e->getMessage());

            return $this->failure('save_failed');
        }

        return $this->success('unlocked');
    }

    /**
     * Validasi field (murni, tanpa database, sehingga mudah diuji).
     *
     * @return array<string,string> field => pesan
     */
    public static function validateFields(array $in, array $msg, bool $needUsername, bool $needPassword): array
    {
        $e = [];

        if ($in['full_name'] === '') {
            $e['full_name'] = $msg['name_required'];                       // VR-33
        } elseif (mb_strlen($in['full_name']) > 100) {
            $e['full_name'] = $msg['name_max'];
        }

        if ($needUsername) {
            if ($in['username'] === '') {
                $e['username'] = $msg['username_required'];                // VR-34
            } elseif (mb_strlen($in['username']) > 50) {
                $e['username'] = $msg['username_max'];
            } elseif (! preg_match('/^[A-Za-z0-9._-]+$/', $in['username'])) {
                $e['username'] = $msg['username_format'];
            }
        }

        if ($needPassword) {
            if ($in['password'] === '' || mb_strlen($in['password']) < 8) {
                $e['password'] = $msg['password_invalid'];                 // VR-35
            } elseif (strlen($in['password']) > 72) {                      // bcrypt hanya membaca 72 byte pertama
                $e['password'] = $msg['password_max'];
            }
        }

        if ($in['role_id'] <= 0) {
            $e['role_id'] = $msg['role_required'];                         // VR-36
        }

        return $e;
    }

    /** Samakan tipe & buang spasi di tepi (password sengaja tidak di-trim). */
    public static function clean(array $input): array
    {
        return [
            'full_name' => trim((string) ($input['full_name'] ?? '')),
            'username'  => trim((string) ($input['username'] ?? '')),
            'password'  => (string) ($input['password'] ?? ''),
            'role_id'   => (int) ($input['role_id'] ?? 0),
        ];
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
