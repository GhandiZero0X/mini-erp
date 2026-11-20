<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Akses data akun. Semua query memakai Query Builder (NFR-02).
 * Waktu penguncian dihitung oleh PostgreSQL (now()) agar tidak terpengaruh zona waktu PHP.
 */
class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // created_at/updated_at diisi default DB dan di-set manual saat update
    protected $allowedFields = [
        'full_name', 'username', 'password_hash', 'role_id', 'is_active',
        'failed_login_count', 'locked_until', 'last_login_at',
    ];

    /** Kolom bersama untuk query login & pemeriksaan sesi. */
    private const COLUMNS = 'u.id, u.username, u.full_name, u.password_hash, u.is_active,
        u.failed_login_count, u.locked_until,
        (u.locked_until IS NOT NULL AND u.locked_until > now()) AS is_locked,
        r.code AS role_code, r.name AS role_name';

    /** Cari akun (beserta peran) berdasarkan username untuk proses login. */
    public function findForLogin(string $username): ?array
    {
        $row = $this->db->table('users u')
            ->select(self::COLUMNS, false)
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.username', $username)
            ->get()->getRowArray();

        return $row ? $this->normalize($row) : null;
    }

    /** Akun aktif berdasarkan id (dipakai filter di setiap request). Null bila nonaktif/tidak ada. */
    public function findActiveById(int $id): ?array
    {
        $row = $this->db->table('users u')
            ->select(self::COLUMNS, false)
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.id', $id)
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }
        $row = $this->normalize($row);

        return $row['is_active'] ? $row : null;
    }

    /**
     * Catat satu percobaan gagal secara atomik (increment di sisi DB).
     * Bila mencapai batas, akun dikunci selama $lockMinutes menit.
     *
     * @return array{count:int, locked:bool}
     */
    public function registerFailure(int $id, int $max, int $lockMinutes): array
    {
        $this->db->transStart();

        $this->db->table('users')
            ->set('failed_login_count', 'failed_login_count + 1', false)
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();

        $count = (int) $this->db->table('users')->select('failed_login_count')
            ->where('id', $id)->get()->getRowArray()['failed_login_count'];

        $locked = $count >= $max;
        if ($locked) {
            $minutes = max(1, $lockMinutes); // int, aman dipakai pada ekspresi SQL mentah
            $this->db->table('users')
                ->set('locked_until', "now() + ({$minutes} * interval '1 minute')", false)
                ->where('id', $id)
                ->update();
        }

        $this->db->transComplete();

        return ['count' => $count, 'locked' => $locked];
    }

    /** Reset penghitung gagal & kunci (login berhasil, kunci kedaluwarsa, atau dibuka Admin). */
    public function resetAttempts(int $id): void
    {
        $this->db->table('users')
            ->set('failed_login_count', 0)
            ->set('locked_until', null)
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();
    }

    /** Login berhasil: reset penghitung dan catat waktu login terakhir. */
    public function markLogin(int $id): void
    {
        $this->db->table('users')
            ->set('failed_login_count', 0)
            ->set('locked_until', null)
            ->set('last_login_at', 'now()', false)
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->db->table('users')
            ->set('password_hash', $hash)
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();
    }

    // ------------------------------------------------------------------
    // UC-21 Kelola Akun Pengguna
    // ------------------------------------------------------------------

    /** Daftar akun + peran + status, berhalaman 10 baris (NFR-11). Pager ada di $this->pager. */
    public function listPage(int $perPage = 10): array
    {
        $rows = $this->select(
            'users.id, users.full_name, users.username, users.is_active, users.failed_login_count,
             (users.locked_until IS NOT NULL AND users.locked_until > now()) AS is_locked,
             r.name AS role_name, r.code AS role_code',
            false
        )
            ->join('roles r', 'r.id = users.role_id')
            ->orderBy('users.full_name', 'ASC')
            ->orderBy('users.id', 'ASC')
            ->paginate($perPage);

        return array_map(fn (array $row) => $this->normalize($row), $rows);
    }

    /** Detail akun tanpa hash password. */
    public function findById(int $id): ?array
    {
        $row = $this->db->table('users u')
            ->select(self::COLUMNS, false)
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.id', $id)
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }
        $row = $this->normalize($row);
        unset($row['password_hash']);

        return $row;
    }

    /** Cek username tanpa membedakan huruf besar/kecil, agar "Admin" dan "admin" tidak dianggap berbeda. */
    public function usernameExists(string $username): bool
    {
        $row = $this->db->query('SELECT 1 FROM users WHERE lower(username) = lower(?) LIMIT 1', [$username])->getRowArray();

        return ! empty($row);
    }

    /** Akun baru selalu berstatus aktif dengan password ter-hash. */
    public function insertUser(string $fullName, string $username, string $passwordHash, int $roleId): bool
    {
        return (bool) $this->db->table('users')->insert([
            'full_name'     => $fullName,
            'username'      => $username,
            'password_hash' => $passwordHash,
            'role_id'       => $roleId,
            'is_active'     => true,
        ]);
    }

    /**
     * Ubah nama & peran dengan pengaman VR-37 (admin aktif terakhir tidak boleh diturunkan perannya).
     * Baris Admin aktif dikunci (FOR UPDATE) selama transaksi agar dua Admin yang bekerja
     * bersamaan tidak bisa sama-sama lolos pemeriksaan.
     *
     * @return string 'ok' | 'not_found' | 'blocked' | 'failed'
     */
    public function updateWithAdminGuard(int $id, string $fullName, int $roleId, ?string $newRoleCode): string
    {
        $this->db->transStart();

        $admins = $this->lockActiveAdmins();
        $target = $this->findById($id);

        if ($target === null) {
            $this->db->transComplete();

            return 'not_found';
        }

        $isLastAdmin = $target['is_active'] && $target['role_code'] === 'admin' && count($admins) <= 1;
        if ($isLastAdmin && $newRoleCode !== 'admin') {
            $this->db->transComplete();

            return 'blocked';
        }

        $this->db->table('users')
            ->set('full_name', $fullName)
            ->set('role_id', $roleId)
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();

        $this->db->transComplete();

        return $this->db->transStatus() ? 'ok' : 'failed';
    }

    /**
     * Balik status aktif/nonaktif dengan pengaman VR-37:
     * Admin tidak boleh menonaktifkan akunnya sendiri dan minimal satu Admin aktif harus tersisa.
     *
     * @return array{status:string, active:bool} status: 'ok' | 'not_found' | 'blocked' | 'failed'
     */
    public function toggleActiveWithGuard(int $id, int $actorId): array
    {
        $this->db->transStart();

        $admins = $this->lockActiveAdmins();
        $target = $this->findById($id);

        if ($target === null) {
            $this->db->transComplete();

            return ['status' => 'not_found', 'active' => false];
        }

        $newActive = ! $target['is_active'];

        if (! $newActive) { // menonaktifkan
            $isLastAdmin = $target['role_code'] === 'admin' && count($admins) <= 1;
            if ($id === $actorId || $isLastAdmin) {
                $this->db->transComplete();

                return ['status' => 'blocked', 'active' => $target['is_active']];
            }
        }

        $this->db->table('users')
            ->set('is_active', $newActive)
            ->set('updated_at', 'now()', false)
            ->where('id', $id)
            ->update();

        $this->db->transComplete();

        return ['status' => $this->db->transStatus() ? 'ok' : 'failed', 'active' => $newActive];
    }

    /** Daftar id Admin aktif, dikunci selama transaksi berjalan. */
    private function lockActiveAdmins(): array
    {
        $rows = $this->db->query(
            "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.code = 'admin' AND u.is_active = true FOR UPDATE OF u"
        )->getResultArray();

        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /**
     * PostgreSQL via driver CI4 mengembalikan boolean sebagai 't'/'f' (string),
     * dan string 'f' itu bernilai TRUE di PHP. Ubah ke bool sungguhan.
     */
    private function normalize(array $row): array
    {
        $row['id']                 = (int) $row['id'];
        $row['failed_login_count'] = (int) $row['failed_login_count'];
        $row['is_active']          = self::toBool($row['is_active']);
        $row['is_locked']          = self::toBool($row['is_locked']);

        return $row;
    }

    private static function toBool(mixed $v): bool
    {
        return $v === true || $v === 't' || $v === 'true' || $v === '1' || $v === 1;
    }
}
