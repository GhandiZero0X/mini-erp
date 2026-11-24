<?php

namespace App\Controllers;

use App\Models\ProductModel;

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

        // Widget stok hanya untuk purchasing (Staff PO)
        $stockAlerts = null;
        if ($user['role_code'] === 'purchasing') {
            $products    = new ProductModel();
            $stockAlerts = [
                'nearMin' => $products->listNearMin(10),
                'nearMax' => $products->listNearMax(10),
            ];
        }

        return view('dashboard/index', [
            'title'       => 'Beranda',
            'user'        => $user,
            'roleDesc'    => $cfg->roleDescriptions[$user['role_code']] ?? '',
            'shortcuts'   => $shortcuts,
            'stockAlerts' => $stockAlerts,
        ]);
    }
}