<?php

/**
 * Helper autentikasi untuk View & Controller.
 * Dimuat otomatis lewat Config\Autoload::$helpers = ['auth', ...].
 */

if (! function_exists('auth_user')) {
    /** Data pengguna yang sedang login dari sesi, atau null. */
    function auth_user(): ?array
    {
        $session = session();
        if (! $session->get('user_id')) {
            return null;
        }

        return [
            'id'        => (int) $session->get('user_id'),
            'username'  => (string) $session->get('username'),
            'full_name' => (string) $session->get('full_name'),
            'role_code' => (string) $session->get('role_code'),
            'role_name' => (string) $session->get('role_name'),
        ];
    }
}

if (! function_exists('auth_can')) {
    /** Cek hak akses peran saat ini. $level: 'R' (lihat) atau 'W' (ubah/proses). */
    function auth_can(?string $feature, string $level = 'R'): bool
    {
        $user = auth_user();

        return $user !== null && config('Auth')->allows($user['role_code'], $feature, $level);
    }
}

if (! function_exists('auth_menu')) {
    /** Item menu yang boleh dilihat peran saat ini (menu mengikuti hak akses, NFR-05). */
    function auth_menu(): array
    {
        return array_values(array_filter(
            config('Auth')->menu,
            static fn (array $item): bool => auth_can($item['feature'])
        ));
    }
}
