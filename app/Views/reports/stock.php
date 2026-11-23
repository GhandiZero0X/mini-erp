<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Laporan Stok</h1>
    <div class="text-muted">Total Produk: <strong><?= $totalItems ?></strong></div>
</div>

<form method="get" action="<?= site_url('reports/stock') ?>" class="row g-2 mb-3">
    <div class="col-auto">
        <label class="form-label small text-muted mb-1">Kategori</label>
        <select name="category_id" class="form-select">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>>
                <?= esc($c['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small text-muted mb-1">Tanggal Mulai</label>
        <input type="date" name="start" class="form-control" value="<?= esc($filter['start']) ?>">
    </div>
    <div class="col-auto align-self-center pt-3">s/d</div>
    <div class="col-auto">
        <label class="form-label small text-muted mb-1">Tanggal Akhir</label>
        <input type="date" name="end" class="form-control" value="<?= esc($filter['end']) ?>">
    </div>
    <div class="col-auto align-self-end">
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
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th class="text-end">Stok Awal</th>
                    <th class="text-end text-success">Masuk</th>
                    <th class="text-end text-danger">Keluar</th>
                    <th class="text-end fw-bold">Stok Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data pada periode ini.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($r['code']) ?></td>
                    <td><?= esc($r['name']) ?></td>
                    <td><?= esc($r['category_name'] ?? '-') ?></td>
                    <td><?= esc($r['unit']) ?></td>
                    <td class="text-end text-secondary"><?= number_format($r['stok_awal'], 0, ',', '.') ?></td>
                    <td class="text-end text-success">+<?= number_format($r['total_masuk'], 0, ',', '.') ?></td>
                    <td class="text-end text-danger">-<?= number_format($r['total_keluar'], 0, ',', '.') ?></td>
                    <td class="text-end fw-bold"><?= number_format($r['stock_akhir'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>