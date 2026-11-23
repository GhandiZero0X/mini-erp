<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Sales extends BaseConfig
{
    public array $messages = [
        // Sales Order — validasi
        'so_date_invalid'      => 'Tanggal Sales Order wajib diisi dengan format yang benar.',
        'customer_required'    => 'Customer wajib dipilih.',
        'customer_not_found'   => 'Customer yang dipilih tidak ditemukan.',
        'items_required'       => 'Sales Order harus memiliki minimal satu baris produk.',
        'items_too_many'       => 'Maksimal 100 baris produk per Sales Order.',
        'product_required'     => 'Produk wajib dipilih.',
        'product_not_found'    => 'Produk yang dipilih tidak ditemukan.',
        'product_duplicate'    => 'Produk tidak boleh dipilih lebih dari sekali dalam satu Sales Order.',
        'quantity_invalid'     => 'Kuantitas harus bilangan bulat lebih dari 0.',
        'unit_price_invalid'   => 'Harga satuan harus angka ≥ 0 (maks 2 desimal).',
        'amount_overflow'      => 'Total nilai melebihi batas yang dapat disimpan.',

        // Sales Order — aksi
        'so_created'              => 'Sales Order berhasil dibuat.',
        'so_not_found'            => 'Sales Order tidak ditemukan.',
        'so_cancelled'            => 'Sales Order berhasil dibatalkan.',
        'so_cancel_not_allowed'   => 'SO hanya dapat dibatalkan bila berstatus Open.',
        'so_cancel_has_delivery'  => 'SO tidak dapat dibatalkan karena masih memiliki Delivery aktif. Batalkan Delivery terlebih dahulu.',
        'save_failed'             => 'Gagal menyimpan data. Silakan coba lagi.',

        // Pembatalan umum
        'cancel_reason_required'  => 'Alasan pembatalan wajib diisi.',
        'cancel_reason_max'       => 'Alasan pembatalan maksimal 500 karakter.',

        // Delivery — validasi
        'delivery_date_invalid'   => 'Tanggal pengiriman wajib diisi dengan format yang benar.',
        'delivery_qty_invalid'    => 'Kuantitas harus bilangan bulat ≥ 0 dan maksimal sebesar sisa ({sisa}).',
        'delivery_nothing'        => 'Isi minimal satu produk dengan kuantitas > 0.',

        // Delivery — aksi
        'delivery_created'            => 'Delivery berhasil dicatat dan stok dikurangi.',
        'delivery_not_found'          => 'Delivery tidak ditemukan.',
        'delivery_not_receivable'     => 'SO tidak dapat dikirim (sudah dibatalkan atau sudah dikirim penuh).',
        'delivery_insufficient_stock' => 'Stok tidak mencukupi. Produk :produk tersedia :stok, dibutuhkan :butuh.',
        'delivery_cancelled'          => 'Delivery berhasil dibatalkan dan stok dikembalikan.',
        'delivery_cancel_not_allowed' => 'Hanya Delivery berstatus Aktif yang dapat dibatalkan.',
    ];
}