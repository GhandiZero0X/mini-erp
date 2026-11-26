<?php
/**
 * @var array      $user
 * @var string     $roleDesc
 * @var array      $shortcuts
 * @var array|null $stockAlerts
 * @var array      $can            Flag widget per hak akses (dari Dashboard::index)
 * @var array      $kpi            Angka kartu ringkasan
 * @var array|null $trend          Data grafik garis
 * @var array      $topProducts
 * @var array      $topCategories
 * @var array      $lockedUsers
 * @var int        $topLimit       Jumlah produk terlaris (5 default, 10 untuk Manajer)
 * @var array      $pendingPOs     PO Draft menunggu persetujuan (Manajer)
 * @var array      $repPurchase    Laporan pembelian bulan ini (Manajer)
 * @var array      $repSales       Laporan penjualan bulan ini (Manajer)
 * @var array      $repStock       Laporan stok periode terpilih (Manajer)
 * @var array      $slDeliveries   Antrean pengiriman SO (Sales, hanya lihat)
 * @var array      $slStock        Stok habis/menipis + harga jual (Sales): total, rows
 * @var array      $slCustomers    Customer teratas periode terpilih (Sales)
 * @var array      $puDrafts       PO Draft menunggu persetujuan Manajer (Purchasing, bisa diedit)
 * @var array      $puReceipts     PO Approved yang belum diterima penuh (Purchasing, hanya lihat)
 * @var array      $puSuppliers    Supplier teratas periode terpilih (Purchasing)
 * @var array      $periodInfo     Filter periode: key, label, prev, unit_name, span, range_text
 */
$can           = $can ?? [];
$kpi           = $kpi ?? [];
$trend         = $trend ?? null;
$topProducts   = $topProducts ?? [];
$topCategories = $topCategories ?? [];
$lockedUsers   = $lockedUsers ?? [];
$topLimit      = $topLimit ?? 5;
$pendingPOs    = $pendingPOs ?? [];
$repPurchase   = $repPurchase ?? [];
$repSales      = $repSales ?? [];
$repStock      = $repStock ?? [];
$whReceipts    = $whReceipts ?? [];
$whDeliveries  = $whDeliveries ?? [];
$whLowStock    = $whLowStock ?? [];
$whHighStock   = $whHighStock ?? [];
$whFlow        = $whFlow ?? ['labels' => [], 'in' => [], 'out' => []];
$whMovements   = $whMovements ?? [];
$slDeliveries  = $slDeliveries ?? [];
$slStock       = $slStock ?? ['total' => 0, 'rows' => []];
$slCustomers   = $slCustomers ?? [];
$puDrafts      = $puDrafts ?? [];
$puReceipts    = $puReceipts ?? [];
$puSuppliers   = $puSuppliers ?? [];
$stockAlerts   = $stockAlerts ?? null;
$periodInfo    = $periodInfo ?? ['key' => 'month', 'label' => 'Bulan Ini', 'prev' => 'bulan lalu', 'unit_name' => 'bulan', 'span' => '6 bulan terakhir', 'range_text' => date('m/Y')];

$showLocked = ! empty($can['locked']);
$showTrend  = $trend !== null;
$showTop    = ! empty($can['top']);
$showApprove = ! empty($can['approve']);
$showRepPo   = ! empty($can['rep_purchase']);
$showRepSo   = ! empty($can['rep_sales']);
$showRepStk  = ! empty($can['rep_stock']);
$hasCharts  = $showTrend || $showTop;
$showWhReceipt  = ! empty($can['wh_receipt']);
$showWhDelivery = ! empty($can['wh_delivery']);
$showWhStock    = ! empty($can['wh_stock']);
$showWhMove     = ! empty($can['wh_move']);
$showSlCreate   = ! empty($can['sl_create']);
$showSlDelivery = ! empty($can['sl_delivery']);
$showSlStock    = ! empty($can['sl_stock']);
$showSlCustomer = ! empty($can['sl_customer']);
$showPuCreate   = ! empty($can['pu_create']);
$showPuDraft    = ! empty($can['pu_draft']);
$showPuReceipt  = ! empty($can['pu_receipt']);
$showPuSupplier = ! empty($can['pu_supplier']);
$showStockAlerts = ! empty($can['pu_stock']); // kartu tetap tampil walau datanya kosong (menampilkan keadaan "aman")
// Filter periode hanya tampil jika ada widget yang mengikutinya
$showFilter     = $showTrend || $showTop || $showRepPo || $showRepSo || $showRepStk || $showWhMove;
$waktu          = static fn ($d): string => date('d-m-Y H:i', strtotime((string) $d));

// ---- Daftar kartu ringkasan: hanya yang diizinkan untuk peran ini ----
// Format: [label, nilai, format, ikon, warna, persen_perubahan, satuan]
$cards = [];
if (isset($kpi['sales'])) {
    $cards[] = ['Penjualan ' . $periodInfo['label'], $kpi['sales'], 'rupiah', 'bi-cart-check', 'blue', $kpi['sales_delta'] ?? null, ''];
}
if (isset($kpi['purchase'])) {
    $cards[] = ['Pembelian ' . $periodInfo['label'], $kpi['purchase'], 'rupiah', 'bi-bag-check', 'green', $kpi['purchase_delta'] ?? null, ''];
}
if (isset($kpi['pu_po'])) {
    $cards[] = ['PO ' . $periodInfo['label'], $kpi['pu_po'], 'int', 'bi-cart-plus', 'teal', null, 'PO'];
}
if (isset($kpi['pu_draft'])) {
    $cards[] = ['PO Menunggu Persetujuan', $kpi['pu_draft'], 'int', 'bi-hourglass-split', 'amber', null, 'PO'];
}
if (isset($kpi['pu_open'])) {
    $cards[] = ['PO Belum Diterima', $kpi['pu_open'], 'int', 'bi-box-arrow-in-down', 'blue', null, 'PO'];
}
if (isset($kpi['suppliers'])) {
    $cards[] = ['Total Supplier', $kpi['suppliers'], 'int', 'bi-truck', 'green', null, 'Supplier'];
}
if (isset($kpi['sl_so'])) {
    $cards[] = ['SO ' . $periodInfo['label'], $kpi['sl_so'], 'int', 'bi-receipt', 'teal', null, 'SO'];
}
if (isset($kpi['sl_open'])) {
    $cards[] = ['SO Belum Terkirim', $kpi['sl_open'], 'int', 'bi-box-arrow-up', 'amber', null, 'SO'];
}
if (isset($kpi['sl_low'])) {
    $cards[] = ['Stok Menipis / Habis', $kpi['sl_low'], 'int', 'bi-exclamation-triangle', 'red', null, 'Produk'];
}
if (isset($kpi['products'])) {
    $cards[] = ['Total Produk', $kpi['products'], 'int', 'bi-box-seam', 'teal', null, 'Item'];
}
if (isset($kpi['customers'])) {
    $cards[] = ['Total Customer', $kpi['customers'], 'int', 'bi-people', 'amber', null, 'Orang'];
}
if (isset($kpi['users'])) {
    $cards[] = ['Pengguna Aktif', $kpi['users'], 'int', 'bi-person-badge', 'red', null, 'Akun'];
}
if (isset($kpi['wh_receipts'])) {
    $cards[] = ['PO Menunggu Diterima', $kpi['wh_receipts'], 'int', 'bi-box-arrow-in-down', 'blue', null, 'PO'];
}
if (isset($kpi['wh_deliveries'])) {
    $cards[] = ['SO Menunggu Dikirim', $kpi['wh_deliveries'], 'int', 'bi-box-arrow-up', 'green', null, 'SO'];
}
if (isset($kpi['wh_low'])) {
    $cards[] = ['Stok di Bawah Minimum', $kpi['wh_low'], 'int', 'bi-exclamation-triangle', 'red', null, 'Produk'];
}
if (isset($kpi['wh_in'])) {
    $cards[] = ['Unit Masuk ' . $periodInfo['label'], $kpi['wh_in'], 'int', 'bi-arrow-down-circle', 'teal', null, 'Unit'];
}
if (isset($kpi['wh_out'])) {
    $cards[] = ['Unit Keluar ' . $periodInfo['label'], $kpi['wh_out'], 'int', 'bi-arrow-up-circle', 'amber', null, 'Unit'];
}

$bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$today = $hari[(int) date('w')] . ', ' . date('j') . ' ' . $bulan[(int) date('n') - 1] . ' ' . date('Y');

$periode = $periodInfo['range_text']; // periode laporan = periode yang dipilih pada filter
$rp      = static fn ($v): string => 'Rp ' . number_format((float) $v, 0, ',', '.');
$tgl     = static fn ($d): string => date('d-m-Y', strtotime((string) $d));
$num     = static fn ($v): string => number_format((int) $v, 0, ',', '.');

// Agregat per status untuk grafik donat laporan (nilai & jumlah dokumen)
$byStatus = static function (array $rows): array {
    $agg = [];
    foreach ($rows as $r) {
        $st                  = (string) $r['status'];
        $agg[$st]['total']   = ($agg[$st]['total'] ?? 0) + (float) $r['total_amount'];
        $agg[$st]['n']       = ($agg[$st]['n'] ?? 0) + 1;
    }
    return $agg;
};
$poStatus = $showRepPo ? $byStatus($repPurchase) : [];
$soStatus = $showRepSo ? $byStatus($repSales) : [];
$hasCharts = $hasCharts || $poStatus !== [] || $soStatus !== [] || ($showWhMove && $whFlow['labels'] !== []);

$animIndex = 0; // dipakai untuk jeda animasi bertahap
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* Semua kelas diawali "dx-" supaya tidak bentrok dengan Bootstrap / layout lain */
.dx {
    --dx-ink: #1e2433;
    --dx-muted: #6b7385;
    --dx-line: #e9ecf4;
    --dx-surface: #ffffff;
    --dx-radius: 20px;
    --dx-shadow: 0 1px 2px rgba(30, 36, 51, .04), 0 8px 24px -12px rgba(30, 36, 51, .12);
    color: var(--dx-ink);
}

.dx-blue {
    --g1: #4c6ef5;
    --g2: #2f4fd4;
    --on: #fff;
    --ghost: rgba(255, 255, 255, .20);
}

.dx-green {
    --g1: #2bd08f;
    --g2: #11a06b;
    --on: #fff;
    --ghost: rgba(255, 255, 255, .20);
}

.dx-teal {
    --g1: #3cc0d6;
    --g2: #26899d;
    --on: #fff;
    --ghost: rgba(255, 255, 255, .20);
}

.dx-amber {
    --g1: #f8c43c;
    --g2: #e0a313;
    --on: #2a2208;
    --ghost: rgba(255, 255, 255, .38);
}

.dx-red {
    --g1: #f0604a;
    --g2: #cc3220;
    --on: #fff;
    --ghost: rgba(255, 255, 255, .20);
}

