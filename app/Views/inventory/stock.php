<?php
/**
 * UI-14 Stok per produk. Variabel: $rows, $pager, $categories, $search, $categoryId.
 * Produk berstok 0 tetap tampil (LEFT JOIN), diurutkan menurut nama.
 */
$this->extend('layouts/main');

$perPage = $pager ? ((int) $pager->getPerPage() ?: 10) : 10;
$page    = $pager ? $pager->getCurrentPage() : 1;
$offset  = ($page - 1) * $perPage;
$total   = max($pager ? (int) $pager->getTotal() : 0, $offset + count($rows));
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "stk-" */
.stk-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.stk-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.stk-head>* {
    position: relative;
    z-index: 1;
}

.stk-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.stk-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.stk-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

/* ---------- Toolbar ---------- */
.stk-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
}

.stk-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    flex: 1 1 100%;
}

.stk-search-field {
    position: relative;
    flex: 1 1 250px;
    max-width: 320px;
}

.stk-search-field i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    pointer-events: none;
}

.stk-search-field input {
    padding-left: 2.3rem;
    border-radius: 99px;
    background: #f8f9fd;
}

.stk-select {
    flex: 0 1 200px;
    border-radius: 99px;
    background: #f8f9fd;
}

.stk-meta {
    color: var(--app-muted);
    font-size: .82rem;
}

.stk-meta b {
    color: var(--app-ink);
}

/* ---------- Tabel ---------- */
.stk-table td {
    vertical-align: middle;
}

.stk-code {
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

.stk-name {
    font-weight: 700;
    overflow-wrap: anywhere;
}

.stk-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .15rem .6rem;
    border-radius: 99px;
    background: #eef0ff;
    color: #4338ca;
    font-size: .72rem;
    font-weight: 700;
}

.stk-unit {
    display: inline-block;
    padding: .15rem .55rem;
    border-radius: 8px;
    border: 1px solid var(--app-line);
    color: var(--app-muted);
    font-size: .76rem;
    font-weight: 700;
}

.stk-qty {
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    font-size: .95rem;
}

.stk-qty.is-zero {
    color: var(--app-muted);
    font-weight: 600;
}

/* ---------- Kosong ---------- */
.stk-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.stk-empty-ico {
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

.stk-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.stk-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Ponsel ---------- */
@media (max-width: 767.98px) {
    .stk-head {
        padding: 1.1rem;
    }

    .stk-table,
    .stk-table tbody,
    .stk-table tr,
    .stk-table td {
        display: block;
        width: 100%;
    }

    .stk-table thead {
        display: none;
    }

    .stk-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .stk-table tbody tr:last-child {
        border-bottom: 0;
    }

    .stk-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .stk-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .stk-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
}
</style>

<!-- Header -->
<div class="stk-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="stk-head-ico" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
        <div>
            <h2>Stok Produk</h2>
            <p>Pantau ketersediaan stok fisik setiap produk saat ini.</p>
        </div>
    </div>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar -->
    <div class="stk-bar">
        <form method="get" action="<?= site_url('stock') ?>" class="stk-filter">
            <label class="stk-search-field mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" maxlength="100"
                    placeholder="Cari kode atau nama produk" value="<?= esc($search) ?>">
            </label>

            <select name="category_id" class="form-select stk-select" aria-label="Filter kategori">
                <option value="">Semua kategori</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= esc($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary px-3">Tampilkan</button>

            <?php if ($search !== '' || $categoryId > 0): ?>
            <a href="<?= site_url('stock') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($rows !== []): ?>
        <div class="stk-meta">
            Menampilkan <b><?= $offset + 1 ?>&ndash;<?= $offset + count($rows) ?></b> dari <b><?= $total ?></b> produk
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 stk-table">
            <thead>
                <tr>
                    <th style="width: 150px;">Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th class="text-end" style="width: 120px;">Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="5" class="p-0">
                        <div class="stk-empty">
                            <div class="stk-empty-ico"><i
                                    class="bi <?= ($search !== '' || $categoryId > 0) ? 'bi-search' : 'bi-box-seam' ?>"></i>
                            </div>
                            <?php if ($search !== '' || $categoryId > 0): ?>
                            <h3>Produk tidak ditemukan</h3>
                            <p>Tidak ada produk yang cocok dengan kriteria filter Anda.</p>
                            <a href="<?= site_url('stock') ?>" class="btn btn-outline-secondary btn-sm">Reset filter</a>
                            <?php else: ?>
                            <h3>Belum ada produk</h3>
                            <p>Data stok akan muncul di sini setelah produk ditambahkan.</p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><span class="stk-code"><?= esc($r['code']) ?></span></td>
                    <td>
                        <div class="stk-name"><?= esc($r['name']) ?></div>
                    </td>
                    <td data-th="Kategori">
                        <?php if (!empty($r['category_name'])): ?>
                        <span class="stk-badge"><i class="bi bi-tag"></i><?= esc($r['category_name']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td data-th="Satuan"><span class="stk-unit"><?= esc($r['unit']) ?></span></td>
                    <td class="text-end" data-th="Stok">
                        <span class="stk-qty <?= (int) $r['quantity'] === 0 ? 'is-zero' : '' ?>">
                            <?= number_format((float) $r['quantity'], 0, ',', '.') ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager !== null && $pager->getPageCount() > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-end border-top-0 py-3">
        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>