<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pesan modul Purchasing (Bab 6 SRS), dipusatkan di satu tempat (NFR-06).
 * Pesan bertanda * adalah tambahan di luar SRS.
 * Pesan Goods Receipt dan pembatalan ditambahkan di sini saat modulnya dibuat.
 */
class Purchasing extends BaseConfig
{
    public array $messages = [
        // UC-07 Membuat Purchase Order
        'supplier_required'  => 'Supplier wajib dipilih.',                                                   // VR-06
        'supplier_invalid'   => 'Supplier tidak valid. Silakan pilih ulang.',                                // A5
        'date_invalid'       => 'Tanggal tidak valid.',                                                      // *
        'items_required'     => 'Transaksi harus memiliki minimal satu produk.',                             // VR-07
        'items_max'          => 'Satu transaksi maksimal 100 baris produk.',                                 // *
        'product_required'   => 'Produk wajib dipilih.',                                                     // *
        'product_invalid'    => 'Produk tidak valid. Silakan pilih ulang.',                                  // A5
        'product_duplicate'  => 'Produk sudah dipilih pada baris lain. Gabungkan kuantitasnya.',             // * (uq_po_items_po_product)
        'qty_invalid'        => 'Kuantitas harus berupa bilangan bulat lebih besar dari 0.',                 // VR-08
        'price_invalid'      => 'Harga satuan tidak valid.',                                                 // VR-09
        'total_too_large'    => 'Nilai transaksi terlalu besar.',                                            // *
        'po_created'         => 'Purchase Order berhasil dibuat dengan status Draft.',

        // UC-08 Menyetujui Purchase Order
        'approve_not_draft'  => 'Hanya PO berstatus Draft yang dapat disetujui.',                           // VR-25
        'po_approved'        => 'Purchase Order berhasil disetujui.',

        // UC-09 Mencatat Goods Receipt
        'gr_not_receivable'  => 'Hanya PO berstatus Approved yang belum diterima penuh yang dapat diproses.', // VR-10
        'gr_qty_invalid'     => 'Kuantitas diterima tidak valid atau melebihi sisa pesanan ({sisa}).',         // VR-11
        'gr_nothing'         => 'Isi kuantitas diterima minimal untuk satu produk.',                          // VR-26
        'gr_date_invalid'    => 'Tanggal penerimaan tidak valid.',                                            // *
        'gr_created'         => 'Goods Receipt berhasil disimpan. Stok produk telah bertambah.',

        // Membatal PO/GR (UC-10, UC-11)
        'cancel_reason_required'     => 'Alasan pembatalan wajib diisi.',
        'cancel_reason_max'          => 'Alasan pembatalan maksimal 500 karakter.',
        'po_cancelled'               => 'Purchase Order berhasil dibatalkan.',
        'po_cancel_not_allowed'      => 'PO hanya dapat dibatalkan bila berstatus Draft atau Approved tanpa penerimaan barang aktif.',
        'po_cancel_has_gr'           => 'PO tidak dapat dibatalkan karena masih memiliki Goods Receipt aktif. Batalkan GR terlebih dahulu.',
        'gr_cancelled'               => 'Goods Receipt berhasil dibatalkan dan stok dikembalikan.',
        'gr_cancel_not_allowed'      => 'Hanya GR berstatus Aktif yang dapat dibatalkan.',
        'gr_cancel_insufficient_stock' => 'Stok tidak mencukupi untuk membatalkan GR. Stok :produk saat ini :stok, dibutuhkan :butuh.',

        // Umum
        'not_found'          => 'Data tidak ditemukan.',
        'save_failed'        => 'Data gagal disimpan karena terjadi kesalahan sistem. Silakan coba lagi.',   // A6, NFR-07
    ];
}