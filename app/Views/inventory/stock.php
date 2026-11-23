<?php
/**
 * UI-14 Stok per produk. Variabel: $rows, $pager, $categories, $search, $categoryId.
 * Produk berstok 0 tetap tampil (LEFT JOIN), diurutkan menurut nama.
 */
$this->extend('layouts/main');
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Stok Produk</h1>
</div>

<form method="get" action="<?= site_url('stock') ?>" class="row g-2 mb-3">
    <div class="col-12 col-md-4 col-lg-3">
        <input type="search" name="q" class="form-control" maxlength="100" placeholder="Cari kode atau nama produk"
            value="<?= esc($search) ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <select name="category_id" class="form-select" aria-label="Filter kategori">
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= esc($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Tampilkan</button>
        <?php if ($search !== '' || $categoryId > 0): ?>
            <a href="<?= site_url('stock') ?>" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Satuan</th>
                <th class="text-end">Stok</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-nowrap fw-semibold"><?= esc($r['code']) ?></td>
                        <td><?= esc($r['name']) ?></td>
                        <td><?= esc($r['category_name'] ?? '-') ?></td>
                        <td><?= esc($r['unit']) ?></td>
                        <td class="text-end fw-semibold">
                            <?php if ((int) $r['quantity'] === 0): ?>
                                <span class="text-muted">0</span>
                            <?php else: ?>
                                <?= number_format($r['quantity'], 0, ',', '.') ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $pager->links('default', 'bootstrap_full') ?>

<?= $this->endSection() ?>