.dx-violet {
    --g1: #8b5cf6;
    --g2: #6a3fe0;
    --on: #fff;
    --ghost: rgba(255, 255, 255, .20);
}


/* ---------- Animasi masuk ---------- */
@keyframes dx-rise {
    from {
        opacity: 0;
        transform: translateY(14px);
    }

    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes dx-float {

    0%,
    100% {
        transform: translate(0, 0);
    }

    50% {
        transform: translate(-18px, 14px);
    }
}

@keyframes dx-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(225, 29, 72, .45);
    }

    100% {
        box-shadow: 0 0 0 10px rgba(225, 29, 72, 0);
    }
}

/* fill-mode 'backwards' (bukan 'forwards') supaya efek hover translateY pada kartu tidak tertimpa keyframe akhir */
.dx-rise {
    animation: dx-rise .6s cubic-bezier(.2, .7, .2, 1) backwards;
    animation-delay: calc(var(--i, 0) * 70ms);
}

/* ---------- Hero ---------- */
.dx-hero {
    position: relative;
    overflow: hidden;
    border-radius: 26px;
    padding: 1.9rem 2.1rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 52%, #0ea5c6 120%);
    box-shadow: 0 18px 40px -22px rgba(67, 56, 202, .7);
}

.dx-hero::before,
.dx-hero::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
    animation: dx-float 10s ease-in-out infinite;
}

.dx-hero::before {
    width: 280px;
    height: 280px;
    right: -70px;
    top: -110px;
}

.dx-hero::after {
    width: 150px;
    height: 150px;
    right: 140px;
    bottom: -80px;
    animation-duration: 13s;
    animation-direction: reverse;
}

.dx-hero h1 {
    font-size: clamp(1.35rem, 2.4vw, 1.85rem);
    font-weight: 700;
    margin: 0 0 .35rem;
    letter-spacing: -.01em;
}

.dx-hero p {
    margin: 0;
    max-width: 60ch;
    opacity: .88;
    position: relative;
    z-index: 1;
}

.dx-hero .dx-date {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .78rem;
    font-weight: 600;
    padding: .3rem .75rem;
    margin-bottom: .8rem;
    border-radius: 99px;
    background: rgba(255, 255, 255, .18);
    backdrop-filter: blur(6px);
    position: relative;
    z-index: 1;
}

/* ---------- Grid ---------- */
.dx-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.1rem;
    margin-top: 1.4rem;
}

@media (min-width: 1200px) {
    .dx-kpis {
        grid-template-columns: repeat(var(--n, 5), minmax(0, 1fr));
    }
}

/* satu baris di layar lebar */
.dx-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1.1rem;
    margin-top: 1.1rem;
}

.dx-s12 {
    grid-column: span 12;
}

.dx-s8 {
    grid-column: span 8;
}

.dx-s7 {
    grid-column: span 7;
}

.dx-s5 {
    grid-column: span 5;
}

.dx-s4 {
    grid-column: span 4;
}

@media (max-width: 991.98px) {

    .dx-s8,
    .dx-s7,
    .dx-s5,
    .dx-s4 {
        grid-column: span 12;
    }
}

/* ---------- Kartu ---------- */
.dx-card {
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius);
    box-shadow: var(--dx-shadow);
    padding: 1.25rem 1.35rem;
    transition: transform .25s ease, box-shadow .25s ease;
    min-width: 0;
}

.dx-card:hover {
    box-shadow: 0 2px 4px rgba(30, 36, 51, .05), 0 18px 34px -14px rgba(30, 36, 51, .18);
}

.dx-kpi {
    position: relative;
    overflow: hidden;
    border: 0;
    color: var(--on);
    padding: 1.15rem 1.3rem;
    background: linear-gradient(135deg, var(--g1) 0%, var(--g2) 100%);
    box-shadow: 0 14px 26px -16px var(--g2);
}

.dx-kpi:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 32px -16px var(--g2);
}

/* Ikon besar transparan di dalam kartu (watermark) */
.dx-kpi-bg {
    position: absolute;
    right: -.5rem;
    top: 50%;
    font-size: 5.4rem;
    line-height: 1;
    color: var(--ghost);
    transform: translateY(-50%) rotate(-8deg);
    transition: transform .45s cubic-bezier(.2, .7, .2, 1);
    pointer-events: none;
}

.dx-kpi:hover .dx-kpi-bg {
    transform: translateY(-50%) rotate(0deg) scale(1.12);
}

.dx-kpi-label,
.dx-kpi-value,
.dx-kpi-foot {
    position: relative;
    z-index: 1;
}

.dx-kpi-label {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .09em;
    text-transform: uppercase;
    opacity: .92;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dx-kpi-value {
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -.01em;
    line-height: 1.2;
    margin-top: .35rem;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
}

.dx-unit {
    font-size: .6em;
    font-weight: 700;
    margin-left: .25rem;
    opacity: .95;
}

.dx-kpi-foot {
    margin-top: .5rem;
    font-size: .74rem;
}

.dx-chip {
    display: inline-flex;
    align-items: center;
    gap: .1rem;
    padding: .1rem .5rem;
    border-radius: 99px;
    font-weight: 700;
    font-size: .72rem;
    background: rgba(255, 255, 255, .26);
    color: inherit;
}

.dx-chip.up,
.dx-chip.down {
    color: inherit;
}


.dx-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: 1rem;
}

.dx-card-title {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
}

.dx-card-sub {
    font-size: .78rem;
    color: var(--dx-muted);
    margin: .1rem 0 0;
}

.dx-pill {
    font-size: .72rem;
    font-weight: 700;
    padding: .25rem .65rem;
    border-radius: 99px;
    background: #f1f3fa;
    color: var(--dx-muted);
    white-space: nowrap;
}

.dx-chart {
    position: relative;
    height: 290px;
}

.dx-chart.sm {
    height: 260px;
}

.dx-empty {
    display: grid;
    place-items: center;
    text-align: center;
    gap: .35rem;
    padding: 2rem 1rem;
    color: var(--dx-muted);
    font-size: .88rem;
}

.dx-empty i {
    font-size: 2rem;
    opacity: .5;
}

/* ---------- Akun terkunci ---------- */
.dx-lock-list {
    display: flex;
    flex-direction: column;
    gap: .7rem;
    max-height: 300px;
    overflow-y: auto;
    padding-right: 2px;
}

.dx-lock {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .7rem .8rem;
    border: 1px solid var(--dx-line);
    border-radius: 14px;
    background: #fff8f9;
}

.dx-avatar {
    flex: none;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #ffe3ea;
    color: #be123c;
    font-weight: 700;
    font-size: .85rem;
    animation: dx-pulse 1.8s ease-out infinite;
}

.dx-lock-info {
    flex: 1;
    min-width: 0;
}

.dx-lock-name {
    font-weight: 600;
    font-size: .9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dx-lock-meta {
    font-size: .74rem;
    color: var(--dx-muted);
    font-variant-numeric: tabular-nums;
}

.dx-btn {
    border: 0;
    cursor: pointer;
    font-size: .78rem;
    font-weight: 700;
    padding: .45rem .8rem;
    border-radius: 10px;
    color: #fff;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    transition: transform .15s ease, box-shadow .15s ease;
    white-space: nowrap;
}

.dx-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px -8px rgba(79, 70, 229, .8);
}

.dx-btn:active {
    transform: none;
}

.dx-ok {
    color: #047857;
}

@media (prefers-reduced-motion: reduce) {
    .dx-rise {
        opacity: 1;
        animation: none;
    }

    .dx-hero::before,
    .dx-hero::after,
    .dx-avatar {
        animation: none;
    }

    .dx-card,
    .dx-btn {
        transition: none;
    }
}

/* ---------- Hero ringkas: teks di kiri, jam di kanan ---------- */
.dx-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1.25rem;
    align-items: center;
    padding: 1.1rem 1.7rem;
    border-radius: 22px;
}

.dx-hero-main {
    position: relative;
    z-index: 1;
    min-width: 0;
}

.dx-hero h1 {
    font-size: clamp(1.15rem, 2vw, 1.5rem);
    margin: 0 0 .25rem;
}

.dx-hero p {
    font-size: .92rem;
    line-height: 1.45;
}

.dx-hero-side {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .35rem;
    text-align: right;
}

.dx-clock {
    font-size: clamp(1.9rem, 3.4vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -.03em;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    text-shadow: 0 6px 24px rgba(0, 0, 0, .18);
}

.dx-clock small {
    font-size: .45em;
    font-weight: 700;
    opacity: .7;
    margin-left: .1em;
    letter-spacing: 0;
}

.dx-greet {
    font-size: .85rem;
    font-weight: 600;
    opacity: .9;
}

.dx-hero .dx-date {
    margin: 0;
    font-size: .72rem;
    padding: .22rem .65rem;
}

@media (max-width: 767.98px) {
    .dx-hero {
        grid-template-columns: 1fr;
        padding: 1rem 1.2rem;
    }

    .dx-hero-side {
        align-items: flex-start;
        text-align: left;
    }
}

/* ---------- Legend grafik (HTML sendiri, bisa nyala/mati berulang kali) ---------- */
.dx-legend {
    display: flex;
    gap: .45rem;
    flex-wrap: wrap;
}

.dx-leg {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    border: 1px solid var(--dx-line);
    background: #fff;
    border-radius: 99px;
    padding: .3rem .8rem;
    font-size: .78rem;
    font-weight: 700;
    color: var(--dx-ink);
    cursor: pointer;
    transition: background .2s, color .2s, border-color .2s;
}

.dx-leg:hover {
    background: #f6f7fc;
}

.dx-leg .dx-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--c);
    transition: background .2s;
}

.dx-leg.off {
    color: var(--dx-muted);
    background: #f4f5fa;
}

.dx-leg.off .dx-dot {
    background: #c9cedb;
}

.dx-leg.off .dx-leg-t {
    text-decoration: line-through;
}

/* ---------- Pintasan modul ---------- */
.dx-mod-head {
    display: flex;
    align-items: center;
    gap: .65rem;
    margin: 2.1rem 0 .9rem;
}

.dx-mod-head::before {
    content: "";
    width: 5px;
    height: 20px;
    border-radius: 99px;
    background: linear-gradient(180deg, var(--g1), var(--g2));
}

.dx-mod-title {
    margin: 0;
    font-size: .82rem;
    font-weight: 800;
    letter-spacing: .11em;
    text-transform: uppercase;
}

.dx-mod-count {
    margin-left: auto;
    font-size: .72rem;
    font-weight: 700;
    color: var(--dx-muted);
    background: #f1f3fa;
    border-radius: 99px;
    padding: .2rem .65rem;
}

