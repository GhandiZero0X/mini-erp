<?php

namespace App\Services;

use App\Models\UserModel;
use Config\Auth as AuthConfig;

/**
 * Logika bisnis autentikasi (UC-01, UC-02, NFR-16, NFR-17).
 * Controller dan Filter hanya memanggil kelas ini (Controller tipis, NFR-10).
 */
class AuthService
{
    private UserModel $users;
    private AuthConfig $cfg;

    /** Hash palsu untuk menyamakan waktu respons saat username tidak ada. */
    private static ?string $dummyHash = null;

    public function __construct(?UserModel $users = null, ?AuthConfig $cfg = null)
    {
        $this->users = $users ?? new UserModel();
        $this->cfg   = $cfg ?? config('Auth');
    }

    /**
     * Proses login. Pesan gagal tidak membocorkan mana yang salah (VR-02).
     *
     * @return array{ok:bool, message:string}
     */
    public function attempt(string $username, string $password): array
    {
        $msg  = $this->cfg->messages;
        $user = $this->users->findForLogin($username);

        if ($user === null) {
            $this->burnTime($password);

            return $this->fail($msg['invalid']);
        }

        // VR-16: akun sedang terkunci
        if ($user['is_locked']) {
            return $this->fail($msg['locked']);
        }

        // FR-23: kunci sudah kedaluwarsa -> terbuka otomatis, penghitung direset
        if ($user['locked_until'] !== null) {
            $this->users->resetAttempts($user['id']);
        }

        // A3: akun nonaktif memakai pesan yang sama dengan VR-02
        if (! $user['is_active']) {
            $this->burnTime($password);

            return $this->fail($msg['invalid']);
        }

        if (! password_verify($password, $user['password_hash'])) {
            $result = $this->users->registerFailure($user['id'], $this->cfg->maxLoginAttempts, $this->cfg->lockMinutes);

            return $this->fail($result['locked'] ? $msg['locked'] : $msg['invalid']);
        }

        // Berhasil
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        $this->users->markLogin($user['id']);
        $this->startSession($user);

        return ['ok' => true, 'message' => ''];
    }

    /** Buat sesi baru. ID sesi diganti untuk mencegah session fixation. */
    private function startSession(array $user): void
    {
        $session = session();
        $session->regenerate(true);
        $session->set([
            'user_id'       => $user['id'],
            'username'      => $user['username'],
            'full_name'     => $user['full_name'],
            'role_code'     => $user['role_code'],
            'role_name'     => $user['role_name'],
            'last_activity' => time(),
        ]);
    }

    /** Segarkan identitas & peran di sesi dari database (dipanggil filter tiap request). */
    public function refreshSession(array $user): void
    {
        session()->set([
            'full_name'     => $user['full_name'],
            'role_code'     => $user['role_code'],
            'role_name'     => $user['role_name'],
            'last_activity' => time(),
        ]);
    }

    /** Hapus seluruh data sesi (identitas & hak akses) dan ganti ID sesi. */
    public function logout(): void
    {
        $session = session();
        $keys    = array_filter(array_keys($_SESSION ?? []), static fn ($k) => ! str_starts_with((string) $k, '__ci'));
        if ($keys !== []) {
            $session->remove(array_values($keys));
        }
        $session->regenerate(true);
    }

    public function findActiveUser(int $id): ?array
    {
        return $this->users->findActiveById($id);
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    /** Verifikasi hash palsu agar lama proses mirip dengan password salah (cegah user enumeration). */
    private function burnTime(string $password): void
    {
        self::$dummyHash ??= password_hash('dummy-password', PASSWORD_DEFAULT);
        password_verify($password, self::$dummyHash);
    }
}
