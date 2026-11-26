<?php
/**
 * UI-04 Produk (daftar). Variabel: $rows, $pager, $search, $canWrite.
 */
$this->extend('layouts/main');

$rows    = $rows ?? [];
$search  = (string) ($search ?? '');
$perPage = $pager ? ((int) $pager->getPerPage() ?: 10) : 10;
$page    = $pager ? $pager->getCurrentPage() : 1;
$offset  = ($page - 1) * $perPage;
$total   = max($pager ? (int) $pager->getTotal() : 0, $offset + count($rows));
$colspan = $canWrite ? 7 : 6;

$rupiah = static function ($v): string {
    $n = (float) $v;

    return 'Rp ' . number_format($n, fmod($n, 1) === 0.0 ? 0 : 2, ',', '.');
};
// Warna kategori konsisten dengan halaman Kategori (nama sama = warna sama)
$tone = static fn (string $n): int => abs(crc32(mb_strtolower(trim($n)))) % 6;
// Margin dari harga beli ke harga jual (persen); null bila harga beli 0
$margin = static function ($buy, $sell): ?float {
    $b = (float) $buy;

    return $b > 0 ? (((float) $sell - $b) / $b) * 100 : null;
};
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "prd-" supaya tidak bentrok dengan Bootstrap / dashboard (dx-) / kategori (cat-) */
.prd-t0 {
    --t-bg: #eef0ff;
    --t-fg: #4338ca;
}

.prd-t1 {
    --t-bg: #e3f8ef;
    --t-fg: #0f8a5c;
}

.prd-t2 {
    --t-bg: #e0f5f9;
    --t-fg: #1f7587;
}

.prd-t3 {
    --t-bg: #fff4d6;
    --t-fg: #946500;
}

.prd-t4 {
    --t-bg: #fde8e4;
    --t-fg: #b3281a;
}

.prd-t5 {
    --t-bg: #efe9fe;
    --t-fg: #5b34c9;
}

/* ---------- Header ---------- */
.prd-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.prd-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.prd-head>* {
    position: relative;
    z-index: 1;
}

.prd-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.prd-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.prd-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.prd-head .btn-light {
    color: #4338ca;
    font-weight: 700;
    border: 0;
    box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .5);
}

.prd-head .btn-light:hover {
    background: #fff;
    transform: translateY(-1px);
}

/* ---------- Toolbar ---------- */
.prd-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
}

.prd-search {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    flex: 1 1 320px;
    max-width: 520px;
}

.prd-search-field {
    position: relative;
    flex: 1 1 220px;
}

.prd-search-field i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    pointer-events: none;
}

.prd-search-field input {
    padding-left: 2.3rem;
    border-radius: 99px;
    background: #f8f9fd;
}

.prd-meta {
    color: var(--app-muted);
    font-size: .82rem;
}

.prd-meta b {
    color: var(--app-ink);
}

.prd-q {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-left: .35rem;
    padding: .15rem .6rem;
    border-radius: 99px;
    background: #eef0ff;
    color: #4338ca;
    font-size: .76rem;
    font-weight: 700;
    overflow-wrap: anywhere;
}

/* ---------- Tabel ---------- */
.prd-table td {
    vertical-align: middle;
}

.prd-code {
    display: inline-block;
    padding: .2rem .55rem;
    border-radius: 8px;
    background: #f1f3f9;
    color: #3b4358;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .8rem;
    font-weight: 600;
    white-space: nowrap;
}

.prd-name {
    font-weight: 700;
    overflow-wrap: anywhere;
}

.prd-cat {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-top: .25rem;
    padding: .12rem .6rem;
    border-radius: 99px;
    background: var(--t-bg);
    color: var(--t-fg);
    font-size: .72rem;
    font-weight: 700;
}

.prd-unit {
    display: inline-block;
    padding: .15rem .55rem;
    border-radius: 8px;
    border: 1px solid var(--app-line);
    color: var(--app-muted);
    font-size: .76rem;
    font-weight: 700;
}

.prd-range {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    white-space: nowrap;
}

.prd-range i {
    color: #b4bacb;
    font-size: .75rem;
}

.prd-range .is-none {
    color: var(--app-muted);
    font-weight: 600;
    font-size: .8rem;
}