.dx-mods {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.dx-mod {
    position: relative;
    display: flex;
    align-items: center;
    gap: .95rem;
    padding: 1rem 1.1rem;
    border-radius: 18px;
    background: #fff;
    border: 1px solid var(--dx-line);
    box-shadow: var(--dx-shadow);
    color: var(--dx-ink);
    text-decoration: none;
    overflow: hidden;
    isolation: isolate;
    transition: transform .25s ease, box-shadow .25s ease, color .25s ease, border-color .25s ease;
}

.dx-mod::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    background: linear-gradient(135deg, var(--g1), var(--g2));
    opacity: 0;
    transition: opacity .3s ease;
}

.dx-mod:hover {
    transform: translateY(-4px);
    color: var(--on);
    border-color: transparent;
    box-shadow: 0 18px 30px -16px var(--g2);
}

.dx-mod:hover::before {
    opacity: 1;
}

.dx-mod-ico {
    flex: none;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 15px;
    font-size: 1.3rem;
    color: var(--on);
    background: linear-gradient(135deg, var(--g1), var(--g2));
    box-shadow: 0 10px 18px -10px var(--g2);
    transition: background .3s ease, transform .3s ease, box-shadow .3s ease;
}

.dx-mod:hover .dx-mod-ico {
    background: rgba(255, 255, 255, .22);
    box-shadow: none;
    transform: rotate(-6deg) scale(1.06);
}

.dx-mod-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: .12rem;
}

.dx-mod-name {
    font-weight: 700;
    font-size: .95rem;
}

.dx-mod-desc {
    font-size: .78rem;
    color: var(--dx-muted);
    line-height: 1.35;
    transition: color .25s ease;
}

.dx-mod:hover .dx-mod-desc {
    color: inherit;
    opacity: .9;
}

.dx-mod-go {
    font-size: 1.6rem;
    line-height: 1;
    opacity: 0;
    transform: translateX(-8px);
    transition: opacity .25s ease, transform .25s ease;
}

.dx-mod:hover .dx-mod-go {
    opacity: 1;
    transform: none;
}

.dx-mod.is-off {
    background: #f7f8fc;
    box-shadow: none;
    cursor: not-allowed;
}

.dx-mod.is-off .dx-mod-ico {
    background: #e4e7f1;
    color: #9aa1b5;
    box-shadow: none;
}

.dx-mod.is-off:hover {
    transform: none;
    color: var(--dx-ink);
    border-color: var(--dx-line);
    box-shadow: none;
}

.dx-mod.is-off:hover::before {
    opacity: 0;
}

.dx-mod.is-off:hover .dx-mod-ico {
    background: #e4e7f1;
    transform: none;
}

.dx-soon {
    font-size: .68rem;
    font-weight: 700;
    padding: .2rem .55rem;
    border-radius: 99px;
    background: #eceef6;
    color: var(--dx-muted);
    white-space: nowrap;
}

@media (prefers-reduced-motion: reduce) {

    .dx-mod,
    .dx-mod::before,
    .dx-mod-ico,
    .dx-mod-go,
    .dx-leg {
        transition: none;
    }
}

/* ---------- Panel Manajer ---------- */
.dx-top-row {
    align-items: start;
}

.dx-chart.tall {
    height: 400px;
}

.dx-head-right {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.dx-link {
    font-size: .8rem;
    font-weight: 700;
    color: #4f46e5;
    text-decoration: none;
    white-space: nowrap;
}

.dx-link:hover {
    text-decoration: underline;
}

.dx-btn.ok {
    background: linear-gradient(135deg, #2bd08f, #11a06b);
}

.dx-btn.ok:hover {
    box-shadow: 0 8px 16px -8px rgba(17, 160, 107, .85);
}

.dx-btn:disabled {
    opacity: .6;
    cursor: wait;
    transform: none;
    box-shadow: none;
}

/* Daftar PO yang menunggu persetujuan: kartu berjajar memenuhi lebar */
.dx-po-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
    gap: .8rem;
    max-height: 360px;
    overflow-y: auto;
    padding-right: 2px;
}

.dx-po {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .9rem;
    padding: .8rem .9rem;
    border: 1px solid var(--dx-line);
    border-radius: 14px;
    background: #fbfbfe;
    transition: border-color .2s, background .2s;
}

.dx-po:hover {
    border-color: #cfd5ee;
    background: #fff;
}

.dx-po-info {
    min-width: 0;
}

.dx-po-no {
    font-weight: 800;
    font-size: .88rem;
    color: var(--dx-ink);
    text-decoration: none;
}

.dx-po-no:hover {
    color: #4f46e5;
}

.dx-po-sup {
    font-size: .85rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dx-po-meta {
    font-size: .73rem;
    color: var(--dx-muted);
}

.dx-po-side {
    flex: none;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .45rem;
}

.dx-po-amt {
    font-weight: 800;
    font-size: .92rem;
    font-variant-numeric: tabular-nums;
}

/* Tabel laporan */
.dx-tablewrap {
    overflow: auto;
    max-height: 420px;
    border: 1px solid var(--dx-line);
    border-radius: 14px;
}

.dx-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .86rem;
}

.dx-table th,
.dx-table td {
    padding: .65rem .95rem;
    text-align: left;
    border-bottom: 1px solid var(--dx-line);
}

.dx-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f6f7fc;
    color: var(--dx-muted);
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    white-space: nowrap;
}

.dx-table tbody tr:hover td {
    background: #fafbff;
}

.dx-table tbody tr:last-child td {
    border-bottom: 0;
}

