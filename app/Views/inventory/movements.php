<?php
/**
 * UI-15 Histori mutasi stok. Variabel: $rows, $pager, $products, $filter, $errors.
 * Filter produk opsional; periode default bulan berjalan; urut dari yang terbaru.
 */
$this->extend('layouts/main');

/** @var array{product_id:int,start:string,end:string} $filter */
$typeBadge = static function (string $t): string {
    return match ($t) {
        'masuk' => 'mov-badge-masuk',
        'keluar' => 'mov-badge-keluar',
        'penyesuaian' => 'mov-badge-adjust',
        default => 'mov-badge-default',
    };
};

// Link referensi hanya untuk pengguna yang berhak membuka dokumen tujuan
$refUrl = static function (array $r): ?string {
    $type = (string) $r['reference_type'];
    $id = $r['reference_id'];
    if ($id === null) return null;

    if (str_starts_with($type, 'GOODS_RECEIPT') && auth_can('goods_receipt', 'R')) {
        return site_url('goods-receipts/' . $id);
    }
    if (str_starts_with($type, 'DELIVERY') && auth_can('delivery', 'R')) {
        return site_url('deliveries/' . $id);
    }
    return null;
};
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "mov-" */
.mov-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.mov-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.mov-head>* {
    position: relative;
    z-index: 1;
}

.mov-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.mov-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.mov-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

/* ---------- Toolbar ---------- */
.mov-bar {
    padding: 1.25rem;
    border-bottom: 1px solid var(--app-line);
    background: #fafbff;
    border-radius: 18px 18px 0 0;
}

.mov-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.mov-filter-group {
    display: flex;
    flex-direction: column;
    gap: .35rem;
    flex: 1 1 180px;
}

.mov-filter-group label {
    font-size: .78rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin: 0;
}

.mov-filter-group .form-control,
.mov-filter-group .form-select {
    border-radius: 10px;
    border-color: #dfe3ee;
    background: #fff;
}

.mov-filter-group .form-select {
    max-width: 100%;
}

/* ---------- Tabel ---------- */
.mov-table td {
    vertical-align: middle;
}

