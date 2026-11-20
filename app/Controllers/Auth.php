<?php

namespace App\Controllers;

use App\Services\AuthService;

/** UC-01 Login dan UC-02 Logout. Controller tipis: logika ada di AuthService. */
class Auth extends BaseController
{
    /** GET /login (UI-01) */
    public function login()
    {
        return view('auth/login');
    }

    /** POST /login */
    public function attempt()
    {
        $cfg      = config('Auth');
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        // VR-01: keduanya wajib diisi (A1)
        if ($username === '' || $password === '') {
            return $this->back($cfg->messages['required'], $username);
        }

        // Batas panjang sesuai kolom database; di luar itu pasti tidak cocok (VR-02)
        if (mb_strlen($username) > 50 || strlen($password) > 255) {
            return $this->back($cfg->messages['invalid'], $username);
        }

        $result = (new AuthService())->attempt($username, $password);

        if (! $result['ok']) {
            return $this->back($result['message'], $username);
        }

        // Berhasil: langsung ke dashboard sesuai peran
        return redirect()->to('/dashboard');
    }

    /** POST /logout (dilindungi token CSRF) */
    public function logout()
    {
        $wasLoggedIn = session()->has('user_id');

        (new AuthService())->logout();

        // A1: sesi sudah berakhir sebelum logout -> langsung ke login tanpa pesan
        if (! $wasLoggedIn) {
            return redirect()->to('/login');
        }

        return redirect()->to('/login')->with('success', config('Auth')->messages['logout']);
    }

    /** Kembali ke form login dengan pesan. Password sengaja TIDAK dikirim balik ke browser. */
    private function back(string $message, string $username)
    {
        return redirect()->to('/login')
            ->with('error', $message)
            ->with('old_username', $username);
    }
}
