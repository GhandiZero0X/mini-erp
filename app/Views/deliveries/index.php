<?php
/**
 * Daftar Delivery. Variabel: $rows, $pager, $search.
 * Delivery dibuat dari daftar SO (tombol Kirim Barang).
 */
$this->extend('layouts/main');

$perPage = $pager ? ((int) $pager->getPerPage() ?: 10) : 10;
$page    = $pager ? $pager->getCurrentPage() : 1;
$offset  = ($page - 1) * $perPage;
$total   = max($pager ? (int) $pager->getTotal() : 0, $offset + count($rows));
$colspan = 7;
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "dl-" */
.dl-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.dl-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.dl-head>* {
    position: relative;
    z-index: 1;
}

.dl-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.dl-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.dl-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.dl-head .btn-light {
    color: #4338ca;
    font-weight: 700;
    border: 0;
    box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .5);
}

.dl-head .btn-light:hover {
    background: #fff;
    transform: translateY(-1px);
}

/* Toolbar */
.dl-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
}

.dl-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    flex: 1 1 100%;
}

.dl-search-field {
    position: relative;
    flex: 1 1 250px;
    max-width: 400px;
}

.dl-search-field i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    pointer-events: none;
}

.dl-search-field input {
    padding-left: 2.3rem;
    border-radius: 99px;
    background: #f8f9fd;
}

.dl-meta {
    color: var(--app-muted);
    font-size: .82rem;
}

.dl-meta b {
    color: var(--app-ink);
}

/* Tabel */
.dl-table td {
    vertical-align: middle;
}

.dl-code {
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

.dl-so-link {
    color: #4338ca;
    font-weight: 600;
    text-decoration: none;
    font-size: .88rem;
}

.dl-so-link:hover {
    text-decoration: underline;
}

.dl-so-text {
    font-weight: 600;
    color: var(--app-ink);
    font-size: .88rem;
    white-space: nowrap;
}

.dl-date {
    font-size: .85rem;
    color: var(--app-muted);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.dl-customer {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.dl-total {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.dl-act {
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

.dl-act:hover {
    transform: translateY(-1px);
    background: #e7e9fb;
    color: #4338ca;
}

/* Kosong */
.dl-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.dl-empty-ico {
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

.dl-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.dl-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* Ponsel */
@media (max-width: 767.98px) {
    .dl-head {
        padding: 1.1rem;
    }

    .dl-table,
    .dl-table tbody,
    .dl-table tr,
    .dl-table td {
        display: block;
        width: 100%;
    }

    .dl-table thead {
        display: none;
    }

    .dl-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .dl-table tbody tr:last-child {
        border-bottom: 0;
    }

    .dl-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .dl-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .dl-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .dl-table td.dl-td-act {
        display: flex;
        justify-content: flex-end;
        margin-top: .5rem;
    }
}
</style>

<!-- Header -->
<div class="dl-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="dl-head-ico" aria-hidden="true"><i class="bi bi-truck"></i></span>
        <div>
            <h2>Delivery</h2>
            <p>Kelola pengiriman barang ke customer berdasarkan Sales Order.</p>
        </div>
    </div>

    <a href="<?= site_url('sales-orders') ?>" class="btn btn-light">
        <i class="bi bi-truck me-1"></i>Kirim Barang dari SO
    </a>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar -->
    <div class="dl-bar">
        <form method="get" action="<?= site_url('deliveries') ?>" class="dl-filter">
            <label class="dl-search-field mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" maxlength="100"
                    placeholder="Cari nomor DL, SO, atau customer" value="<?= esc($search) ?>">
            </label>
            <button type="submit" class="btn btn-primary px-3">Cari</button>
            <?php if ($search !== ''): ?>
            <a href="<?= site_url('deliveries') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($rows !== []): ?>
        <div class="dl-meta">
            Menampilkan <b><?= $offset + 1 ?>&ndash;<?= $offset + count($rows) ?></b> dari <b><?= $total ?></b> delivery
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 dl-table">
            <thead>
                <tr>
                    <th style="width: 160px;">Nomor DL</th>
                    <th style="width: 130px;">Tanggal</th>
                    <th style="width: 140px;">Nomor SO</th>
                    <th>Customer</th>
                    <th style="width: 130px;">Status</th>
                    <th class="text-end" style="width: 130px;">Total</th>
                    <th class="text-end" style="width: 80px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= $colspan ?>" class="p-0">
                        <div class="dl-empty">
                            <div class="dl-empty-ico"><i class="bi bi-truck"></i></div>
                            <h3>Belum Ada Delivery</h3>
                            <p>Delivery dibuat dari halaman Sales Order dengan menekan tombol "Kirim Barang".</p>
                            <a href="<?= site_url('sales-orders') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Buka Sales Order
                            </a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><span class="dl-code"><?= esc($row['delivery_number']) ?></span></td>
                    <td data-th="Tanggal">
                        <span class="dl-date"><?= esc(format_date($row['delivery_date'])) ?></span>
                    </td>
                    <td data-th="Nomor SO">
                        <?php if (! empty($row['sales_order_id'])): ?>
                        <a href="<?= site_url('sales-orders/' . $row['sales_order_id']) ?>" class="dl-so-link">
                            <?= esc($row['so_number']) ?>
                        </a>
                        <?php else: ?>
                        <span class="dl-so-text"><?= esc($row['so_number']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-th="Customer">
                        <div class="dl-customer"><?= esc($row['customer_name']) ?></div>
                    </td>
                    <td data-th="Status"><?= status_badge($row['status']) ?></td>
                    <td class="text-end" data-th="Total">
                        <span class="dl-total"><?= number_format($row['total_qty'], 0, ',', '.') ?></span>
                    </td>
                    <td class="text-end text-nowrap dl-td-act">
                        <a href="<?= site_url('deliveries/' . $row['id']) ?>" class="dl-act" title="Lihat Detail"
                            aria-label="Detail <?= esc($row['delivery_number'], 'attr') ?>">
                            <i class="bi bi-eye"></i>
                        </a>
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