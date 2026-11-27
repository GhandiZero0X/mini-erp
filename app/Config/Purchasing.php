<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Purchasing extends BaseConfig
{
    /**
     * true  = PO DITOLAK bila qty melebihi sisa kapasitas max_stock (perilaku lama).
     * false = melebihi max_stock hanya MEMUNCULKAN PERINGATAN di form, PO tetap bisa disimpan.
     *         (max_stock dianggap target persediaan, bukan batas keras: PO sering harus
     *          memenuhi pesanan customer yang jumlahnya melebihi max_stock.)
     */
    public bool $enforceMaxStock = false;

    public array $messages = [
        // UC-07 Membuat Purchase Order
        'supplier_required'  => 'Supplier wajib dipilih.',
        'supplier_invalid'   => 'Supplier tidak valid. Silakan pilih ulang.',
        'date_invalid'       => 'Tanggal tidak valid.',
        'items_required'     => 'Transaksi harus memiliki minimal satu produk.',
        'items_max'          => 'Satu transaksi maksimal 100 baris produk.',
        'product_required'   => 'Produk wajib dipilih.',
        'product_invalid'    => 'Produk tidak valid. Silakan pilih ulang.',
        'product_duplicate'  => 'Produk sudah dipilih pada baris lain. Gabungkan kuantitasnya.',
        'qty_invalid'        => 'Kuantitas harus berupa bilangan bulat lebih besar dari 0.',
        'price_invalid'      => 'Harga satuan tidak valid.',
        'total_too_large'    => 'Nilai transaksi terlalu besar.',
        'po_created'         => 'Purchase Order berhasil dibuat dengan status Draft.',

        // ← BARU: batas atas (kapasitas) stok
        'stock_at_max'       => 'Stok produk sudah mencapai batas maksimal, tidak dapat dipesan.',
        'stock_exceeds_max'  => 'Kuantitas melebihi kapasitas. Sisa yang dapat dipesan: %d (maks %d, sudah ada %d).',

        // UC-08 Menyetujui Purchase Order
        'approve_not_draft'  => 'Hanya PO berstatus Draft yang dapat disetujui.',
        'po_approved'        => 'Purchase Order berhasil disetujui.',

        // update po
        'po_updated'        => 'Purchase Order berhasil diperbarui.',
        'edit_not_draft'    => 'Hanya PO berstatus Draft yang dapat diedit.',

        // UC-09 Mencatat Goods Receipt
        'gr_not_receivable'  => 'Hanya PO berstatus Approved yang belum diterima penuh yang dapat diproses.',
        'gr_qty_invalid'     => 'Kuantitas diterima tidak valid atau melebihi sisa pesanan ({sisa}).',
        'gr_nothing'         => 'Isi kuantitas diterima minimal untuk satu produk.',
        'gr_date_invalid'    => 'Tanggal penerimaan tidak valid.',
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
        'save_failed'        => 'Data gagal disimpan karena terjadi kesalahan sistem. Silakan coba lagi.',
    ];
}