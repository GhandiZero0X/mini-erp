<?php

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter terpusat untuk halaman internal (FR-03, NFR-01, NFR-15, NFR-16).
 *
 * Pemakaian di route:
 *   'filter' => 'auth'                  -> cukup sudah login
 *   'filter' => 'auth:master_data'      -> perlu hak lihat (R) pada fitur master_data
 *   'filter' => 'auth:master_data,W'    -> perlu hak ubah/proses (W)
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $cfg     = config('Auth');
        $session = session();
        $service = new AuthService();

        // 1. Belum login -> login (A4)
        $userId = (int) $session->get('user_id');
        if ($userId === 0) {
            return redirect()->to('/login');
        }

        // 2. Tidak aktif > 30 menit -> sesi berakhir (NFR-16)
        $last = (int) $session->get('last_activity');
        if ($last > 0 && (time() - $last) > $cfg->sessionTimeout) {
            $service->logout();

            return redirect()->to('/login')->with('warning', $cfg->messages['timeout']);
        }

        // 3. Akun dinonaktifkan/dihapus setelah login -> sesi berakhir pada request berikutnya (UC-21 A6)
        $user = $service->findActiveUser($userId);
        if ($user === null) {
            $service->logout();

            return redirect()->to('/login')->with('warning', $cfg->messages['timeout']);
        }
        $service->refreshSession($user); // peran selalu mengikuti database, last_activity diperbarui

        // 4. Hak akses di sisi server (NFR-15, VR-17)
        if (! empty($arguments)) {
            $feature = $arguments[0];
            $level   = $arguments[1] ?? 'R';

            if (! $cfg->allows($user['role_code'], $feature, $level)) {
                return redirect()->to('/dashboard')->with('error', $cfg->messages['forbidden']);
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Halaman internal tidak boleh disimpan cache, supaya tombol Back setelah logout
        // tidak menampilkan halaman internal (NFR-16, UC-02 A2).
        return $response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }
}
