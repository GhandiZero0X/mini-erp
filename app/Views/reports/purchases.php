<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3">Laporan Pembelian</h1>

<form method="get" action="<?= site_url('reports/purchases') ?>" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="date" name="start" class="form-control" value="<?= esc($filter['start']) ?>">
    </div>
    <div class="col-auto align-self-center">s/d</div>
    <div class="col-auto">
        <input type="date" name="end" class="form-control" value="<?= esc($filter['end']) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Tampilkan</button>
    </div>
</form>

<?php if (isset($errors['period'])): ?>
<div class="alert alert-danger py-2"><?= esc($errors['period']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nomor PO</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Tidak ada data pembelian pada periode ini.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($r['po_number']) ?></td>
                    <td><?= esc(format_date($r['po_date'])) ?></td>
                    <td><?= esc($r['supplier_name']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="text-end"><?= esc(format_rupiah($r['total_amount'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="4" class="text-end">Total Pembelian</th>
                    <th class="text-end"><?= esc(format_rupiah($total)) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?= $this->endSection() ?>