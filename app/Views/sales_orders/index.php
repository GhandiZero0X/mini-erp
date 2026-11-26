<?php
/**
 * UI-11 Daftar Sales Order. Variabel: $rows, $pager, $search, $status, $statuses,
 * $canWrite, $shippableOnly (Gudang), $canShip.
 */
$this->extend('layouts/main');

$colspan = 6;
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "so-" */
.so-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.so-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.so-head>* {
    position: relative;
    z-index: 1;
}

.so-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.so-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.so-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.so-head .btn-light {
    color: #4338ca;
    font-weight: 700;
    border: 0;
    box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .5);
}

.so-head .btn-light:hover {
    background: #fff;
    transform: translateY(-1px);
}

/* Toolbar */
.so-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
}

.so-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    flex: 1 1 100%;
}

.so-search-field {
    position: relative;
    flex: 1 1 250px;
    max-width: 320px;
}

.so-search-field i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    pointer-events: none;
}

.so-search-field input {
    padding-left: 2.3rem;
    border-radius: 99px;
    background: #f8f9fd;
}

.so-select {
    flex: 0 1 180px;
    border-radius: 99px;
    background: #f8f9fd;
}

.so-meta {
    color: var(--app-muted);
    font-size: .82rem;
}

.so-meta b {
    color: var(--app-ink);
}

/* Tabel */
.so-table td {
    vertical-align: middle;
}

.so-code {
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

.so-date {
    font-size: .85rem;
    color: var(--app-muted);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.so-customer {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.so-total {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

/* Tombol aksi berikon */
.so-act {
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

.so-act:hover {
    transform: translateY(-1px);
}

.so-act.is-detail:hover {
    background: #e7e9fb;
    color: #4338ca;
}

.so-act.is-ship:hover {
    background: #e3f8ef;
    color: #0f8a5c;
}

/* Kosong */
.so-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.so-empty-ico {
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

.so-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.so-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* Ponsel */
@media (max-width: 767.98px) {
    .so-head {
        padding: 1.1rem;
    }

    .so-table,
    .so-table tbody,
    .so-table tr,
    .so-table td {
        display: block;
        width: 100%;
    }

    .so-table thead {
        display: none;
    }

    .so-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .so-table tbody tr:last-child {
        border-bottom: 0;
    }

    .so-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .so-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .so-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .so-table td.so-td-act {
        display: flex;
        justify-content: flex-start;
        gap: .5rem;
        margin-top: .5rem;
    }
}
</style>

<!-- Header -->
<div class="so-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="so-head-ico" aria-hidden="true"><i class="bi bi-receipt"></i></span>
        <div>
            <h2><?= $shippableOnly ? 'SO Menunggu Pengiriman' : 'Sales Order' ?></h2>
            <p><?= $shippableOnly ? 'Daftar pesanan yang siap dikirim ke customer.' : 'Kelola pesanan penjualan ke customer.' ?>
            </p>
        </div>
    </div>

    <?php if ($canWrite): ?>
    <a href="<?= site_url('sales-orders/new') ?>" class="btn btn-light">
        <i class="bi bi-plus-lg me-1"></i>Buat SO
    </a>
    <?php endif; ?>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar -->
    <div class="so-bar">
        <form method="get" action="<?= site_url('sales-orders') ?>" class="so-filter">
            <label class="so-search-field mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" maxlength="100"
                    placeholder="Cari nomor SO atau customer" value="<?= esc($search) ?>">
            </label>
            <?php if (! $shippableOnly): ?>
            <select name="status" class="form-select so-select" aria-label="Filter status">
                <option value="">Semua status</option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?= esc($s, 'attr') ?>" <?= $status === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary px-3">Tampilkan</button>
            <?php if ($search !== '' || $status !== ''): ?>
            <a href="<?= site_url('sales-orders') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 so-table">
            <thead>
                <tr>
                    <th style="width: 160px;">Nomor SO</th>
                    <th style="width: 130px;">Tanggal</th>
                    <th>Customer</th>
                    <th style="width: 150px;">Status</th>
                    <th class="text-end" style="width: 160px;">Total</th>
                    <th class="text-end" style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= $colspan ?>" class="p-0">
                        <div class="so-empty">
                            <div class="so-empty-ico"><i class="bi bi-inbox"></i></div>
                            <h3>Belum Ada Data</h3>
                            <p><?= $shippableOnly ? 'Tidak ada SO yang menunggu pengiriman barang.' : 'Belum ada Sales Order yang tercatat.' ?>
                            </p>
                            <?php if ($canWrite && ! $shippableOnly): ?>
                            <a href="<?= site_url('sales-orders/new') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Buat SO
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><span class="so-code"><?= esc($row['so_number']) ?></span></td>
                    <td data-th="Tanggal"><span class="so-date"><?= esc(format_date($row['so_date'])) ?></span></td>
                    <td data-th="Customer">
                        <div class="so-customer"><?= esc($row['customer_name']) ?></div>
                    </td>
                    <td data-th="Status"><?= status_badge($row['status']) ?></td>
                    <td class="text-end" data-th="Total">
                        <span class="so-total"><?= esc(format_rupiah($row['total_amount'])) ?></span>
                    </td>
                    <td class="text-end text-nowrap so-td-act">
                        <a href="<?= site_url('sales-orders/' . $row['id']) ?>" class="so-act is-detail"
                            title="Lihat Detail" aria-label="Detail <?= esc($row['so_number'], 'attr') ?>">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($canShip && $row['status'] === \Config\Documents::SO_OPEN): ?>
                        <a href="<?= site_url('deliveries/new/' . $row['id']) ?>" class="so-act is-ship"
                            title="Kirim Barang" aria-label="Kirim <?= esc($row['so_number'], 'attr') ?>">
                            <i class="bi bi-truck"></i>
                        </a>
                        <?php endif; ?>
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