.mov-date {
    font-size: .85rem;
    color: var(--app-muted);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.mov-code {
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

.mov-product {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.mov-unit {
    font-size: .8rem;
    color: var(--app-muted);
    font-weight: 500;
}

/* Badge Jenis Mutasi */
.mov-type-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .25rem .7rem;
    border-radius: 99px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .02em;
}

.mov-badge-masuk {
    background: #e3f8ef;
    color: #0f8a5c;
}

.mov-badge-keluar {
    background: #fde8e4;
    color: #b3281a;
}

.mov-badge-adjust {
    background: #fff4d6;
    color: #946500;
}

.mov-badge-default {
    background: #f1f3f9;
    color: #475069;
}

.mov-qty {
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    font-size: .95rem;
}

.mov-qty.is-up {
    color: #0f8a5c;
}

.mov-qty.is-down {
    color: #b91c1c;
}

.mov-ref {
    font-weight: 600;
    color: #4338ca;
    text-decoration: none;
}

.mov-ref:hover {
    text-decoration: underline;
}

.mov-user {
    font-size: .85rem;
    color: var(--app-muted);
}

/* ---------- Kosong ---------- */
.mov-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.mov-empty-ico {
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

.mov-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.mov-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Ponsel ---------- */
@media (max-width: 767.98px) {
    .mov-head {
        padding: 1.1rem;
    }

    .mov-filter-row {
        flex-direction: column;
        align-items: stretch;
    }

    .mov-table,
    .mov-table tbody,
    .mov-table tr,
    .mov-table td {
        display: block;
        width: 100%;
    }

    .mov-table thead {
        display: none;
    }

    .mov-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .mov-table tbody tr:last-child {
        border-bottom: 0;
    }

    .mov-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .mov-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .mov-table td[data-th]::before {
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
<div class="mov-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="mov-head-ico" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
        <div>
            <h2>Histori Mutasi Stok</h2>
            <p>Lacak semua pergerakan stok masuk, keluar, dan penyesuaian.</p>
        </div>
    </div>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar -->
    <div class="mov-bar">
        <form method="get" action="<?= site_url('stock-movements') ?>" class="mov-filter-row">
            <div class="mov-filter-group" style="flex: 2 1 250px;">
                <label for="product_id">Produk</label>
                <select name="product_id" id="product_id" class="form-select">
                    <option value="">Semua produk</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                        <?= $filter['product_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= esc($p['code'] . ' - ' . $p['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mov-filter-group">
                <label for="start">Tanggal Mulai</label>
                <input type="date" id="start" name="start"
                    class="form-control<?= isset($errors['start']) ? ' is-invalid' : '' ?>"
                    value="<?= esc($filter['start'], 'attr') ?>">
                <?php if (isset($errors['start'])): ?>
                <div class="invalid-feedback d-block"><?= esc($errors['start']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mov-filter-group">
                <label for="end">Tanggal Akhir</label>
                <input type="date" id="end" name="end"
                    class="form-control<?= isset($errors['end']) ? ' is-invalid' : '' ?>"
                    value="<?= esc($filter['end'], 'attr') ?>">
                <?php if (isset($errors['end'])): ?>
                <div class="invalid-feedback d-block"><?= esc($errors['end']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mov-filter-group" style="flex: 0 0 auto; min-width: 100px;">
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
        <table class="table table-hover align-middle mb-0 mov-table">
            <thead>
                <tr>
                    <th style="width: 150px;">Tanggal</th>
                    <th>Produk</th>
                    <th style="width: 130px;">Jenis</th>
                    <th class="text-end" style="width: 100px;">Kuantitas</th>
                    <th>Dokumen Ref.</th>
                    <th>Dicatat Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="6" class="p-0">
                        <div class="mov-empty">
                            <div class="mov-empty-ico"><i class="bi bi-inbox"></i></div>
                            <h3>Tidak Ada Mutasi</h3>
                            <p><?= isset($errors['period']) ? 'Periode yang Anda masukkan tidak valid.' : 'Belum ada pergerakan stok pada periode ini.' ?>
                            </p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $r):
                        $qty = (float) $r['quantity_change'];
                        $sign = $qty > 0 ? '+' : '';
                        $qtyCls = $qty > 0 ? 'is-up' : ($qty < 0 ? 'is-down' : '');
                        $url = $refUrl($r);
                        $typeRaw = (string) $r['movement_type'];
                        ?>
                <tr>
                    <td><span class="mov-date"><?= esc(format_datetime($r['movement_date'])) ?></span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="mov-code"><?= esc($r['code']) ?></span>
                            <span class="mov-product"><?= esc($r['product_name']) ?></span>
                        </div>
                        <span class="mov-unit">Satuan: <?= esc($r['unit']) ?></span>
                    </td>
                    <td data-th="Jenis">
                        <span class="mov-type-badge <?= $typeBadge($typeRaw) ?>">
                            <?= esc(ucfirst($typeRaw)) ?>
                        </span>
                    </td>
                    <td class="text-end" data-th="Kuantitas">
                        <span class="mov-qty <?= $qtyCls ?>">
                            <?= esc($sign . number_format($qty, 0, ',', '.')) ?>
                        </span>
                    </td>
                    <td data-th="Dokumen Ref.">
                        <?php if ($r['reference_number'] === null): ?>
                        <span class="text-muted">-</span>
                        <?php elseif ($url !== null): ?>
                        <a href="<?= $url ?>" class="mov-ref"><?= esc($r['reference_number']) ?></a>
                        <?php else: ?>
                        <span class="text-dark fw-semibold"><?= esc($r['reference_number']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-th="Dicatat Oleh">
                        <span class="mov-user"><i
                                class="bi bi-person-circle me-1"></i><?= esc($r['created_by_name']) ?></span>
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