.prd-price {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.prd-margin {
    display: block;
    margin-top: .1rem;
    font-size: .72rem;
    font-weight: 700;
    color: var(--app-muted);
}

.prd-margin.is-up {
    color: #0f8a5c;
}

.prd-margin.is-down {
    color: #b91c1c;
}

/* Tombol aksi berikon */
.prd-act {
    display: inline-grid;
    place-items: center;
    width: 34px;
    height: 34px;
    padding: 0;
    border: 0;
    border-radius: 10px;
    background: #f1f3f9;
    color: #475069;
    font-size: .95rem;
    transition: background .15s ease, color .15s ease, transform .15s ease;
}

.prd-act:hover {
    transform: translateY(-1px);
}

.prd-act.is-edit:hover {
    background: #e7e9fb;
    color: #4338ca;
}

.prd-act.is-del:hover {
    background: #fee2e2;
    color: #b91c1c;
}

.prd-act:focus-visible {
    outline: 2px solid #a5b4fc;
    outline-offset: 2px;
}

/* ---------- Kosong ---------- */
.prd-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.prd-empty-ico {
    width: 60px;
    height: 60px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 18px;
    background: #eef0ff;
    color: #4338ca;
    font-size: 1.6rem;
}

.prd-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.prd-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Modal hapus ---------- */
.prd-del-ico {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 18px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 1.5rem;
}

.prd-del-name {
    display: inline-block;
    max-width: 100%;
    padding: .2rem .65rem;
    border-radius: 8px;
    background: #f1f3f9;
    overflow-wrap: anywhere;
}

/* ---------- Ponsel: baris tabel menjadi kartu ---------- */
@media (max-width: 767.98px) {
    .prd-head {
        padding: 1.1rem;
    }

    .prd-table,
    .prd-table tbody,
    .prd-table tr,
    .prd-table td {
        display: block;
        width: 100%;
    }

    .prd-table thead {
        display: none;
    }

    .prd-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .prd-table tbody tr:last-child {
        border-bottom: 0;
    }

    .prd-table tbody td {
        padding: .3rem 0;
        border: 0;
        background: transparent;
        box-shadow: none;
        text-align: left !important;
    }

    .prd-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .prd-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .prd-table td.prd-td-act {
        margin-top: .4rem;
    }
}

@media (prefers-reduced-motion: reduce) {

    .prd-act,
    .prd-head .btn-light {
        transition: none;
    }
}
</style>

<!-- Header halaman -->
<div class="prd-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="prd-head-ico" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
        <div>
            <h2>Produk</h2>
            <p>Daftar produk beserta harga dan batas stok.</p>
        </div>
    </div>

    <?php if ($canWrite): ?>
    <a href="<?= site_url('products/new') ?>" class="btn btn-light">
        <i class="bi bi-plus-lg me-1"></i>Tambah Produk
    </a>
    <?php endif; ?>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <div class="prd-bar">
        <form method="get" action="<?= site_url('products') ?>" class="prd-search">
            <label class="prd-search-field mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" maxlength="100"
                    placeholder="Cari kode atau nama produk" value="<?= esc($search, 'attr') ?>"
                    aria-label="Cari kode atau nama produk" autocomplete="off">
            </label>
            <button type="submit" class="btn btn-primary px-3">Cari</button>
            <?php if ($search !== ''): ?>
            <a href="<?= site_url('products') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($rows !== []): ?>
        <div class="prd-meta">
            Menampilkan <b><?= $offset + 1 ?>&ndash;<?= $offset + count($rows) ?></b> dari <b><?= $total ?></b> produk
            <?php if ($search !== ''): ?>
            <span class="prd-q"><i class="bi bi-funnel"></i><?= esc($search) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 prd-table">
            <thead>
                <tr>
                    <th style="width: 150px;">Kode</th>
                    <th>Produk</th>
                    <th>Satuan</th>
                    <th>Batas Stok</th>
                    <th class="text-end">Harga Beli</th>
                    <th class="text-end">Harga Jual</th>
                    <?php if ($canWrite): ?>
                    <th class="text-end" style="width: 110px;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= $colspan ?>" class="p-0">
                        <div class="prd-empty">
                            <div class="prd-empty-ico"><i
                                    class="bi <?= $search !== '' ? 'bi-search' : 'bi-box-seam' ?>"></i></div>
                            <?php if ($search !== ''): ?>
                            <h3>Produk tidak ditemukan</h3>
                            <p>Tidak ada produk yang cocok dengan &ldquo;<?= esc($search) ?>&rdquo;. Coba kata kunci
                                lain.</p>
                            <a href="<?= site_url('products') ?>" class="btn btn-outline-secondary btn-sm">Reset
                                pencarian</a>
                            <?php else: ?>
                            <h3>Belum ada produk</h3>
                            <?php if ($canWrite): ?>
                            <p>Tambahkan produk pertama untuk mulai mencatat stok dan transaksi.</p>
                            <a href="<?= site_url('products/new') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Produk
                            </a>
                            <?php else: ?>
                            <p class="mb-0">Produk akan tampil di sini setelah ditambahkan.</p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $row):
                    $cat    = (string) ($row['category_name'] ?? '');
                    $min    = (int) ($row['min_stock'] ?? 0);
                    $max    = $row['max_stock'] !== null ? (int) $row['max_stock'] : null;
                    $mg     = $margin($row['purchase_price'], $row['selling_price']);
                    $mgCls  = $mg === null ? '' : ($mg > 0 ? ' is-up' : ($mg < 0 ? ' is-down' : ''));
                    $label  = (string) $row['code'] . ' - ' . (string) $row['name'];
                    ?>
                <tr>
                    <td><span class="prd-code"><?= esc($row['code']) ?></span></td>
                    <td>
                        <div class="prd-name"><?= esc($row['name']) ?></div>
                        <?php if ($cat !== ''): ?>
                        <span class="prd-cat prd-t<?= $tone($cat) ?>"><i class="bi bi-tag"></i><?= esc($cat) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-th="Satuan"><span class="prd-unit"><?= esc($row['unit_code'] ?? '-') ?></span></td>
                    <td data-th="Batas Stok">
                        <span class="prd-range" title="Minimum &rarr; Maksimum">
                            <span><?= $min ?></span>
                            <i class="bi bi-arrow-right"></i>
                            <?php if ($max !== null): ?>
                            <span><?= $max ?></span>
                            <?php else: ?>
                            <span class="is-none">tanpa batas</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="text-end" data-th="Harga Beli">
                        <span class="prd-price"><?= esc($rupiah($row['purchase_price'])) ?></span>
                    </td>
                    <td class="text-end" data-th="Harga Jual">
                        <span class="prd-price"><?= esc($rupiah($row['selling_price'])) ?></span>
                        <?php if ($mg !== null): ?>
                        <span class="prd-margin<?= $mgCls ?>">Margin
                            <?= ($mg > 0 ? '+' : '') . number_format($mg, 1, ',', '.') ?>%</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($canWrite): ?>
                    <td class="text-end text-nowrap prd-td-act">
                        <a href="<?= site_url('products/' . $row['id'] . '/edit') ?>" class="prd-act is-edit"
                            title="Ubah produk" aria-label="Ubah <?= esc($label, 'attr') ?>">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <button type="button" class="prd-act is-del" data-bs-toggle="modal"
                            data-bs-target="#deleteProductModal" title="Hapus produk"
                            aria-label="Hapus <?= esc($label, 'attr') ?>"
                            data-action="<?= esc(site_url('products/' . $row['id'] . '/delete'), 'attr') ?>"
                            data-label="<?= esc($label, 'attr') ?>">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager !== null && $pager->getPageCount() > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-end">
        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($canWrite): ?>
<!-- Satu modal konfirmasi dipakai semua baris; label dan alamat form diisi saat dibuka -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <form method="post" action="" id="deleteProductForm" class="modal-content border-0"
            style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="prd-del-ico"><i class="bi bi-trash3"></i></div>
                <h5 class="fw-bold mb-2" id="deleteProductTitle">Hapus produk?</h5>
                <p class="mb-2 text-muted small">Produk berikut akan dihapus dan tindakan ini tidak dapat dibatalkan.
                </p>
                <strong class="prd-del-name" id="deleteProductLabel"></strong>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill m-0"
                    data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger flex-fill m-0">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('deleteProductModal');
    var form = document.getElementById('deleteProductForm');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function(event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        form.action = btn.getAttribute('data-action');
        document.getElementById('deleteProductLabel').textContent = btn.getAttribute('data-label');
    });
    // Cegah klik ganda saat form dikirim
    form.addEventListener('submit', function() {
        var b = form.querySelector('button[type="submit"]');
        if (b) {
            b.disabled = true;
            b.textContent = 'Menghapus...';
        }
    });
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>