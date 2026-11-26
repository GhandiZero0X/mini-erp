<?php

namespace App\Controllers;

use App\Libraries\DashboardStats;
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

        // Widget mana yang boleh tampil untuk peran ini (deny by default lewat allows())
        $role      = $user['role_code'];
        $isManager   = $role === 'manajer';
        $isWarehouse = $role === 'gudang';
        $isSales     = $role === 'sales';
        $isPurchasing = $role === 'purchasing';

        $can = [
            'sales'     => $cfg->allows($role, 'report_sales'),
            'purchase'  => $cfg->allows($role, 'report_purchase'),
            'products'  => $cfg->allows($role, 'master_data') && ! $isSales && ! $isPurchasing, // Sales/Purchasing: diganti kartu khusus peran
            'customers' => $cfg->allows($role, 'master_data') && ! $isPurchasing,
            'users'     => $cfg->allows($role, 'users', 'W'),
            'locked'    => $cfg->allows($role, 'users', 'W'),
            'top'       => $cfg->allows($role, 'report_top'),

            // BARU: panel khusus Manajer (tetap lewat allows() agar mengikuti matriks hak akses)
            'approve'      => $isManager && $cfg->allows($role, 'po_approve', 'W'),
            'rep_purchase' => ($isManager || $isPurchasing) && $cfg->allows($role, 'report_purchase'),
            'rep_sales'    => ($isManager || $isSales) && $cfg->allows($role, 'report_sales'),
            'rep_stock'    => $isManager && $cfg->allows($role, 'report_stock'),

            // BARU: panel khusus Staff Gudang
            'wh_receipt'   => $isWarehouse && $cfg->allows($role, 'goods_receipt', 'W'),
            'wh_delivery'  => $isWarehouse && $cfg->allows($role, 'delivery', 'W'),
            'wh_stock'     => $isWarehouse && $cfg->allows($role, 'stock'),
            'wh_move'      => $isWarehouse && $cfg->allows($role, 'stock_movement'),

            // BARU: panel khusus Sales (semua lewat allows(); Sales hanya LIHAT status pengiriman)
            'sl_create'    => $isSales && $cfg->allows($role, 'sales_order', 'W'),
            'sl_delivery'  => $isSales && $cfg->allows($role, 'sales_order'),
            'sl_customer'  => $isSales && $cfg->allows($role, 'sales_order'),
            'sl_stock'     => $isSales && $cfg->allows($role, 'stock'),

            // BARU: panel khusus Purchasing (Purchasing hanya LIHAT penerimaan barang & status persetujuan)
            'pu_create'    => $isPurchasing && $cfg->allows($role, 'purchase_order', 'W'),
            'pu_draft'     => $isPurchasing && $cfg->allows($role, 'purchase_order', 'W'),
            'pu_receipt'   => $isPurchasing && $cfg->allows($role, 'purchase_order'),
            'pu_supplier'  => $isPurchasing && $cfg->allows($role, 'purchase_order'),
            'suppliers'    => $isPurchasing && $cfg->allows($role, 'master_data'),
            'pu_stock'     => $isPurchasing && $cfg->allows($role, 'purchase_order'),
        ];

        // Peringatan stok Purchasing (di bawah minimum / mendekati batas maksimum).
        // Mengikuti $can supaya satu aturan hak akses dipakai di controller dan view.
        $stockAlerts = ['nearMin' => [], 'nearMax' => []];
        if (! empty($can['pu_stock'])) {
            $products    = new ProductModel();
            $stockAlerts = [
                'nearMin' => $products->listNearMin(10),
                'nearMax' => $products->listNearMax(10),
            ];
        }

        // Filter periode: ?period=day|week|month|year. Nilai lain/kosong -> month (divalidasi di DashboardStats).
        $period = (string) $this->request->getGet('period');

        // Query statistik hanya dijalankan untuk widget yang diizinkan.
        // BARU: Manajer melihat 10 produk terlaris, peran lain tetap 5.
        $stats = (new DashboardStats())->collect($can, [
            'topLimit' => $isManager ? 10 : 5,
            'period'   => $period,
        ]);

        return view('dashboard/index', [
            'title'       => 'Beranda',
            'user'        => $user,
            'roleDesc'    => $cfg->roleDescriptions[$user['role_code']] ?? '',
            'shortcuts'   => $shortcuts,
            'stockAlerts' => $stockAlerts,
            'can'         => $can,
        ] + $stats); // kpi, trend, topProducts, topCategories, lockedUsers, topLimit, pendingPOs, repPurchase, repSales, repStock,
        // whReceipts, whDeliveries, whLowStock, whHighStock, whFlow, whMovements, periodInfo,
        // slDeliveries, slStock, slCustomers, puDrafts, puReceipts, puSuppliers
    }
}