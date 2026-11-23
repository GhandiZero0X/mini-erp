<?php
/** Daftar Goods Receipt. Variabel: $rows, $pager, $search. PO baru diterima lewat daftar PO (tombol Terima Barang). */
$this->extend('layouts/main');
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Goods Receipt</h1>
    <a href="<?= site_url('purchase-orders') ?>" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-down me-1"></i>Terima Barang dari PO
    </a>
</div>

<form method="get" action="<?= site_url('goods-receipts') ?>" class="row g-2 mb-3">
    <div class="col-12 col-md-6 col-lg-4">
        <input type="search" name="q" class="form-control" maxlength="100"
               placeholder="Cari nomor GR, nomor PO, atau supplier" value="<?= esc($search) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Cari</button>
        <?php if ($search !== ''): ?>
            <a href="<?= site_url('goods-receipts') ?>" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Nomor GR</th>
                <th>Tanggal</th>
                <th>Nomor PO</th>
                <th>Supplier</th>
                <th>Status</th>
                <th class="text-end">Total Diterima</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="text-nowrap fw-semibold"><?= esc($row['gr_number']) ?></td>
                    <td class="text-nowrap"><?= esc(format_date($row['receipt_date'])) ?></td>
                    <td class="text-nowrap"><?= esc($row['po_number']) ?></td>
                    <td><?= esc($row['supplier_name']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end"><?= number_format($row['total_qty'], 0, ',', '.') ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= site_url('goods-receipts/' . $row['id']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $pager->links() ?>

<?= $this->endSection() ?>
