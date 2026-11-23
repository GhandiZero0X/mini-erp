<?php
/**
 * Daftar Delivery. Variabel: $rows, $pager, $search.
 * Delivery dibuat dari daftar SO (tombol Kirim Barang).
 */
$this->extend('layouts/main');
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Delivery</h1>
    <a href="<?= site_url('sales-orders') ?>" class="btn btn-primary">
        <i class="bi bi-truck me-1"></i>Kirim Barang dari SO
    </a>
</div>

<form method="get" action="<?= site_url('deliveries') ?>" class="row g-2 mb-3">
    <div class="col-12 col-md-6 col-lg-4">
        <input type="search" name="q" class="form-control" maxlength="100"
            placeholder="Cari nomor DL, nomor SO, atau customer" value="<?= esc($search) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Cari</button>
        <?php if ($search !== ''): ?>
        <a href="<?= site_url('deliveries') ?>" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Nomor Delivery</th>
                <th>Tanggal</th>
                <th>Nomor SO</th>
                <th>Customer</th>
                <th>Status</th>
                <th class="text-end">Total Dikirim</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">Tidak ada data</td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="text-nowrap fw-semibold"><?= esc($row['delivery_number']) ?></td>
                <td class="text-nowrap"><?= esc(format_date($row['delivery_date'])) ?></td>
                <td class="text-nowrap"><?= esc($row['so_number']) ?></td>
                <td><?= esc($row['customer_name']) ?></td>
                <td><?= status_badge($row['status']) ?></td>
                <td class="text-end"><?= number_format($row['total_qty'], 0, ',', '.') ?></td>
                <td class="text-end text-nowrap">
                    <a href="<?= site_url('deliveries/' . $row['id']) ?>"
                        class="btn btn-sm btn-outline-primary">Detail</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $pager->links('default', 'bootstrap_full') ?>

<?= $this->endSection() ?>