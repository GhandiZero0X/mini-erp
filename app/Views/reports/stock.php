<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
/* Prefiks "lstk-" (Laporan Stok) */
.lstk-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.lstk-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.lstk-head>* {
    position: relative;
    z-index: 1;
}

.lstk-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.lstk-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.lstk-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.lstk-head-badge {
    background: rgba(255, 255, 255, .2);
    padding: .5rem 1rem;
    border-radius: 12px;
    font-size: .85rem;
    font-weight: 600;
    backdrop-filter: blur(4px);
}

.lstk-head-badge strong {
    font-size: 1.1rem;
    font-weight: 800;
    margin-left: .25rem;
}

/* ---------- Toolbar / Filter ---------- */
.lstk-bar {
    padding: 1.25rem;
    border-bottom: 1px solid var(--app-line);
    background: #fafbff;
    border-radius: 18px 18px 0 0;
}

.lstk-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.lstk-filter-group {
    display: flex;
    flex-direction: column;
    gap: .35rem;
    flex: 1 1 180px;
    max-width: 220px;
}

.lstk-filter-group label {
    font-size: .78rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin: 0;
}

.lstk-filter-group .form-control,
.lstk-filter-group .form-select {
    border-radius: 10px;
    border-color: #dfe3ee;
    background: #fff;
}

.lstk-filter-actions {
    display: flex;
    gap: .5rem;
    flex: 0 0 auto;
}

/* ---------- Tabel ---------- */
.lstk-table td {
    vertical-align: middle;
}

.lstk-code {
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

.lstk-name {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.lstk-badge {
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

.lstk-unit {
    display: inline-block;
    padding: .15rem .55rem;
    border-radius: 8px;
    border: 1px solid var(--app-line);
    color: var(--app-muted);
    font-size: .76rem;
    font-weight: 700;
}

.lstk-qty {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    font-size: .95rem;
}

.lstk-qty.is-in {
    color: #0f8a5c;
}

.lstk-qty.is-out {
    color: #b91c1c;
}

.lstk-qty.is-final {
    font-weight: 800;
    color: var(--app-ink);
}

/* ---------- Kosong ---------- */
.lstk-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.lstk-empty-ico {
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

.lstk-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.lstk-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Ponsel ---------- */
@media (max-width: 767.98px) {
    .lstk-head {
        padding: 1.1rem;
    }

    .lstk-head-badge {
        width: 100%;
        text-align: center;
    }

    .lstk-filter {
        flex-direction: column;
        align-items: stretch;
    }

    .lstk-filter-group {
        max-width: 100%;
    }

    .lstk-filter-actions {
        width: 100%;
    }

    .lstk-filter-actions .btn {
        flex: 1;
    }

    .lstk-table,
    .lstk-table tbody,
    .lstk-table tr,
    .lstk-table td {
        display: block;
        width: 100%;
    }

    .lstk-table thead {
        display: none;
    }

    .lstk-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .lstk-table tbody tr:last-child {
        border-bottom: 0;
    }

    .lstk-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .lstk-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .lstk-table td[data-th]::before {
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
<div class="lstk-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="lstk-head-ico" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
        <div>
            <h2>Laporan Stok</h2>
            <p>Rangkuman pergerakan dan posisi stok produk.</p>
        </div>
    </div>
    <div class="lstk-head-badge">
        Total Produk: <strong><?= $totalItems ?></strong>
    </div>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar / Filter -->
    <div class="lstk-bar">
        <form method="get" action="<?= site_url('reports/stock') ?>" class="lstk-filter">
            <div class="lstk-filter-group">
                <label for="category_id">Kategori</label>
                <select name="category_id" id="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= esc($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lstk-filter-group">
                <label for="start">Tanggal Mulai</label>
                <input type="date" id="start" name="start" class="form-control" value="<?= esc($filter['start']) ?>">
            </div>

            <div class="lstk-filter-group">
                <label for="end">Tanggal Akhir</label>
                <input type="date" id="end" name="end" class="form-control" value="<?= esc($filter['end']) ?>">
            </div>

            <div class="lstk-filter-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel me-1"></i>Tampilkan
                </button>
                <?php if ($categoryId > 0 || $filter['start'] !== '' || $filter['end'] !== ''): ?>
                <a href="<?= site_url('reports/stock') ?>" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (isset($errors['period'])): ?>
    <div class="alert alert-danger rounded-0 border-0 m-0 py-2 text-center">
        <i class="bi bi-exclamation-triangle me-1"></i><?= esc($errors['period']) ?>
    </div>
    <?php endif; ?>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 lstk-table">
            <thead>
                <tr>
                    <th style="width: 130px;">Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th class="text-end" style="width: 110px;">Stok Awal</th>
                    <th class="text-end" style="width: 100px;">Masuk</th>
                    <th class="text-end" style="width: 100px;">Keluar</th>
                    <th class="text-end" style="width: 120px;">Stok Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="8" class="p-0">
                        <div class="lstk-empty">
                            <div class="lstk-empty-ico"><i class="bi bi-clipboard-x"></i></div>
                            <h3>Tidak Ada Data</h3>
                            <p>Tidak ada data stok produk pada periode yang Anda pilih.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><span class="lstk-code"><?= esc($r['code']) ?></span></td>
                    <td>
                        <div class="lstk-name"><?= esc($r['name']) ?></div>
                    </td>
                    <td data-th="Kategori">
                        <?php if (!empty($r['category_name'])): ?>
                        <span class="lstk-badge"><i class="bi bi-tag"></i><?= esc($r['category_name']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td data-th="Satuan"><span class="lstk-unit"><?= esc($r['unit']) ?></span></td>
                    <td class="text-end" data-th="Stok Awal">
                        <span class="lstk-qty text-muted"><?= number_format($r['stok_awal'], 0, ',', '.') ?></span>
                    </td>
                    <td class="text-end" data-th="Masuk">
                        <span class="lstk-qty is-in">+<?= number_format($r['total_masuk'], 0, ',', '.') ?></span>
                    </td>
                    <td class="text-end" data-th="Keluar">
                        <span class="lstk-qty is-out">-<?= number_format($r['total_keluar'], 0, ',', '.') ?></span>
                    </td>
                    <td class="text-end" data-th="Stok Akhir">
                        <span class="lstk-qty is-final"><?= number_format($r['stock_akhir'], 0, ',', '.') ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>