.dx-table .num {
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.dx-table .nowrap {
    white-space: nowrap;
}

.dx-table .fw {
    font-weight: 700;
}

.dx-table tfoot td {
    position: sticky;
    bottom: 0;
    z-index: 2;
    background: #f6f7fc;
    font-weight: 800;
    border-top: 1px solid var(--dx-line);
    border-bottom: 0;
}

.dx-td-empty {
    text-align: center !important;
    color: var(--dx-muted);
    padding: 1.6rem 1rem !important;
}

.dx-in {
    color: #047857;
    font-weight: 700;
}

.dx-out {
    color: #be123c;
    font-weight: 700;
}

.dx-zero {
    color: #a3a9ba;
}

/* Badge status dokumen */
.dx-badge {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 99px;
    font-size: .72rem;
    font-weight: 700;
    background: #eceef6;
    color: var(--dx-muted);
}

.dx-badge.draft {
    background: #fff4d6;
    color: #8a5a00;
}

.dx-badge.approved,
.dx-badge.selesai,
.dx-badge.aktif {
    background: #dcfce7;
    color: #166534;
}

.dx-badge.open,
.dx-badge.terjadwal {
    background: #e0e7ff;
    color: #3730a3;
}

.dx-badge.dibatalkan {
    background: #ffe4e6;
    color: #9f1239;
}

@media (max-width: 575.98px) {
    .dx-po {
        flex-direction: column;
        align-items: stretch;
    }

    .dx-po-side {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

/* ---------- Panel Staff Gudang ---------- */
.dx-s6 {
    grid-column: span 6;
}

@media (max-width: 991.98px) {
    .dx-s6 {
        grid-column: span 12;
    }
}

.dx-queue {
    display: flex;
    flex-direction: column;
    gap: .7rem;
    max-height: 420px;
    overflow-y: auto;
    padding-right: 2px;
}

a.dx-btn {
    display: inline-block;
    text-decoration: none;
    color: #fff;
}

.dx-prog {
    height: 6px;
    margin: .45rem 0 .3rem;
    border-radius: 99px;
    background: #e9ecf4;
    overflow: hidden;
}

.dx-prog span {
    display: block;
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #4c6ef5, #2bd08f);
}

.dx-cell-sub {
    display: block;
    font-size: .72rem;
    font-weight: 500;
    color: var(--dx-muted);
}

.dx-badge.cukup,
.dx-badge.masuk {
    background: #dcfce7;
    color: #166534;
}

.dx-badge.kurang,
.dx-badge.keluar {
    background: #ffe4e6;
    color: #9f1239;
}

.dx-badge.penyesuaian {
    background: #fff4d6;
    color: #8a5a00;
}

/* ---------- Peringatan stok: warna & bar kapasitas ---------- */
.dx-warn {
    color: #b45309;
    font-weight: 700;
}

.dx-prog.warn span {
    background: linear-gradient(90deg, #f8c43c, #e0a313);
}

.dx-prog.full span {
    background: linear-gradient(90deg, #f0604a, #cc3220);
}

/* ---------- Filter periode ---------- */
.dx-filter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.1rem;
}

.dx-seg {
    display: inline-flex;
    gap: .2rem;
    padding: .25rem;
    border-radius: 99px;
    background: #fff;
    border: 1px solid var(--dx-line);
    box-shadow: var(--dx-shadow);
}

.dx-seg a {
    padding: .4rem 1.05rem;
    border-radius: 99px;
    font-size: .82rem;
    font-weight: 700;
    color: var(--dx-muted);
    text-decoration: none;
    transition: background .2s ease, color .2s ease;
}

.dx-seg a:hover {
    background: #f1f3fa;
    color: var(--dx-ink);
}

.dx-seg a.on {
    color: #fff;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
}

.dx-filter-range {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .8rem;
    font-weight: 600;
    color: var(--dx-muted);
}

@media (prefers-reduced-motion: reduce) {
    .dx-seg a {
        transition: none;
    }
}
</style>

<div class="dx">

    <!-- ===== Hero ===== -->
    <section class="dx-hero dx-rise" style="--i:<?= $animIndex++ ?>">
        <div class="dx-hero-main">
            <h1>Selamat datang, <?= esc($user['full_name']) ?></h1>
            <p>Anda masuk sebagai <strong><?= esc($user['role_name']) ?></strong>. <?= esc($roleDesc) ?></p>
        </div>

        <div class="dx-hero-side">
            <div class="dx-clock" id="dxClock">--:--<small>:--</small></div>
            <div class="dx-greet" id="dxGreet">Selamat datang</div>
            <span class="dx-date"><i class="bi bi-calendar3"></i><?= esc($today) ?></span>
        </div>
    </section>

    <!-- ===== Filter periode: Hari / Minggu / Bulan / Tahun ===== -->
    <?php if ($showFilter): ?>
    <nav class="dx-filter dx-rise" style="--i:<?= $animIndex++ ?>" aria-label="Filter periode">
        <div class="dx-seg" role="group">
            <?php foreach (['day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan', 'year' => 'Tahun'] as $k => $lbl): ?>
            <a href="<?= esc(site_url('dashboard') . '?period=' . $k) ?>"
                class="<?= $periodInfo['key'] === $k ? 'on' : '' ?>"
                <?= $periodInfo['key'] === $k ? 'aria-current="true"' : '' ?>><?= esc($lbl) ?></a>
            <?php endforeach; ?>
        </div>
        <span class="dx-filter-range"><i class="bi bi-calendar-range"></i><?= esc($periodInfo['range_text']) ?></span>
    </nav>
    <?php endif; ?>

    <!-- ===== Kartu ringkasan ===== -->
    <?php if ($cards !== []): ?>
    <section class="dx-kpis" style="--n:<?= count($cards) ?>">
        <?php foreach ($cards as [$label, $value, $fmt, $icon, $tone, $delta, $unit]): ?>
        <article class="dx-card dx-kpi dx-<?= $tone ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <i class="bi <?= $icon ?> dx-kpi-bg"></i>
            <div class="dx-kpi-label"><?= esc($label) ?></div>
            <div class="dx-kpi-value"
                <?= $fmt === 'rupiah' ? 'title="Rp ' . number_format((float) $value, 0, ',', '.') . '"' : '' ?>>
                <span data-count="<?= (float) $value ?>" data-fmt="<?= $fmt ?>">0</span><?php if ($unit !== ''): ?><span
                    class="dx-unit"><?= esc($unit) ?></span><?php endif; ?>
            </div>
            <?php if ($delta !== null): ?>
            <div class="dx-kpi-foot">
                <span class="dx-chip"><i
                        class="bi bi-arrow-<?= $delta >= 0 ? 'up' : 'down' ?>-short"></i><?= abs($delta) ?>%</span>
                <span style="opacity:.85">vs <?= esc($periodInfo['prev']) ?></span>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- ===== Tren + Akun terkunci ===== -->
    <?php if ($showTrend || $showLocked): ?>
    <section class="dx-grid">
        <?php if ($showTrend): ?>
        <article class="dx-card <?= $showLocked ? 'dx-s8' : 'dx-s12' ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Tren Penjualan &amp; Pembelian</h2>
                    <p class="dx-card-sub">Nilai transaksi per <?= esc($periodInfo['unit_name']) ?> &middot;
                        <?= esc($periodInfo['span']) ?></p>
                </div>
                <div class="dx-legend" id="dxTrendLegend"></div>
            </div>
            <div class="dx-chart"><canvas id="dxTrend"></canvas></div>
        </article>
        <?php endif; ?>

        <?php if ($showLocked): ?>
        <article class="dx-card <?= $showTrend ? 'dx-s4' : 'dx-s12' ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Akun Terkunci</h2>
                    <p class="dx-card-sub">Terlalu banyak percobaan login gagal</p>
                </div>
                <span class="dx-pill"><?= count($lockedUsers) ?> akun</span>
            </div>

            <?php if ($lockedUsers === []): ?>
            <div class="dx-empty">
                <i class="bi bi-shield-check dx-ok"></i>
                <strong class="dx-ok">Semua akun aman</strong>
                <span>Tidak ada akun yang sedang terkunci.</span>
            </div>
            <?php else: ?>
            <div class="dx-lock-list">
                <?php foreach ($lockedUsers as $lu): ?>
                <div class="dx-lock">
                    <div class="dx-avatar"><?= esc(strtoupper(mb_substr($lu['full_name'], 0, 1))) ?></div>
                    <div class="dx-lock-info">
                        <div class="dx-lock-name"><?= esc($lu['full_name']) ?></div>
                        <div class="dx-lock-meta">
                            @<?= esc($lu['username']) ?> ·
                            <i class="bi bi-hourglass-split"></i>
                            <span data-until="<?= esc(date('c', strtotime($lu['locked_until']))) ?>">--:--</span>
                        </div>
                    </div>
                    <form action="<?= site_url('users/' . (int) $lu['id'] . '/unlock') ?>" method="post"
                        onsubmit="return confirm('Buka kunci akun <?= esc($lu['username'], 'js') ?> sekarang?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="dx-btn"><i class="bi bi-unlock me-1"></i>Buka</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ===== Persetujuan PO (Manajer) : memanjang satu baris penuh ===== -->
    <?php if ($showApprove): ?>
    <section class="dx-grid">
        <article class="dx-card dx-s12 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Persetujuan Purchase Order</h2>
                    <p class="dx-card-sub">PO berstatus Draft yang menunggu keputusan Anda</p>
                </div>
                <span class="dx-pill"><?= count($pendingPOs) ?> menunggu</span>
            </div>

            <?php if ($pendingPOs === []): ?>
            <div class="dx-empty">
                <i class="bi bi-check2-circle dx-ok"></i>
                <strong class="dx-ok">Semua PO sudah diproses</strong>
                <span>Tidak ada PO yang menunggu persetujuan.</span>
            </div>
            <?php else: ?>
            <div class="dx-po-grid">
                <?php foreach ($pendingPOs as $po): ?>
                <div class="dx-po">
                    <div class="dx-po-info">
                        <a class="dx-po-no" href="<?= site_url('purchase-orders/' . (int) $po['id']) ?>"
                            title="Lihat detail PO"><?= esc($po['po_number']) ?></a>
                        <div class="dx-po-sup"><?= esc($po['supplier_name']) ?></div>
                        <div class="dx-po-meta">
                            <?= esc($tgl($po['po_date'])) ?> &middot; <?= (int) $po['item_count'] ?> item &middot;
                            oleh <?= esc($po['creator']) ?>
                        </div>
                    </div>
                    <div class="dx-po-side">
                        <div class="dx-po-amt"><?= esc($rp($po['total_amount'])) ?></div>
                        <form action="<?= site_url('purchase-orders/' . (int) $po['id'] . '/approve') ?>" method="post"
                            class="dx-approve-form"
                            onsubmit="return confirm('Setujui <?= esc($po['po_number'], 'js') ?> senilai <?= esc($rp($po['total_amount']), 'js') ?>?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="dx-btn ok"><i
                                    class="bi bi-check2-circle me-1"></i>Setujui</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Produk & kategori terlaris : bersampingan ===== -->
    <?php if ($showTop): ?>
    <section class="dx-grid">
        <article class="dx-card dx-s7 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Produk Terlaris</h2>
                    <p class="dx-card-sub">Berdasarkan jumlah unit terjual &middot;
                        <?= esc($periodInfo['range_text']) ?></p>
                </div>
                <span class="dx-pill">Top <?= (int) $topLimit ?></span>
            </div>
            <?php if ($topProducts === []): ?>
            <div class="dx-empty"><i class="bi bi-bar-chart"></i><span>Belum ada penjualan pada periode ini.</span>
            </div>
            <?php else: ?>
            <div class="dx-chart <?= $topLimit > 5 ? 'tall' : 'sm' ?>"><canvas id="dxTopProducts"></canvas></div>
            <?php endif; ?>
        </article>

        <article class="dx-card dx-s5 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Kategori Terlaris</h2>
                    <p class="dx-card-sub">Porsi unit terjual per kategori</p>
                </div>
                <span class="dx-pill"><?= esc($periodInfo['label']) ?></span>
            </div>
            <?php if ($topCategories === []): ?>
            <div class="dx-empty"><i class="bi bi-pie-chart"></i><span>Belum ada penjualan pada periode ini.</span>
            </div>
            <?php else: ?>
            <div class="dx-chart <?= $topLimit > 5 ? 'tall' : 'sm' ?>"><canvas id="dxTopCategories"></canvas></div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Laporan Pembelian (Manajer): tabel + grafik status ===== -->
    <?php if ($showRepPo):
        $totPo = array_sum(array_map(static fn ($r) => $r['status'] === 'Approved' ? (float) $r['total_amount'] : 0.0, $repPurchase));
    ?>
    <section class="dx-grid dx-top-row">
        <article class="dx-card dx-s8 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title"><?= $showPuCreate ? 'Purchase Order' : 'Laporan Pembelian' ?></h2>
                    <p class="dx-card-sub">Purchase Order periode <?= esc($periode) ?></p>
                </div>
                <div class="dx-head-right">
                    <span class="dx-pill"><?= count($repPurchase) ?> PO</span>
                    <?php if ($showPuCreate): ?>
                    <a href="<?= site_url('purchase-orders/new') ?>" class="dx-btn"><i
                            class="bi bi-plus-circle me-1"></i>Buat PO</a>
                    <?php endif; ?>
                    <a href="<?= site_url('reports/purchases') ?>" class="dx-link">Laporan lengkap <i
                            class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
            <div class="dx-tablewrap">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Nomor PO</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($repPurchase === []): ?>
                        <tr>
                            <td colspan="5" class="dx-td-empty">Belum ada Purchase Order pada periode ini.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($repPurchase as $r): ?>
                        <tr>
                            <td class="nowrap fw"><?php if ($showPuReceipt || $showApprove): ?><a class="dx-po-no"
                                    href="<?= site_url('purchase-orders/' . (int) $r['id']) ?>"><?= esc($r['po_number']) ?></a><?php else: ?><?= esc($r['po_number']) ?><?php endif; ?>
                            </td>
                            <td class="nowrap"><?= esc($tgl($r['po_date'])) ?></td>
                            <td><?= esc($r['party_name']) ?></td>
                            <td><span
                                    class="dx-badge <?= esc(strtolower($r['status'])) ?>"><?= esc($r['status']) ?></span>
                            </td>
                            <td class="num"><?= esc($rp($r['total_amount'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($repPurchase !== []): ?>
                    <tfoot>
                        <tr>
                            <td colspan="4">Total Pembelian <span class="dx-cell-sub">hanya PO Approved</span></td>
                            <td class="num"><?= esc($rp($totPo)) ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </article>

        <article class="dx-card dx-s4 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Komposisi Status</h2>
                    <p class="dx-card-sub">Nilai pembelian per status</p>
                </div>
            </div>
            <?php if ($poStatus === []): ?>
            <div class="dx-empty"><i class="bi bi-pie-chart"></i><span>Belum ada data pada periode ini.</span></div>
            <?php else: ?>
            <div class="dx-chart sm"><canvas id="dxPoStatus"></canvas></div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Laporan Penjualan (Manajer): tabel + grafik status ===== -->
    <?php if ($showRepSo):
        $totSo = array_sum(array_map(static fn ($r) => $r['status'] === 'Dibatalkan' ? 0.0 : (float) $r['total_amount'], $repSales));
    ?>
    <section class="dx-grid dx-top-row">
        <article class="dx-card dx-s8 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title"><?= $showSlCreate ? 'Sales Order' : 'Laporan Penjualan' ?></h2>
                    <p class="dx-card-sub">Sales Order periode <?= esc($periode) ?></p>
                </div>
                <div class="dx-head-right">
                    <span class="dx-pill"><?= count($repSales) ?> SO</span>
                    <?php if ($showSlCreate): ?>
                    <a href="<?= site_url('sales-orders/new') ?>" class="dx-btn"><i
                            class="bi bi-plus-circle me-1"></i>Buat SO</a>
                    <?php endif; ?>
                    <a href="<?= site_url('reports/sales') ?>" class="dx-link">Laporan lengkap <i
                            class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
            <div class="dx-tablewrap">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Nomor SO</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($repSales === []): ?>
                        <tr>
                            <td colspan="5" class="dx-td-empty">Belum ada Sales Order pada periode ini.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($repSales as $r): ?>
                        <tr>
                            <td class="nowrap fw"><?php if ($showSlDelivery): ?><a class="dx-po-no"
                                    href="<?= site_url('sales-orders/' . (int) $r['id']) ?>"><?= esc($r['so_number']) ?></a><?php else: ?><?= esc($r['so_number']) ?><?php endif; ?>
                            </td>
                            <td class="nowrap"><?= esc($tgl($r['so_date'])) ?></td>
                            <td><?= esc($r['party_name']) ?></td>
                            <td><span
                                    class="dx-badge <?= esc(strtolower($r['status'])) ?>"><?= esc($r['status']) ?></span>
                            </td>
                            <td class="num"><?= esc($rp($r['total_amount'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($repSales !== []): ?>
                    <tfoot>
                        <tr>
                            <td colspan="4">Total Penjualan <span class="dx-cell-sub">tidak termasuk SO
                                    dibatalkan</span></td>
                            <td class="num"><?= esc($rp($totSo)) ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </article>

        <article class="dx-card dx-s4 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Komposisi Status</h2>
                    <p class="dx-card-sub">Nilai penjualan per status</p>
                </div>
            </div>
            <?php if ($soStatus === []): ?>
            <div class="dx-empty"><i class="bi bi-pie-chart"></i><span>Belum ada data pada periode ini.</span></div>
            <?php else: ?>
            <div class="dx-chart sm"><canvas id="dxSoStatus"></canvas></div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Panel Purchasing: persetujuan PO & penerimaan barang ===== -->
    <?php if ($showPuDraft || $showPuReceipt): ?>
    <section class="dx-grid dx-top-row">
        <?php if ($showPuDraft): ?>
        <article class="dx-card <?= $showPuReceipt ? 'dx-s6' : 'dx-s12' ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Menunggu Persetujuan</h2>
                    <p class="dx-card-sub">PO Draft yang belum disetujui Manajer</p>
                </div>
                <span class="dx-pill"><?= (int) ($kpi['pu_draft'] ?? 0) ?> PO</span>
            </div>
            <?php if ($puDrafts === []): ?>
            <div class="dx-empty">
                <i class="bi bi-check2-circle dx-ok"></i>
                <strong class="dx-ok">Semua PO sudah diproses</strong>
                <span>Tidak ada PO Draft yang menunggu persetujuan.</span>
            </div>
            <?php else: ?>
            <div class="dx-queue">
                <?php foreach ($puDrafts as $po):
                    $wait = (int) $po['waiting_days'];
                ?>
                <div class="dx-po">
                    <div class="dx-po-info">
                        <a class="dx-po-no" href="<?= site_url('purchase-orders/' . (int) $po['id']) ?>"
                            title="Lihat detail PO"><?= esc($po['po_number']) ?></a>
                        <div class="dx-po-sup"><?= esc($po['supplier_name']) ?></div>
                        <div class="dx-po-meta">
                            <?= esc($tgl($po['po_date'])) ?> &middot; <?= (int) $po['item_count'] ?> item &middot;
                            oleh <?= esc($po['creator']) ?>
                        </div>
                        <div class="dx-po-meta">
                            <span
                                class="dx-badge <?= $wait >= 3 ? 'kurang' : 'draft' ?>"><?= $wait <= 0 ? 'Diajukan hari ini' : 'Menunggu ' . $wait . ' hari' ?></span>
                        </div>
                    </div>
                    <div class="dx-po-side">
                        <div class="dx-po-amt"><?= esc($rp($po['total_amount'])) ?></div>
                        <a href="<?= site_url('purchase-orders/' . (int) $po['id'] . '/edit') ?>" class="dx-btn"><i
                                class="bi bi-pencil me-1"></i>Edit</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endif; ?>

        <?php if ($showPuReceipt): ?>
        <article class="dx-card <?= $showPuDraft ? 'dx-s6' : 'dx-s12' ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Penerimaan Barang</h2>
                    <p class="dx-card-sub">PO Approved yang barangnya belum diterima penuh</p>
                </div>
                <span class="dx-pill"><?= (int) ($kpi['pu_open'] ?? 0) ?> PO</span>
            </div>
            <?php if ($puReceipts === []): ?>
            <div class="dx-empty">
                <i class="bi bi-check2-circle dx-ok"></i>
                <strong class="dx-ok">Semua barang sudah diterima</strong>
                <span>Tidak ada PO Approved yang menunggu penerimaan.</span>
            </div>
            <?php else: ?>
            <div class="dx-queue">
                <?php foreach ($puReceipts as $q): ?>
                <div class="dx-po">
                    <div class="dx-po-info">
                        <a class="dx-po-no" href="<?= site_url('purchase-orders/' . (int) $q['id']) ?>"
                            title="Lihat detail PO"><?= esc($q['po_number']) ?></a>
                        <div class="dx-po-sup"><?= esc($q['party_name']) ?></div>
                        <div class="dx-po-meta">
                            PO <?= esc($tgl($q['po_date'])) ?> &middot; <?= (int) $q['open_items'] ?> item belum lengkap
                        </div>
                        <div class="dx-prog" title="<?= (int) $q['received_pct'] ?>% sudah diterima">
                            <span style="width:<?= (int) $q['received_pct'] ?>%"></span>
                        </div>
                        <div class="dx-po-meta">
                            Diterima <?= $num($q['ordered_qty'] - $q['remaining_qty']) ?> dari
                            <?= $num($q['ordered_qty']) ?> unit
                            &middot; <strong>sisa <?= $num($q['remaining_qty']) ?></strong>
                        </div>
                    </div>
                    <div class="dx-po-side">
                        <a href="<?= site_url('purchase-orders/' . (int) $q['id']) ?>" class="dx-btn"><i
                                class="bi bi-eye me-1"></i>Detail</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ===== Peringatan stok Purchasing: di bawah minimum & mendekati batas maksimum ===== -->
    <?php if ($showStockAlerts):
        $nearMin = $stockAlerts['nearMin'] ?? [];
        $nearMax = $stockAlerts['nearMax'] ?? [];
    ?>
    <section class="dx-grid dx-top-row">
        <article class="dx-card dx-s6 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Stok di Bawah Minimum</h2>
                    <p class="dx-card-sub">Barang yang perlu segera diisi ulang</p>
                </div>
                <span class="dx-pill"><?= count($nearMin) ?> produk</span>
            </div>
            <?php if ($nearMin === []): ?>
            <div class="dx-empty">
                <i class="bi bi-shield-check dx-ok"></i>
                <strong class="dx-ok">Semua stok aman</strong>
                <span>Tidak ada produk di bawah batas minimum.</span>
            </div>
            <?php else: ?>
            <div class="dx-tablewrap">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Stok</th>
                            <th class="num">Min</th>
                            <th class="num">Kurang</th>
                            <th class="num">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nearMin as $p):
                            $gap = max(0, (int) $p['min_stock'] - (int) $p['current_stock']);
                        ?>
                        <tr>
                            <td>
                                <span class="fw"><?= esc($p['name']) ?></span>
                                <span class="dx-cell-sub"><?= esc($p['code']) ?> &middot;
                                    <?= esc($p['unit'] ?? '-') ?></span>
                            </td>
                            <td class="num dx-out"><?= $num($p['current_stock']) ?></td>
                            <td class="num"><?= $num($p['min_stock']) ?></td>
                            <td class="num dx-out">+<?= $num($gap) ?></td>
                            <td class="num">
                                <a href="<?= site_url('purchase-orders/new?product_id=' . (int) $p['id']) ?>"
                                    class="dx-btn"><i class="bi bi-cart-plus me-1"></i>Buat PO</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </article>

        <article class="dx-card dx-s6 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Mendekati Batas Maksimum</h2>
                    <p class="dx-card-sub">Stok hampir atau sudah melebihi kapasitas</p>
                </div>
                <span class="dx-pill"><?= count($nearMax) ?> produk</span>
            </div>
            <?php if ($nearMax === []): ?>
            <div class="dx-empty">
                <i class="bi bi-shield-check dx-ok"></i>
                <strong class="dx-ok">Kapasitas aman</strong>
                <span>Tidak ada produk yang mendekati batas maksimum.</span>
            </div>
            <?php else: ?>
            <div class="dx-tablewrap">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Stok</th>
                            <th class="num">Maks</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nearMax as $p):
                            $cur  = (int) $p['current_stock'];
                            $max  = (int) $p['max_stock'];
                            $pct  = $max > 0 ? (int) round($cur / $max * 100) : 0;
                            $full = $cur >= $max;
                        ?>
                        <tr>
                            <td>
                                <span class="fw"><?= esc($p['name']) ?></span>
                                <span class="dx-cell-sub"><?= esc($p['code']) ?> &middot;
                                    <?= esc($p['unit'] ?? '-') ?></span>
                            </td>
                            <td class="num <?= $full ? 'dx-out' : 'dx-warn' ?>"><?= $num($cur) ?></td>
                            <td class="num"><?= $num($max) ?></td>
                            <td style="min-width:90px">
                                <span class="fw"><?= $pct ?>%</span>
                                <div class="dx-prog <?= $full ? 'full' : 'warn' ?>">
                                    <span style="width:<?= min(100, $pct) ?>%"></span>
                                </div>
                            </td>
                            <td>
                                <span
                                    class="dx-badge <?= $full ? 'kurang' : 'draft' ?>"><?= $full ? 'Penuh' : 'Hampir Penuh' ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Panel Purchasing: supplier teratas ===== -->
    <?php if ($showPuSupplier): ?>
    <section class="dx-grid">
        <article class="dx-card dx-s12 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Supplier Teratas</h2>
                    <p class="dx-card-sub">Berdasarkan nilai PO Approved &middot; <?= esc($periodInfo['range_text']) ?>
                    </p>
                </div>
                <span class="dx-pill"><?= esc($periodInfo['label']) ?></span>
            </div>
            <?php if ($puSuppliers === []): ?>
            <div class="dx-empty"><i class="bi bi-truck"></i><span>Belum ada PO Approved pada periode ini.</span></div>
            <?php else: ?>
            <div class="dx-po-grid">
                <?php foreach ($puSuppliers as $i => $su): ?>
                <div class="dx-po">
                    <div class="dx-po-info" style="flex:1">
                        <div class="dx-po-sup"><?= ($i + 1) ?>. <?= esc($su['name']) ?></div>
                        <div class="dx-prog"><span style="width:<?= (int) $su['pct'] ?>%"></span></div>
                        <div class="dx-po-meta"><?= (int) $su['po_count'] ?> Purchase Order</div>
                    </div>
                    <div class="dx-po-side">
                        <div class="dx-po-amt"><?= esc($rp($su['total'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Panel Sales: status pengiriman SO & ketersediaan stok ===== -->
    <?php if ($showSlDelivery || $showSlStock): ?>
    <section class="dx-grid dx-top-row">
        <?php if ($showSlDelivery): ?>
        <article class="dx-card <?= $showSlStock ? 'dx-s6' : 'dx-s12' ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Status Pengiriman SO</h2>
                    <p class="dx-card-sub">SO Open yang barangnya belum terkirim penuh</p>
                </div>
                <span class="dx-pill"><?= (int) ($kpi['sl_open'] ?? 0) ?> SO</span>
            </div>
            <?php if ($slDeliveries === []): ?>
            <div class="dx-empty">
                <i class="bi bi-check2-circle dx-ok"></i>
                <strong class="dx-ok">Semua SO sudah terkirim</strong>
                <span>Tidak ada SO Open yang menunggu pengiriman.</span>
            </div>
            <?php else: ?>
            <div class="dx-queue">
                <?php foreach ($slDeliveries as $q): ?>
                <div class="dx-po">
                    <div class="dx-po-info">
                        <a class="dx-po-no" href="<?= site_url('sales-orders/' . (int) $q['id']) ?>"
                            title="Lihat detail SO"><?= esc($q['so_number']) ?></a>
                        <div class="dx-po-sup"><?= esc($q['party_name']) ?></div>
                        <div class="dx-po-meta">
                            SO <?= esc($tgl($q['so_date'])) ?> &middot; <?= (int) $q['open_items'] ?> item belum lengkap
                        </div>
                        <div class="dx-prog" title="<?= (int) $q['received_pct'] ?>% sudah dikirim">
                            <span style="width:<?= (int) $q['received_pct'] ?>%"></span>
                        </div>
                        <div class="dx-po-meta">
                            Terkirim <?= $num($q['ordered_qty'] - $q['remaining_qty']) ?> dari
                            <?= $num($q['ordered_qty']) ?> unit
                            &middot; <strong>sisa <?= $num($q['remaining_qty']) ?></strong>
                            <?php if ($q['short_items'] > 0): ?>
                            <span class="dx-badge kurang ms-1">Stok kurang (<?= (int) $q['short_items'] ?> item)</span>
                            <?php else: ?>
                            <span class="dx-badge cukup ms-1">Stok cukup</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dx-po-side">
                        <a href="<?= site_url('sales-orders/' . (int) $q['id']) ?>" class="dx-btn"><i
                                class="bi bi-eye me-1"></i>Detail</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endif; ?>

        <?php if ($showSlStock): ?>
        <article class="dx-card <?= $showSlDelivery ? 'dx-s6' : 'dx-s12' ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Ketersediaan Stok</h2>
                    <p class="dx-card-sub">Produk yang habis atau sudah mencapai batas minimum</p>
                </div>
                <div class="dx-head-right">
                    <span class="dx-pill"><?= (int) $slStock['total'] ?> produk</span>
                    <a href="<?= site_url('stock') ?>" class="dx-link">Lihat stok <i
                            class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
            <?php if ($slStock['rows'] === []): ?>
            <div class="dx-empty">
                <i class="bi bi-shield-check dx-ok"></i>
                <strong class="dx-ok">Stok aman</strong>
                <span>Tidak ada produk yang habis atau di batas minimum.</span>
            </div>
            <?php else: ?>
            <div class="dx-tablewrap">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Stok</th>
                            <th class="num">Min</th>
                            <th class="num">Harga Jual</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slStock['rows'] as $p):
                            $cur = (int) $p['current_stock'];
                        ?>
                        <tr>
                            <td>
                                <span class="fw"><?= esc($p['name']) ?></span>
                                <span class="dx-cell-sub"><?= esc($p['code']) ?> &middot;
                                    <?= esc($p['unit_code']) ?></span>
                            </td>
                            <td class="num <?= $cur <= 0 ? 'dx-out' : 'fw' ?>"><?= $num($cur) ?></td>
                            <td class="num"><?= $num($p['min_stock']) ?></td>
                            <td class="num"><?= esc($rp($p['selling_price'])) ?></td>
                            <td><span
                                    class="dx-badge <?= $cur <= 0 ? 'kurang' : 'draft' ?>"><?= $cur <= 0 ? 'Habis' : 'Menipis' ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </article>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ===== Panel Sales: customer teratas ===== -->
    <?php if ($showSlCustomer): ?>
    <section class="dx-grid">
        <article class="dx-card dx-s12 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Customer Teratas</h2>
                    <p class="dx-card-sub">Berdasarkan nilai Sales Order &middot; <?= esc($periodInfo['range_text']) ?>
                    </p>
                </div>
                <span class="dx-pill"><?= esc($periodInfo['label']) ?></span>
            </div>
            <?php if ($slCustomers === []): ?>
            <div class="dx-empty"><i class="bi bi-people"></i><span>Belum ada penjualan pada periode ini.</span></div>
            <?php else: ?>
            <div class="dx-po-grid">
                <?php foreach ($slCustomers as $i => $cu): ?>
                <div class="dx-po">
                    <div class="dx-po-info" style="flex:1">
                        <div class="dx-po-sup"><?= ($i + 1) ?>. <?= esc($cu['name']) ?></div>
                        <div class="dx-prog"><span style="width:<?= (int) $cu['pct'] ?>%"></span></div>
                        <div class="dx-po-meta"><?= (int) $cu['so_count'] ?> Sales Order</div>
                    </div>
                    <div class="dx-po-side">
                        <div class="dx-po-amt"><?= esc($rp($cu['total'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Laporan Stok (Manajer) ===== -->
    <?php if ($showRepStk): ?>
    <section class="dx-grid">
        <article class="dx-card dx-s12 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Laporan Stok</h2>
                    <p class="dx-card-sub">Pergerakan stok periode <?= esc($periode) ?></p>
                </div>
                <div class="dx-head-right">
                    <span class="dx-pill"><?= count($repStock) ?> produk</span>
                    <a href="<?= site_url('reports/stock') ?>" class="dx-link">Laporan lengkap <i
                            class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
            <div class="dx-tablewrap">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th class="num">Stok Awal</th>
                            <th class="num">Masuk</th>
                            <th class="num">Keluar</th>
                            <th class="num">Stok Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($repStock === []): ?>
                        <tr>
                            <td colspan="8" class="dx-td-empty">Belum ada data produk.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($repStock as $r): ?>
                        <tr>
                            <td class="nowrap fw"><?= esc($r['code']) ?></td>
                            <td><?= esc($r['name']) ?></td>
                            <td><?= esc($r['category_name']) ?></td>
                            <td><?= esc($r['unit_code']) ?></td>
                            <td class="num"><?= $num($r['opening']) ?></td>
                            <td class="num <?= $r['qty_in'] > 0 ? 'dx-in' : 'dx-zero' ?>">+<?= $num($r['qty_in']) ?>
                            </td>
                            <td class="num <?= $r['qty_out'] < 0 ? 'dx-out' : 'dx-zero' ?>">
                                -<?= $num(abs($r['qty_out'])) ?></td>
                            <td class="num fw"><?= $num($r['closing']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Antrean kerja Staff Gudang: terima & kirim barang ===== -->
    <?php if ($showWhReceipt || $showWhDelivery): ?>
    <section class="dx-grid dx-top-row">
        <?php if ($showWhReceipt): ?>
        <article class="dx-card <?= $showWhDelivery ? 'dx-s6' : 'dx-s12' ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Penerimaan Barang</h2>
                    <p class="dx-card-sub">PO Approved yang barangnya belum diterima penuh</p>
                </div>
                <span class="dx-pill"><?= (int) ($kpi['wh_receipts'] ?? 0) ?> PO</span>
            </div>
            <?php if ($whReceipts === []): ?>
            <div class="dx-empty">
                <i class="bi bi-check2-circle dx-ok"></i>
                <strong class="dx-ok">Tidak ada antrean penerimaan</strong>
                <span>Semua PO Approved sudah diterima lengkap.</span>
            </div>
            <?php else: ?>
            <div class="dx-queue">
                <?php foreach ($whReceipts as $q): ?>
                <div class="dx-po">
                    <div class="dx-po-info">
                        <a class="dx-po-no" href="<?= site_url('purchase-orders/' . (int) $q['id']) ?>"
                            title="Lihat detail PO"><?= esc($q['po_number']) ?></a>
                        <div class="dx-po-sup"><?= esc($q['party_name']) ?></div>
                        <div class="dx-po-meta">
                            PO <?= esc($tgl($q['po_date'])) ?> &middot; <?= (int) $q['open_items'] ?> item belum lengkap
                        </div>
                        <div class="dx-prog" title="<?= (int) $q['received_pct'] ?>% sudah diterima">
                            <span style="width:<?= (int) $q['received_pct'] ?>%"></span>
                        </div>
                        <div class="dx-po-meta">
                            Diterima <?= $num($q['ordered_qty'] - $q['remaining_qty']) ?> dari
                            <?= $num($q['ordered_qty']) ?> unit
                            &middot; <strong>sisa <?= $num($q['remaining_qty']) ?></strong>
                        </div>
                    </div>
                    <div class="dx-po-side">
                        <a href="<?= site_url('goods-receipts/new?purchase_order_id=' . (int) $q['id']) ?>"
                            class="dx-btn ok"><i class="bi bi-box-arrow-in-down me-1"></i>Terima Barang</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endif; ?>

        <?php if ($showWhDelivery): ?>
        <article class="dx-card <?= $showWhReceipt ? 'dx-s6' : 'dx-s12' ?> dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Pengiriman Barang</h2>
                    <p class="dx-card-sub">SO Open yang barangnya belum terkirim penuh</p>
                </div>
                <span class="dx-pill"><?= (int) ($kpi['wh_deliveries'] ?? 0) ?> SO</span>
            </div>
            <?php if ($whDeliveries === []): ?>
            <div class="dx-empty">
                <i class="bi bi-check2-circle dx-ok"></i>
                <strong class="dx-ok">Tidak ada antrean pengiriman</strong>
                <span>Semua SO Open sudah terkirim lengkap.</span>
            </div>
            <?php else: ?>
            <div class="dx-queue">
                <?php foreach ($whDeliveries as $q): ?>
                <div class="dx-po">
                    <div class="dx-po-info">
                        <a class="dx-po-no" href="<?= site_url('sales-orders/' . (int) $q['id']) ?>"
                            title="Lihat detail SO"><?= esc($q['so_number']) ?></a>
                        <div class="dx-po-sup"><?= esc($q['party_name']) ?></div>
                        <div class="dx-po-meta">
                            SO <?= esc($tgl($q['so_date'])) ?> &middot; <?= (int) $q['open_items'] ?> item belum lengkap
                        </div>
                        <div class="dx-prog" title="<?= (int) $q['received_pct'] ?>% sudah dikirim">
                            <span style="width:<?= (int) $q['received_pct'] ?>%"></span>
                        </div>
                        <div class="dx-po-meta">
                            Terkirim <?= $num($q['ordered_qty'] - $q['remaining_qty']) ?> dari
                            <?= $num($q['ordered_qty']) ?> unit
                            &middot; <strong>sisa <?= $num($q['remaining_qty']) ?></strong>
                            <?php if ($q['short_items'] > 0): ?>
                            <span class="dx-badge kurang ms-1">Stok kurang (<?= (int) $q['short_items'] ?> item)</span>
                            <?php else: ?>
                            <span class="dx-badge cukup ms-1">Stok cukup</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dx-po-side">
                        <a href="<?= site_url('deliveries/new?sales_order_id=' . (int) $q['id']) ?>" class="dx-btn"><i
                                class="bi bi-box-arrow-up me-1"></i>Kirim Barang</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ===== Peringatan stok Staff Gudang: minimum & batas maksimum ===== -->
    <?php if ($showWhStock): ?>
    <section class="dx-grid dx-top-row">
        <article class="dx-card dx-s6 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Stok di Bawah Minimum</h2>
                    <p class="dx-card-sub">Barang yang perlu segera diisi ulang</p>
                </div>
                <div class="dx-head-right">
                    <span class="dx-pill"><?= (int) ($kpi['wh_low'] ?? 0) ?> produk</span>
                    <a href="<?= site_url('stock') ?>" class="dx-link">Lihat stok <i
                            class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
            <?php if ($whLowStock === []): ?>
            <div class="dx-empty">
                <i class="bi bi-shield-check dx-ok"></i>
                <strong class="dx-ok">Semua stok aman</strong>
                <span>Tidak ada produk di bawah batas minimum.</span>
            </div>
            <?php else: ?>
            <div class="dx-tablewrap">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Stok</th>
                            <th class="num">Min</th>
                            <th class="num">Kurang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($whLowStock as $p): ?>
                        <tr>
                            <td>
                                <span class="fw"><?= esc($p['name']) ?></span>
                                <span class="dx-cell-sub"><?= esc($p['code']) ?> &middot;
                                    <?= esc($p['unit_code']) ?></span>
                            </td>
                            <td class="num dx-out"><?= $num($p['current_stock']) ?></td>
                            <td class="num"><?= $num($p['min_stock']) ?></td>
                            <td class="num dx-out">+<?= $num(max(0, $p['min_stock'] - $p['current_stock'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </article>

        <article class="dx-card dx-s6 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Mendekati Batas Maksimum</h2>
                    <p class="dx-card-sub">Stok sudah 90% atau lebih dari kapasitas</p>
                </div>
                <span class="dx-pill"><?= count($whHighStock) ?> produk</span>
            </div>
            <?php if ($whHighStock === []): ?>
            <div class="dx-empty">
                <i class="bi bi-shield-check dx-ok"></i>
                <strong class="dx-ok">Kapasitas aman</strong>
                <span>Tidak ada produk yang mendekati batas maksimum.</span>
            </div>
            <?php else: ?>
            <div class="dx-tablewrap">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Stok</th>
                            <th class="num">Maks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($whHighStock as $p):
                            $cur  = (int) $p['current_stock'];
                            $max  = (int) $p['max_stock'];
                            $full = $cur >= $max;
                        ?>
                        <tr>
                            <td>
                                <span class="fw"><?= esc($p['name']) ?></span>
                                <span class="dx-cell-sub"><?= esc($p['code']) ?> &middot;
                                    <?= esc($p['unit_code']) ?></span>
                            </td>
                            <td class="num fw"><?= $num($cur) ?></td>
                            <td class="num"><?= $num($max) ?></td>
                            <td>
                                <span class="dx-badge <?= $full ? 'kurang' : 'draft' ?>">
                                    <?= $full ? 'Penuh' : 'Hampir penuh' ?> &middot;
                                    <?= (int) round($cur / $max * 100) ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

    <!-- ===== Aktivitas stok Staff Gudang: grafik masuk vs keluar + mutasi terakhir ===== -->
    <?php if ($showWhMove): ?>
    <section class="dx-grid dx-top-row">
        <article class="dx-card dx-s7 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Unit Masuk vs Keluar</h2>
                    <p class="dx-card-sub">Total unit per <?= esc($periodInfo['unit_name']) ?> &middot;
                        <?= esc($periodInfo['span']) ?></p>
                </div>
            </div>
            <div class="dx-chart"><canvas id="dxWhFlow"></canvas></div>
        </article>

        <article class="dx-card dx-s5 dx-rise" style="--i:<?= $animIndex++ ?>">
            <div class="dx-card-head">
                <div>
                    <h2 class="dx-card-title">Mutasi Terakhir</h2>
                    <p class="dx-card-sub">10 pergerakan stok terbaru</p>
                </div>
                <a href="<?= site_url('stock-movements') ?>" class="dx-link">Semua <i
                        class="bi bi-arrow-right-short"></i></a>
            </div>
            <?php if ($whMovements === []): ?>
            <div class="dx-empty"><i class="bi bi-clock-history"></i><span>Belum ada mutasi stok.</span></div>
            <?php else: ?>
            <div class="dx-tablewrap" style="max-height:290px">
                <table class="dx-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Jenis</th>
                            <th class="num">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($whMovements as $m):
                            $chg = (int) $m['quantity_change'];
                        ?>
                        <tr>
                            <td>
                                <span class="fw"><?= esc($m['name']) ?></span>
                                <span class="dx-cell-sub">
                                    <?= esc($m['reference_number'] ?? '-') ?> &middot;
                                    <?= esc($waktu($m['movement_date'])) ?>
                                </span>
                            </td>
                            <td><span
                                    class="dx-badge <?= esc(strtolower($m['movement_type'])) ?>"><?= esc(ucfirst($m['movement_type'])) ?></span>
                            </td>
                            <td class="num <?= $chg > 0 ? 'dx-in' : 'dx-out' ?>">
                                <?= $chg > 0 ? '+' : '-' ?><?= $num(abs($chg)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </article>
    </section>
    <?php endif; ?>

</div>

<!-- ===== Pintasan modul ===== -->
<div class="dx">
    <?php if ($shortcuts === []): ?>
    <div class="alert alert-info">Belum ada fitur yang tersedia untuk peran Anda.</div>
    <?php endif; ?>

    <?php
$tones = ['blue', 'green', 'teal', 'amber', 'red', 'violet'];
$gi    = 0;
foreach ($shortcuts as $group => $items):
    $tone = $tones[$gi++ % count($tones)];
?>
    <section class="dx-<?= $tone ?>">
        <div class="dx-mod-head">
            <h2 class="dx-mod-title"><?= esc($group) ?></h2>
            <span class="dx-mod-count"><?= count($items) ?> modul</span>
        </div>
        <div class="dx-mods">
            <?php foreach ($items as $item): ?>
            <?php if ($item['ready']): ?>
            <a href="<?= site_url($item['path']) ?>" class="dx-mod dx-rise" style="--i:<?= min($animIndex++, 16) ?>">
                <span class="dx-mod-ico"><i class="bi <?= esc($item['icon']) ?>"></i></span>
                <span class="dx-mod-body">
                    <span class="dx-mod-name"><?= esc($item['label']) ?></span>
                    <span class="dx-mod-desc"><?= esc($item['desc']) ?></span>
                </span>
                <i class="bi bi-arrow-right-short dx-mod-go"></i>
            </a>
            <?php else: ?>
            <div class="dx-mod is-off dx-rise" style="--i:<?= min($animIndex++, 16) ?>">
                <span class="dx-mod-ico"><i class="bi <?= esc($item['icon']) ?>"></i></span>
                <span class="dx-mod-body">
                    <span class="dx-mod-name"><?= esc($item['label']) ?></span>
                    <span class="dx-mod-desc"><?= esc($item['desc']) ?></span>
                </span>
                <span class="dx-soon">Segera</span>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</div>

<!-- ===== Data untuk JavaScript (JSON aman, bukan string yang di-echo mentah) ===== -->
<script type="application/json" id="dx-data">
<?= json_encode([
    'trend'         => $trend,
    'topProducts'   => array_map(static fn ($r) => ['name' => $r['name'], 'qty' => (int) $r['qty']], $topProducts),
    'topCategories' => array_map(static fn ($r) => ['name' => $r['name'], 'qty' => (int) $r['qty']], $topCategories),
    'poStatus'      => array_map(static fn ($k, $v) => ['status' => $k, 'total' => $v['total'], 'n' => $v['n']], array_keys($poStatus), $poStatus),
    'soStatus'      => array_map(static fn ($k, $v) => ['status' => $k, 'total' => $v['total'], 'n' => $v['n']], array_keys($soStatus), $soStatus),
    'whFlow'        => $showWhMove ? $whFlow : ['labels' => [], 'in' => [], 'out' => []],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>
</script>

<?php if ($hasCharts): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>

<script>
(function() {
    'use strict';
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const idNum = new Intl.NumberFormat('id-ID');

    /* ---------- Format angka ---------- */
    function rupiah(v) {
        if (v >= 1e9) return 'Rp ' + (v / 1e9).toLocaleString('id-ID', {
            maximumFractionDigits: 2
        }) + ' M';
        if (v >= 1e6) return 'Rp ' + (v / 1e6).toLocaleString('id-ID', {
            maximumFractionDigits: 1
        }) + ' jt';
        return 'Rp ' + idNum.format(Math.round(v));
    }
    const fmtValue = (v, t) => (t === 'rupiah' ? (v >= 1e12 ? rupiah(v) : 'Rp ' + idNum.format(Math.round(v))) :
        idNum.format(Math.round(v)));

    /* ---------- Kartu: angka penuh, font otomatis mengecil agar muat, lalu count-up ---------- */
    const boxes = document.querySelectorAll('.dx-kpi-value');

    function fit(box) {
        box.style.fontSize = '';
        let size = parseFloat(getComputedStyle(box).fontSize);
        while (box.scrollWidth > box.clientWidth && size > 12) {
            size -= 1;
            box.style.fontSize = size + 'px';
        }
    }
    boxes.forEach(function(box) {
        const el = box.querySelector('[data-count]');
        const target = parseFloat(el.dataset.count) || 0,
            type = el.dataset.fmt;
        el.textContent = fmtValue(target, type); // nilai akhir dulu, untuk mengukur lebar
        fit(box);
        if (reduce || target === 0) return;
        const dur = 1100,
            t0 = performance.now();
        (function tick(now) {
            const p = Math.min((now - t0) / dur, 1),
                eased = 1 - Math.pow(1 - p, 3);
            el.textContent = fmtValue(target * eased, type);
            if (p < 1) requestAnimationFrame(tick);
        })(t0);
    });
    let fitTimer;
    window.addEventListener('resize', function() {
        clearTimeout(fitTimer);
        fitTimer = setTimeout(function() {
            boxes.forEach(fit);
        }, 150);
    });

    /* ---------- Hitung mundur sisa waktu kunci akun ---------- */
    const timers = document.querySelectorAll('[data-until]');

    function updateTimers() {
        const now = Date.now();
        timers.forEach(function(el) {
            const left = Math.floor((new Date(el.dataset.until).getTime() - now) / 1000);
            if (left <= 0) {
                el.textContent = 'kunci berakhir';
                return;
            }
            const m = String(Math.floor(left / 60)).padStart(2, '0'),
                s = String(left % 60).padStart(2, '0');
            el.textContent = 'sisa ' + m + ':' + s;
        });
    }
    if (timers.length) {
        updateTimers();
        setInterval(updateTimers, 1000);
    }

    /* ---------- Jam di hero ---------- */
    const pad = (n) => String(n).padStart(2, '0');
    const clockEl = document.getElementById('dxClock'),
        greetEl = document.getElementById('dxGreet');

    function tickClock() {
        if (!clockEl) return;
        const d = new Date(),
            h = d.getHours();
        clockEl.innerHTML = pad(h) + ':' + pad(d.getMinutes()) + '<small>:' + pad(d.getSeconds()) + '</small>';
        if (greetEl) greetEl.textContent = h < 4 ? 'Selamat malam' : h < 11 ? 'Selamat pagi' : h < 15 ?
            'Selamat siang' : h < 18 ? 'Selamat sore' : 'Selamat malam';
    }
    tickClock();
    setInterval(tickClock, 1000);

    /* ---------- Approve PO: cegah klik ganda ---------- */
    document.querySelectorAll('.dx-approve-form').forEach(function(f) {
        f.addEventListener('submit', function(e) {
            if (e.defaultPrevented) return; // dibatalkan lewat dialog konfirmasi
            const b = f.querySelector('button[type=submit]');
            if (b) {
                b.disabled = true;
                b.textContent = 'Memproses...';
            }
        });
    });

    /* ---------- Grafik ---------- */
    if (!window.Chart) return;
    const data = JSON.parse(document.getElementById('dx-data').textContent);
    const INK = '#6b7385',
        GRID = 'rgba(107,115,133,.14)';
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = INK;
    if (reduce) {
        Chart.defaults.animation = false;
    } else {
        Chart.defaults.animation.duration = 1100;
        Chart.defaults.animation.easing = 'easeOutQuart';
    }

    const tooltip = {
        backgroundColor: '#1e2433',
        padding: 12,
        cornerRadius: 10,
        boxPadding: 5,
        titleFont: {
            weight: '700'
        },
    };
    const shorten = (s, n) => (s.length > n ? s.slice(0, n - 1) + '…' : s);

    // Legend HTML: klik = tampil/sembunyi dataset, bisa dinyalakan lagi kapan saja
    function buildLegend(chart, sets) {
        const box = document.getElementById('dxTrendLegend');
        if (!box) return;
        box.innerHTML = '';
        sets.forEach(function(ds, i) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'dx-leg';
            b.setAttribute('aria-pressed', 'true');
            b.style.setProperty('--c', ds.borderColor);
            b.innerHTML = '<span class="dx-dot"></span><span class="dx-leg-t"></span>';
            b.querySelector('.dx-leg-t').textContent = ds.label;
            b.addEventListener('click', function() {
                const wasVisible = chart.isDatasetVisible(i);
                chart.setDatasetVisibility(i, !wasVisible);
                chart.update();
                b.classList.toggle('off', wasVisible);
                b.setAttribute('aria-pressed', String(!wasVisible));
            });
            box.appendChild(b);
        });
    }


    // -- Garis: penjualan vs pembelian
    const trendEl = document.getElementById('dxTrend');
    if (trendEl && data.trend) {
        const ctx = trendEl.getContext('2d');
        const grad = (hex) => {
            const g = ctx.createLinearGradient(0, 0, 0, 290);
            g.addColorStop(0, hex + '44');
            g.addColorStop(1, hex + '00');
            return g;
        };
        const mk = (label, arr, color) => ({
            label: label,
            data: arr,
            borderColor: color,
            backgroundColor: grad(color),
            fill: true,
            tension: .4,
            borderWidth: 3,
            pointRadius: 0,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: color,
            pointHoverBorderWidth: 3,
        });
        const sets = [];
        if (data.trend.sales) sets.push(mk('Penjualan', data.trend.sales, '#4f46e5'));
        if (data.trend.purchase) sets.push(mk('Pembelian', data.trend.purchase, '#10b981'));

        const trendChart = new Chart(trendEl, {
            type: 'line',
            data: {
                labels: data.trend.labels,
                datasets: sets
            },
            options: {
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: Object.assign({}, tooltip, {
                        callbacks: {
                            label: (c) => ' ' + c.dataset.label + ': Rp ' + idNum.format(c.parsed.y)
                        }
                    }),
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            color: GRID
                        },
                        ticks: {
                            callback: (v) => rupiah(v).replace('Rp ', '')
                        }
                    },
                },
            },
        });
        buildLegend(trendChart, sets);
    }

    // -- Batang horizontal: produk terlaris
    const prodEl = document.getElementById('dxTopProducts');
    if (prodEl && data.topProducts.length) {
        const colors = ['#4f46e5', '#5b52ea', '#6d5cf0', '#7b6cf3', '#8b7cf6', '#988bf8', '#a99cf9', '#b8adfa',
            '#c7bffb', '#d6d0fc'
        ];
        new Chart(prodEl, {
            type: 'bar',
            data: {
                labels: data.topProducts.map((r) => shorten(r.name, 26)),
                datasets: [{
                    data: data.topProducts.map((r) => r.qty),
                    backgroundColor: data.topProducts.map((_, i) => colors[i % colors.length]),
                    borderRadius: 10,
                    borderSkipped: false,
                    barThickness: data.topProducts.length > 5 ? 18 : 20
                }],
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: Object.assign({}, tooltip, {
                        callbacks: {
                            label: (c) => ' ' + idNum.format(c.parsed.x) + ' unit terjual'
                        }
                    })
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            color: GRID
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                },
            },
        });
    }

    // -- Donat: kategori terlaris (total unit di tengah)
    const catEl = document.getElementById('dxTopCategories');
    if (catEl && data.topCategories.length) {
        const total = data.topCategories.reduce((a, r) => a + r.qty, 0);
        const centerText = {
            id: 'centerText',
            afterDraw(chart) {
                const a = chart.chartArea,
                    c = chart.ctx,
                    x = (a.left + a.right) / 2,
                    y = (a.top + a.bottom) / 2;
                c.save();
                c.textAlign = 'center';
                c.textBaseline = 'middle';
                c.fillStyle = '#1e2433';
                c.font = '700 24px ' + Chart.defaults.font.family;
                c.fillText(idNum.format(total), x, y - 6);
                c.fillStyle = INK;
                c.font = '12px ' + Chart.defaults.font.family;
                c.fillText('unit terjual', x, y + 16);
                c.restore();
            },
        };
        new Chart(catEl, {
            type: 'doughnut',
            data: {
                labels: data.topCategories.map((r) => r.name),
                datasets: [{
                    data: data.topCategories.map((r) => r.qty),
                    backgroundColor: ['#4f46e5', '#0ea5c6', '#10b981', '#f59e0b', '#f43f5e',
                        '#8b5cf6'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 8
                }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '68%',
                animation: reduce ? false : {
                    animateRotate: true,
                    duration: 1200
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 14
                        }
                    },
                    tooltip: Object.assign({}, tooltip, {
                        callbacks: {
                            label: (c) => ' ' + c.label + ': ' + idNum.format(c.parsed) +
                                ' unit (' + Math.round(c.parsed / total * 100) + '%)'
                        }
                    }),
                },
            },
            plugins: [centerText],
        });
    }

    // -- Donat: komposisi status laporan pembelian & penjualan (total nilai di tengah)
    const STATUS_COLOR = {
        Draft: '#f59e0b',
        Approved: '#10b981',
        Open: '#4f46e5',
        Selesai: '#10b981',
        Dibatalkan: '#f43f5e'
    };

    function statusDonut(canvasId, rows) {
        const el = document.getElementById(canvasId);
        if (!el || !rows || !rows.length) return;
        const total = rows.reduce((a, r) => a + r.total, 0);
        const center = {
            id: 'centerStatus',
            afterDraw(chart) {
                const a = chart.chartArea,
                    c = chart.ctx,
                    x = (a.left + a.right) / 2,
                    y = (a.top + a.bottom) / 2;
                c.save();
                c.textAlign = 'center';
                c.textBaseline = 'middle';
                c.fillStyle = '#1e2433';
                c.font = '700 18px ' + Chart.defaults.font.family;
                c.fillText(rupiah(total), x, y - 6);
                c.fillStyle = INK;
                c.font = '12px ' + Chart.defaults.font.family;
                c.fillText('total', x, y + 14);
                c.restore();
            },
        };
        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: rows.map((r) => r.status),
                datasets: [{
                    data: rows.map((r) => r.total),
                    backgroundColor: rows.map((r) => STATUS_COLOR[r.status] || '#94a3b8'),
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 8
                }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '66%',
                animation: reduce ? false : {
                    animateRotate: true,
                    duration: 1200
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 14
                        }
                    },
                    tooltip: Object.assign({}, tooltip, {
                        callbacks: {
                            label: (c) => ' ' + c.label + ': Rp ' + idNum.format(c.parsed) +
                                ' (' + rows[c.dataIndex].n + ' dokumen)'
                        }
                    }),
                },
            },
            plugins: [center],
        });
    }
    statusDonut('dxPoStatus', data.poStatus);
    statusDonut('dxSoStatus', data.soStatus);

    // -- Batang: unit masuk vs keluar per hari (Staff Gudang)
    const flowEl = document.getElementById('dxWhFlow');
    if (flowEl && data.whFlow && data.whFlow.labels.length) {
        new Chart(flowEl, {
            type: 'bar',
            data: {
                labels: data.whFlow.labels,
                datasets: [{
                        label: 'Masuk',
                        data: data.whFlow.in,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        borderSkipped: false
                    },
                    {
                        label: 'Keluar',
                        data: data.whFlow.out,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        borderSkipped: false
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8
                        }
                    },
                    tooltip: Object.assign({}, tooltip, {
                        callbacks: {
                            label: (c) => ' ' + c.dataset.label + ': ' + idNum.format(c.parsed.y) +
                                ' unit'
                        }
                    }),
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            color: GRID
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                },
            },
        });
    }
})();
</script>

<?= $this->endSection() ?>