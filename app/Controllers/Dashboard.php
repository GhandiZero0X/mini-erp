<?php

namespace App\Controllers;

/** UI-02 Beranda/dashboard. Isi berbeda per peran mengikuti matriks hak akses. */
class Dashboard extends BaseController
{
    public function index()
    {
        $user = auth_user();
        $cfg  = config('Auth');

        // Pintasan = menu yang boleh diakses peran ini, dikelompokkan, tanpa Beranda itu sendiri
        $shortcuts = [];
        foreach (auth_menu() as $item) {
            if ($item['feature'] === null) {
                continue;
            }
            $shortcuts[$item['group']][] = $item;
        }

        return view('dashboard/index', [
            'title'     => 'Beranda',
            'user'      => $user,
            'roleDesc'  => $cfg->roleDescriptions[$user['role_code']] ?? '',
            'shortcuts' => $shortcuts,
        ]);
    }
}
