<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
/* Prefiks "tp-" (Top Products) */
.tp-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.tp-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.tp-head>* {
    position: relative;
    z-index: 1;
}

.tp-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.tp-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.tp-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

/* ---------- Toolbar / Filter ---------- */
.tp-bar {
    padding: 1.25rem;
    border-bottom: 1px solid var(--app-line);
    background: #fafbff;
    border-radius: 18px 18px 0 0;
}

.tp-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.tp-filter-group {
    display: flex;
    flex-direction: column;
    gap: .35rem;
    flex: 1 1 180px;
    max-width: 250px;
}

.tp-filter-group label {
    font-size: .78rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin: 0;
}

.tp-filter-group .form-control {
    border-radius: 10px;
    border-color: #dfe3ee;
    background: #fff;
}

/* ---------- Tabel ---------- */
.tp-table td {
    vertical-align: middle;
}

/* Badge Peringkat */
.tp-rank {
    display: inline-grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    font-size: .9rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.tp-rank-1 {
    background: #fff4d6;
    color: #946500;
    box-shadow: 0 4px 12px -4px rgba(234, 179, 8, .4);
}

.tp-rank-2 {
    background: #eef0f4;
    color: #475069;
    box-shadow: 0 4px 12px -4px rgba(148, 163, 184, .4);
}

.tp-rank-3 {
    background: #fde8d4;
    color: #b45309;
    box-shadow: 0 4px 12px -4px rgba(249, 115, 22, .4);
}

.tp-rank-other {
    background: #f1f3f9;
    color: #9aa1b5;
}

.tp-rank-1 i,
.tp-rank-2 i,
.tp-rank-3 i {
    font-size: 1rem;
}

/* Nama Produk */
.tp-code {
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

.tp-product {
    font-weight: 600;
    overflow-wrap: anywhere;
    margin-left: .35rem;
}

/* Progress bar qty */
.tp-qty-wrap {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .75rem;
}

.tp-qty-bar {
    flex: 1 1 auto;
    max-width: 120px;
    height: 6px;
    border-radius: 99px;
    background: #f1f3f9;
    overflow: hidden;
}

.tp-qty-bar>span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #6d4aed, #4c6ef5);
}

.tp-qty-val {
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    min-width: 45px;
    text-align: right;
    color: var(--app-ink);
}

/* Pendapatan */
.tp-revenue {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    color: #0f8a5c;
}

/* ---------- Kosong ---------- */
.tp-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.tp-empty-ico {
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

.tp-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.tp-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Ponsel ---------- */
@media (max-width: 767.98px) {
    .tp-head {
        padding: 1.1rem;
    }

    .tp-filter {
        flex-direction: column;
        align-items: stretch;
    }

    .tp-filter-group {
        max-width: 100%;
    }

    .tp-table,
    .tp-table tbody,
    .tp-table tr,
    .tp-table td {
        display: block;
        width: 100%;
    }

    .tp-table thead {
        display: none;
    }

    .tp-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .tp-table tbody tr:last-child {
        border-bottom: 0;
    }

    .tp-table tbody td {
        padding: .35rem 0;
        border: 0;
        text-align: left !important;
    }

    .tp-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .tp-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    /* Rank di mobile tetap inline dengan nama */
    .tp-table td.tp-td-rank {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding-bottom: .5rem;
    }

    .tp-qty-wrap {
        justify-content: flex-end;
    }
}
</style>

<!-- Header -->
<div class="tp-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="tp-head-ico" aria-hidden="true"><i class="bi bi-trophy"></i></span>
        <div>
            <h2>Top 10 Produk Terlaris</h2>
            <p>Peringkat produk berdasarkan kuantitas terjual pada periode tertentu.</p>
        </div>
    </div>

    <a href="<?= site_url('reports/top-products/download?' . http_build_query([
        'start' => $filter['start'],
        'end'   => $filter['end'],
    ])) ?>" class="btn btn-light fw-semibold shadow-sm">
        <i class="bi bi-file-earmark-excel me-1 text-success"></i>Unduh Excel
    </a>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar / Filter -->
    <div class="tp-bar">
        <form method="get" action="<?= site_url('reports/top-products') ?>" class="tp-filter">
            <div class="tp-filter-group">
                <label for="start">Dari Tanggal</label>
                <input type="date" id="start" name="start" class="form-control" value="<?= esc($filter['start']) ?>">
            </div>

            <div class="tp-filter-group">
                <label for="end">Sampai Tanggal</label>
                <input type="date" id="end" name="end" class="form-control" value="<?= esc($filter['end']) ?>">
            </div>

            <div class="tp-filter-group" style="flex: 0 0 auto; min-width: 100px;">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel me-1"></i>Tampilkan
                </button>
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
        <table class="table table-hover align-middle mb-0 tp-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Peringkat</th>
                    <th>Produk</th>
                    <th class="text-end" style="width: 250px;">Kuantitas Terjual</th>
                    <th class="text-end" style="width: 200px;">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="4" class="p-0">
                        <div class="tp-empty">
                            <div class="tp-empty-ico"><i class="bi bi-bar-chart"></i></div>
                            <h3>Tidak Ada Data</h3>
                            <p>Belum ada penjualan pada periode yang Anda pilih.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php
                    // Nilai kuantitas tertinggi (dipakai untuk skala progress bar)
                    $maxQty = 0;
                    foreach ($rows as $r) {
                        $maxQty = max($maxQty, (float) $r['total_qty']);
                    }
                    ?>
                <?php foreach ($rows as $i => $r):
                        $rank = $i + 1;
                        if ($rank === 1) { $rankCls = 'tp-rank-1'; $rankIcon = 'bi-trophy-fill'; }
                        elseif ($rank === 2) { $rankCls = 'tp-rank-2'; $rankIcon = 'bi-award-fill'; }
                        elseif ($rank === 3) { $rankCls = 'tp-rank-3'; $rankIcon = 'bi-award-fill'; }
                        else { $rankCls = 'tp-rank-other'; $rankIcon = null; }
                        
                        $qty = (float) $r['total_qty'];
                        $pct = $maxQty > 0 ? ($qty / $maxQty) * 100 : 0;
                        ?>
                <tr>
                    <td class="tp-td-rank" data-th="Peringkat">
                        <span class="tp-rank <?= $rankCls ?>">
                            <?php if ($rankIcon): ?>
                            <i class="bi <?= $rankIcon ?>"></i>
                            <?php else: ?>
                            #<?= $rank ?>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <span class="tp-code"><?= esc($r['code']) ?></span>
                        <span class="tp-product"><?= esc($r['name']) ?></span>
                    </td>
                    <td class="text-end" data-th="Kuantitas Terjual">
                        <div class="tp-qty-wrap">
                            <div class="tp-qty-bar" aria-hidden="true">
                                <span style="width: <?= number_format($pct, 1, '.', '') ?>%"></span>
                            </div>
                            <span class="tp-qty-val"><?= number_format($qty, 0, ',', '.') ?></span>
                        </div>
                    </td>
                    <td class="text-end" data-th="Total Pendapatan">
                        <span class="tp-revenue"><?= esc(format_rupiah($r['total_revenue'])) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>