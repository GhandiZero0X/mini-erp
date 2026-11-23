<?php
/**
 * UI-11 Daftar Sales Order. Variabel: $rows, $pager, $search, $status, $statuses,
 * $canWrite, $shippableOnly (Gudang), $canShip.
 */
$this->extend('layouts/main');
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0"><?= $shippableOnly ? 'SO Menunggu Pengiriman' : 'Sales Order' ?></h1>
    <?php if ($canWrite): ?>
    <a href="<?= site_url('sales-orders/new') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Buat SO
    </a>
    <?php endif; ?>
</div>

<form method="get" action="<?= site_url('sales-orders') ?>" class="row g-2 mb-3">
    <div class="col-12 col-md-5 col-lg-4">
        <input type="search" name="q" class="form-control" maxlength="100" placeholder="Cari nomor SO atau customer"
            value="<?= esc($search) ?>">
    </div>
    <?php if (! $shippableOnly): ?>
    <div class="col-6 col-md-3 col-lg-2">
        <select name="status" class="form-select" aria-label="Filter status">
            <option value="">Semua status</option>
            <?php foreach ($statuses as $s): ?>
            <option value="<?= esc($s, 'attr') ?>" <?= $status === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Tampilkan</button>
        <?php if ($search !== '' || $status !== ''): ?>
        <a href="<?= site_url('sales-orders') ?>" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Nomor SO</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Status</th>
                <th class="text-end">Total</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <?= $shippableOnly ? 'Tidak ada SO yang menunggu pengiriman barang' : 'Tidak ada data' ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="text-nowrap fw-semibold"><?= esc($row['so_number']) ?></td>
                <td class="text-nowrap"><?= esc(format_date($row['so_date'])) ?></td>
                <td><?= esc($row['customer_name']) ?></td>
                <td><?= status_badge($row['status']) ?></td>
                <td class="text-end text-nowrap"><?= esc(format_rupiah($row['total_amount'])) ?></td>
                <td class="text-end text-nowrap">
                    <a href="<?= site_url('sales-orders/' . $row['id']) ?>"
                        class="btn btn-sm btn-outline-primary">Detail</a>
                    <?php if ($canShip && $row['status'] === \Config\Documents::SO_OPEN): ?>
                    <a href="<?= site_url('deliveries/new/' . $row['id']) ?>" class="btn btn-sm btn-success">Kirim
                        Barang</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $pager->links() ?>

<?= $this->endSection() ?>