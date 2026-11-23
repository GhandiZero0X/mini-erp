<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Nilai terdefinisi untuk dokumen transaksi (NFR-19): status, awalan nomor, dan warna label.
 * Dipusatkan di sini supaya tidak tersebar di banyak tempat.
 */
class Documents extends BaseConfig
{
    // Status Purchase Order (SRS 5.5)
    public const PO_DRAFT     = 'Draft';
    public const PO_APPROVED  = 'Approved';
    public const PO_CANCELLED = 'Dibatalkan';

    // Status Goods Receipt
    public const GR_ACTIVE    = 'Aktif';
    public const GR_CANCELLED = 'Dibatalkan';

    /** Urutan tampil pada filter status daftar PO. */
    public array $poStatuses = [self::PO_DRAFT, self::PO_APPROVED, self::PO_CANCELLED];

    /** Urutan tampil pada filter status daftar GR. */
    public array $grStatuses = [self::GR_ACTIVE, self::GR_CANCELLED];

    // Status Sales Order
    public const SO_OPEN      = 'Open';
    public const SO_CANCELLED = 'Dibatalkan';

    // Status Delivery
    public const DL_ACTIVE    = 'Aktif';
    public const DL_CANCELLED = 'Dibatalkan';

    public array $soStatuses = [self::SO_OPEN, self::SO_CANCELLED];
    public array $dlStatuses = [self::DL_ACTIVE, self::DL_CANCELLED];

    /** Awalan nomor dokumen: PREFIX-YYYYMM-0001. */
    public array $prefixes = [
        'purchase_order' => 'PO',
        'goods_receipt'  => 'GR',
        'sales_order'    => 'SO',
        'delivery'       => 'DL',
    ];

    /** [tabel, kolom nomor] tiap jenis dokumen. Konstan, bukan input pengguna. */
    public array $numberColumns = [
        'purchase_order' => ['purchase_orders', 'po_number'],
        'goods_receipt'  => ['goods_receipts', 'gr_number'],
        'sales_order'    => ['sales_orders', 'so_number'],
        'delivery'       => ['deliveries', 'delivery_number'],
    ];

    /** Kelas Bootstrap label status (UI standar SRS 3.1). */
    public array $statusBadges = [
        'Draft'      => 'text-bg-secondary',
        'Approved'   => 'text-bg-success',
        'Open'       => 'text-bg-primary',
        'Aktif'      => 'text-bg-success',
        'Dibatalkan' => 'text-bg-danger',
    ];
}