<?php
/**
 * UI-07 Daftar Purchase Order. Variabel: $rows, $pager, $search, $status, $statuses,
 * $canWrite (buat PO), $canApprove (manajer: setujui PO), $canReceive (gudang: terima barang),
 * $receivableOnly (Staf Gudang: hanya PO Approved yang belum diterima penuh).
 */
$this->extend('layouts/main');

$perPage = $pager ? ((int) $pager->getPerPage() ?: 10) : 10;
$page    = $pager ? $pager->getCurrentPage() : 1;
$offset  = ($page - 1) * $perPage;
$total   = max($pager ? (int) $pager->getTotal() : 0, $offset + count($rows));
$colspan = 6;
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "po-" */
.po-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.po-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.po-head>* {
    position: relative;
    z-index: 1;
}

.po-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.po-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.po-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.po-head .btn-light {
    color: #4338ca;
    font-weight: 700;
    border: 0;
    box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .5);
}

.po-head .btn-light:hover {
    background: #fff;
    transform: translateY(-1px);
}

/* Toolbar */
.po-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
}

.po-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    flex: 1 1 100%;
}

.po-search-field {
    position: relative;
    flex: 1 1 250px;
    max-width: 400px;
}

.po-search-field i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    pointer-events: none;
}

.po-search-field input {
    padding-left: 2.3rem;
    border-radius: 99px;
    background: #f8f9fd;
}

.po-select {
    flex: 0 1 180px;
    border-radius: 99px;
    background: #f8f9fd;
}

.po-meta {
    color: var(--app-muted);
    font-size: .82rem;
}

.po-meta b {
    color: var(--app-ink);
}

/* Tabel */
.po-table td {
    vertical-align: middle;
}

.po-code {
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

.po-auto-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-left: .35rem;
    padding: .15rem .55rem;
    border-radius: 99px;
    background: #fff4d6;
    color: #946500;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .02em;
}

.po-date {
    font-size: .85rem;
    color: var(--app-muted);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.po-supplier {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.po-total {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

/* Tombol aksi berikon */
.po-act {
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

.po-act:hover {
    transform: translateY(-1px);
}

.po-act.is-detail:hover {
    background: #e7e9fb;
    color: #4338ca;
}

.po-act.is-approve:hover {
    background: #e3f8ef;
    color: #0f8a5c;
}

.po-act.is-receive:hover {
    background: #e3f8ef;
    color: #0f8a5c;
}

/* Kosong */
.po-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.po-empty-ico {
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

.po-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.po-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* Ponsel */
@media (max-width: 767.98px) {
    .po-head {
        padding: 1.1rem;
    }

    .po-table,
    .po-table tbody,
    .po-table tr,
    .po-table td {
        display: block;
        width: 100%;
    }

    .po-table thead {
        display: none;
    }

    .po-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .po-table tbody tr:last-child {
        border-bottom: 0;
    }

    .po-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .po-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .po-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .po-table td.po-td-act {
        display: flex;
        justify-content: flex-start;
        gap: .5rem;
        margin-top: .5rem;
    }
}
</style>

<!-- Header -->
<div class="po-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="po-head-ico" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
        <div>
            <h2><?= $receivableOnly ? 'PO Menunggu Penerimaan' : 'Purchase Order' ?></h2>
            <p><?= $receivableOnly ? 'Daftar PO Approved yang siap diterima barangnya.' : 'Kelola pesanan pembelian ke supplier.' ?>
            </p>
        </div>
    </div>

    <?php if ($canWrite ?? false): ?>
    <a href="<?= site_url('purchase-orders/new') ?>" class="btn btn-light">
        <i class="bi bi-plus-lg me-1"></i>Buat PO
    </a>
    <?php endif; ?>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar -->
    <div class="po-bar">
        <form method="get" action="<?= site_url('purchase-orders') ?>" class="po-filter">
            <label class="po-search-field mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" maxlength="100"
                    placeholder="Cari nomor PO atau supplier" value="<?= esc($search) ?>">
            </label>
            <?php if (!$receivableOnly): ?>
            <select name="status" class="form-select po-select" aria-label="Filter status">
                <option value="">Semua status</option>
                <?php foreach ($statuses ?? [] as $s): ?>
                <option value="<?= esc($s, 'attr') ?>" <?= $status === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary px-3">Tampilkan</button>
            <?php if ($search !== '' || $status !== ''): ?>
            <a href="<?= site_url('purchase-orders') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($rows !== []): ?>
        <div class="po-meta">
            Menampilkan <b><?= $offset + 1 ?>&ndash;<?= $offset + count($rows) ?></b> dari <b><?= $total ?></b> PO
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 po-table">
            <thead>
                <tr>
                    <th style="width: 200px;">Nomor PO</th>
                    <th style="width: 130px;">Tanggal</th>
                    <th>Supplier</th>
                    <th style="width: 130px;">Status</th>
                    <th class="text-end" style="width: 160px;">Total</th>
                    <th class="text-end" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= $colspan ?>" class="p-0">
                        <div class="po-empty">
                            <div class="po-empty-ico"><i class="bi bi-file-earmark-x"></i></div>
                            <h3>Belum Ada Data</h3>
                            <p><?= $receivableOnly ? 'Tidak ada PO yang menunggu penerimaan barang.' : 'Belum ada Purchase Order yang tercatat.' ?>
                            </p>
                            <?php if (($canWrite ?? false) && !$receivableOnly): ?>
                            <a href="<?= site_url('purchase-orders/new') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Buat PO
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <?php $isAuto = str_starts_with($row['cancel_reason'] ?? '', 'AUTO-PO:'); ?>
                <tr>
                    <td>
                        <span class="po-code"><?= esc($row['po_number']) ?></span>
                        <?php if ($isAuto): ?>
                        <span class="po-auto-badge" title="Dibuat otomatis oleh sistem">
                            <i class="bi bi-robot"></i>Auto-PO
                        </span>
                        <?php endif; ?>
                    </td>
                    <td data-th="Tanggal"><span class="po-date"><?= esc(format_date($row['po_date'])) ?></span></td>
                    <td data-th="Supplier">
                        <div class="po-supplier"><?= esc($row['supplier_name']) ?></div>
                    </td>
                    <td data-th="Status"><?= status_badge($row['status']) ?></td>
                    <td class="text-end" data-th="Total">
                        <span class="po-total"><?= esc(format_rupiah($row['total_amount'])) ?></span>
                    </td>
                    <td class="text-end text-nowrap po-td-act">
                        <a href="<?= site_url('purchase-orders/' . $row['id']) ?>" class="po-act is-detail"
                            title="Lihat Detail" aria-label="Detail <?= esc($row['po_number'], 'attr') ?>">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if (($canApprove ?? false) && $row['status'] === \Config\Documents::PO_DRAFT): ?>
                        <a href="<?= site_url('purchase-orders/' . $row['id']) ?>" class="po-act is-approve"
                            title="Setujui PO" aria-label="Setujui <?= esc($row['po_number'], 'attr') ?>">
                            <i class="bi bi-check2-circle"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (($canReceive ?? false) && $row['status'] === \Config\Documents::PO_APPROVED): ?>
                        <a href="<?= site_url('goods-receipts/new/' . $row['id']) ?>" class="po-act is-receive"
                            title="Terima Barang" aria-label="Terima <?= esc($row['po_number'], 'attr') ?>">
                            <i class="bi bi-box-arrow-in-down"></i>
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