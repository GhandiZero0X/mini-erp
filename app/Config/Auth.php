<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi autentikasi & hak akses (SRS: NFR-16, NFR-17, NFR-19, Tabel 2.3).
 * Semua aturan peran/menu/pesan dipusatkan di sini supaya perubahan cukup di satu tempat.
 */
class Auth extends BaseConfig
{
    /** NFR-17: maksimal percobaan login gagal berturut-turut. */
    public int $maxLoginAttempts = 5;

    /** NFR-17: lama penguncian akun (menit). */
    public int $lockMinutes = 15;

    /** NFR-16: sesi berakhir setelah tidak aktif (detik) = 30 menit. */
    public int $sessionTimeout = 1800;

    /** Pesan standar (Bab 6 SRS + pesan UC-02). */
    public array $messages = [
        'required'  => 'Username dan password wajib diisi.',                                        // VR-01
        'invalid'   => 'Username atau password salah.',                                             // VR-02
        'locked'    => 'Akun terkunci sementara karena terlalu banyak percobaan gagal. Hubungi Admin atau coba lagi nanti.', // VR-16
        'forbidden' => 'Anda tidak memiliki hak akses ke halaman ini.',                             // VR-17
        'logout'    => 'Anda telah berhasil keluar.',                                               // UC-02 langkah 4
        'timeout'   => 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.',       // NFR-16
    ];

    /** Pesan UC-21 Kelola Akun Pengguna (VR-33 s.d. VR-37). Pesan bertanda * adalah tambahan di luar SRS. */
    public array $userMessages = [
        'name_required'     => 'Nama lengkap wajib diisi.',                                    // VR-33
        'name_max'          => 'Nama lengkap maksimal 100 karakter.',                          // *
        'username_required' => 'Username wajib diisi.',                                        // VR-34
        'username_taken'    => 'Username sudah digunakan.',                                    // VR-34
        'username_max'      => 'Username maksimal 50 karakter.',                               // *
        'username_format'   => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, dan strip.', // *
        'password_invalid'  => 'Password wajib diisi dan minimal 8 karakter.',                 // VR-35
        'password_max'      => 'Password maksimal 72 karakter.',                               // * batas bcrypt
        'role_required'     => 'Peran wajib dipilih.',                                         // VR-36
        'last_admin'        => 'Akun ini tidak dapat dinonaktifkan atau diubah perannya karena sistem harus memiliki minimal satu Admin aktif.', // VR-37
        'not_found'         => 'Data tidak ditemukan.',
        'save_failed'       => 'Data gagal disimpan. Silakan coba lagi.',
        'created'           => 'Akun pengguna berhasil dibuat.',
        'updated'           => 'Data akun berhasil diperbarui.',
        'activated'         => 'Akun berhasil diaktifkan.',
        'deactivated'       => 'Akun berhasil dinonaktifkan.',
        'password_reset'    => 'Password berhasil direset.',
        'unlocked'          => 'Kunci akun berhasil dibuka.',
    ];

    /**
     * Matriks hak akses (Tabel 2.3). W = lihat + buat/ubah/proses (✓), R = hanya lihat (L).
     * Peran/fitur yang tidak tercantum = tidak ada akses (deny by default).
     */
    public array $permissions = [
        'master_data'     => ['admin' => 'W', 'purchasing' => 'R', 'sales' => 'R'],
        'users'           => ['admin' => 'W'],
        'purchase_order'  => ['purchasing' => 'W', 'gudang' => 'R'],
        'goods_receipt'   => ['gudang' => 'W'],
        'sales_order'     => ['gudang' => 'R', 'sales' => 'W'],
        'delivery'        => ['gudang' => 'W'],
        'po_cancel'       => ['purchasing' => 'W'],
        'gr_cancel'       => ['gudang' => 'W'],
        'so_cancel'       => ['sales' => 'W'],
        'dl_cancel'       => ['gudang' => 'W'],
        'stock'           => ['admin' => 'R', 'gudang' => 'R', 'sales' => 'R', 'manajer' => 'R'],
        'stock_movement'  => ['admin' => 'R', 'gudang' => 'R', 'manajer' => 'R'],
        'report_purchase' => ['admin' => 'R', 'purchasing' => 'R', 'manajer' => 'R'],
        'report_sales'    => ['admin' => 'R', 'sales' => 'R', 'manajer' => 'R'],
        'report_stock'    => ['admin' => 'R', 'gudang' => 'R', 'sales' => 'R', 'manajer' => 'R'],
        'report_top'      => ['admin' => 'R', 'manajer' => 'R'],
    ];

    /** Keterangan singkat peran untuk dashboard (UI-02). */
    public array $roleDescriptions = [
        'admin'      => 'Mengelola data master dan akun pengguna, serta memantau stok dan laporan.',
        'purchasing' => 'Membuat dan menyetujui Purchase Order, serta memantau pembelian.',
        'gudang'     => 'Mencatat Goods Receipt dan Delivery, serta memeriksa stok.',
        'sales'      => 'Membuat Sales Order dan memeriksa ketersediaan stok.',
        'manajer'    => 'Memantau stok dan laporan operasional (hanya melihat).',
    ];

