<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3">Top 10 Produk Terlaris</h1>

<form method="get" action="<?= site_url('reports/top-products') ?>" class="row g-2 mb-3">
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

<div class="card shadow-sm col-md-8">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;" class="text-center">Peringkat</th>
                    <th>Produk</th>
                    <th class="text-end">Kuantitas Terjual</th>
                    <th class="text-end">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Belum ada penjualan pada periode ini.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td class="text-center fw-bold fs-5 text-secondary">#<?= $i + 1 ?></td>
                    <td>
                        <span class="fw-semibold"><?= esc($r['code']) ?></span> - <?= esc($r['name']) ?>
                    </td>
                    <td class="text-end fw-semibold"><?= number_format($r['total_qty'], 0, ',', '.') ?></td>
                    <td class="text-end text-success"><?= esc(format_rupiah($r['total_revenue'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>