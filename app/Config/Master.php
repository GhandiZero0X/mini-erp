<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pesan modul Master Data (Bab 6 SRS), dipusatkan di satu tempat (NFR-06).
 * Pesan bertanda * adalah tambahan di luar SRS.
 * Modul Produk, Supplier, Customer nanti menambah pesannya di sini.
 */
class Master extends BaseConfig
{
    public array $messages = [
        // Kategori (UC-03)
        'category_name_required' => 'Nama kategori wajib diisi.',                  // VR-19
        'category_name_taken'    => 'Nama kategori sudah digunakan.',              // VR-20
        'category_name_max'      => 'Nama kategori maksimal 100 karakter.',        // *
        'category_created'       => 'Kategori berhasil ditambahkan.',
        'category_updated'       => 'Kategori berhasil diperbarui.',
        'category_deleted'       => 'Kategori berhasil dihapus.',

        // Produk (UC-04)
        'product_code_required'     => 'Kode produk wajib diisi.',                                              // VR-03
        'product_code_taken'        => 'Kode produk sudah digunakan.',                                          // VR-03
        'product_code_max'          => 'Kode produk maksimal 30 karakter.',                                     // *
        'product_name_required'     => 'Nama produk wajib diisi.',                                              // VR-04
        'product_name_max'          => 'Nama produk maksimal 150 karakter.',                                    // *
        'product_unit_required'     => 'Satuan wajib diisi.',                                                   // VR-04
        'product_unit_max'          => 'Satuan maksimal 20 karakter.',                                          // *
        'product_category_required' => 'Kategori wajib diisi.',                                                 // VR-04
        'product_category_invalid'  => 'Kategori tidak valid.',                                                 // VR-04
        'product_price_invalid'     => 'Harga harus berupa angka dan tidak boleh negatif.',                     // VR-05
        'product_code_locked'       => 'Kode produk tidak dapat diubah karena sudah digunakan pada transaksi.', // VR-21
        'product_created'           => 'Produk berhasil ditambahkan.',
        'product_updated'           => 'Produk berhasil diperbarui.',
        'product_deleted'           => 'Produk berhasil dihapus.',

        // Umum (dipakai semua master data)
        'in_use'                 => 'Data tidak dapat dihapus karena sudah digunakan pada %s.', // VR-15, %s = produk/PO/SO
        'not_found'              => 'Data tidak ditemukan.',
        'save_failed'            => 'Data gagal disimpan. Silakan coba lagi.',
    ];
}