    /**
     * Menu navigasi. 'ready' => false menampilkan menu nonaktif (halaman belum dibuat);
     * ubah ke true saat halamannya sudah ada. 'feature' => null berarti semua peran.
     */
    public array $menu = [
        ['group' => null,           'label' => 'Beranda',            'path' => 'dashboard',        'icon' => 'bi-speedometer2',    'feature' => null,              'ready' => true,  'desc' => ''],

        ['group' => 'Master Data',  'label' => 'Kategori',           'path' => 'categories',       'icon' => 'bi-tags',            'feature' => 'master_data',     'ready' => true,  'desc' => 'Kelola kategori produk.'],
        ['group' => 'Master Data',  'label' => 'Produk',             'path' => 'products',         'icon' => 'bi-box-seam',        'feature' => 'master_data',     'ready' => true, 'desc' => 'Kelola data produk dan harga.'],
        ['group' => 'Master Data',  'label' => 'Supplier',           'path' => 'suppliers',        'icon' => 'bi-truck',           'feature' => 'master_data',     'ready' => true, 'desc' => 'Kelola data supplier.'],
        ['group' => 'Master Data',  'label' => 'Customer',           'path' => 'customers',        'icon' => 'bi-people',          'feature' => 'master_data',     'ready' => true, 'desc' => 'Kelola data customer.'],

        ['group' => 'Purchasing',   'label' => 'Purchase Order',     'path' => 'purchase-orders',  'icon' => 'bi-cart-plus',       'feature' => 'purchase_order',  'ready' => false, 'desc' => 'Buat dan setujui Purchase Order.'],
        ['group' => 'Purchasing',   'label' => 'Goods Receipt',      'path' => 'goods-receipts',   'icon' => 'bi-box-arrow-in-down','feature' => 'goods_receipt',  'ready' => false, 'desc' => 'Catat penerimaan barang.'],

        ['group' => 'Sales',        'label' => 'Sales Order',        'path' => 'sales-orders',     'icon' => 'bi-receipt',         'feature' => 'sales_order',     'ready' => false, 'desc' => 'Buat dan pantau Sales Order.'],
        ['group' => 'Sales',        'label' => 'Delivery',           'path' => 'deliveries',       'icon' => 'bi-box-arrow-up',    'feature' => 'delivery',        'ready' => false, 'desc' => 'Catat pengeluaran barang.'],

        ['group' => 'Inventory',    'label' => 'Stok Produk',        'path' => 'stock',            'icon' => 'bi-boxes',           'feature' => 'stock',           'ready' => false, 'desc' => 'Lihat stok terkini per produk.'],
        ['group' => 'Inventory',    'label' => 'Histori Mutasi',     'path' => 'stock-movements',  'icon' => 'bi-clock-history',   'feature' => 'stock_movement',  'ready' => false, 'desc' => 'Telusuri mutasi stok.'],

        ['group' => 'Laporan',      'label' => 'Laporan Pembelian',  'path' => 'reports/purchases','icon' => 'bi-file-earmark-bar-graph','feature' => 'report_purchase','ready' => false, 'desc' => 'Rekap pembelian per periode.'],
        ['group' => 'Laporan',      'label' => 'Laporan Penjualan',  'path' => 'reports/sales',    'icon' => 'bi-file-earmark-bar-graph','feature' => 'report_sales',   'ready' => false, 'desc' => 'Rekap penjualan per periode.'],
        ['group' => 'Laporan',      'label' => 'Laporan Stok',       'path' => 'reports/stock',    'icon' => 'bi-file-earmark-bar-graph','feature' => 'report_stock',   'ready' => false, 'desc' => 'Kondisi stok terkini.'],
        ['group' => 'Laporan',      'label' => 'Produk Terlaris',    'path' => 'reports/top-products','icon' => 'bi-trophy',       'feature' => 'report_top',      'ready' => false, 'desc' => '10 produk terlaris per periode.'],

        ['group' => 'Administrasi', 'label' => 'Pengguna',           'path' => 'users',            'icon' => 'bi-person-gear',     'feature' => 'users',           'ready' => true,  'desc' => 'Kelola akun pengguna.'],
    ];

    /** Pemeriksaan hak akses terpusat (NFR-19). $level: 'R' = lihat, 'W' = ubah/proses. */
    public function allows(?string $roleCode, ?string $feature, string $level = 'R'): bool
    {
        if ($feature === null) {
            return $roleCode !== null; // fitur tanpa batasan peran: cukup sudah login
        }

        $granted = $this->permissions[$feature][$roleCode] ?? null;
        if ($granted === null) {
            return false;
        }

        return $level === 'W' ? $granted === 'W' : true;
    }
}