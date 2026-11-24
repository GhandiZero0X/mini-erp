<?php
/**
 * @var array $user
 * @var string $roleDesc
 * @var array $shortcuts
 * @var array|null $stockAlerts
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h1 class="h3 mb-1">Selamat datang, <?= esc($user['full_name']) ?></h1>
    <p class="text-muted mb-0">
        Anda masuk sebagai <strong><?= esc($user['role_name']) ?></strong>. <?= esc($roleDesc) ?>
    </p>
</div>

<?php if (! empty($stockAlerts)): ?>

<?php if (! empty($stockAlerts['nearMin'])): ?>
<h2 class="h6 text-uppercase text-danger mt-4 mb-2">
    <i class="bi bi-exclamation-triangle me-1"></i>Stok di Bawah Minimum
</h2>
<div class="card shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th class="text-end">Stok</th>
                    <th class="text-end">Min</th>
                    <th class="text-end">Kurang</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockAlerts['nearMin'] as $p):
                    $gap = (int) $p['min_stock'] - (int) $p['current_stock'];
                ?>
                <tr>
                    <td class="text-nowrap fw-semibold"><?= esc($p['code']) ?></td>
                    <td>
                        <?= esc($p['name']) ?>
                        <span class="text-muted small">(<?= esc($p['unit'] ?? '-') ?>)</span>
                    </td>
                    <td class="text-end text-danger fw-semibold"><?= (int) $p['current_stock'] ?></td>
                    <td class="text-end"><?= (int) $p['min_stock'] ?></td>
                    <td class="text-end text-danger">+<?= max(0, $gap) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('purchase-orders/new?product_id=' . (int) $p['id']) ?>"
                            class="btn btn-sm btn-primary">
                            <i class="bi bi-cart-plus me-1"></i>Buat PO
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (! empty($stockAlerts['nearMax'])): ?>
<h2 class="h6 text-uppercase text-warning mt-4 mb-2">
    <i class="bi bi-arrow-up-circle me-1"></i>Stok Mendekati Batas Atas
</h2>
<div class="card shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th class="text-end">Stok</th>
                    <th class="text-end">Maks</th>
                    <th class="text-end">Kapasitas</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockAlerts['nearMax'] as $p):
                    $current = (int) $p['current_stock'];
                    $max     = (int) $p['max_stock'];
                    $pct     = $max > 0 ? (int) round(($current / $max) * 100) : 0;
                    $isFull  = $current >= $max;
                ?>
                <tr>
                    <td class="text-nowrap fw-semibold"><?= esc($p['code']) ?></td>
                    <td>
                        <?= esc($p['name']) ?>
                        <span class="text-muted small">(<?= esc($p['unit'] ?? '-') ?>)</span>
                    </td>
                    <td class="text-end <?= $isFull ? 'text-danger fw-semibold' : 'text-warning fw-semibold' ?>">
                        <?= $current ?>
                    </td>
                    <td class="text-end"><?= $max ?></td>
                    <td class="text-end"><?= $pct ?>%</td>
                    <td class="text-center">
                        <?php if ($isFull): ?>
                        <span class="badge text-bg-danger">Penuh</span>
                        <?php else: ?>
                        <span class="badge text-bg-warning">Hampir Penuh</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php if ($shortcuts === []): ?>
<div class="alert alert-info">Belum ada fitur yang tersedia untuk peran Anda.</div>
<?php endif; ?>

<?php foreach ($shortcuts as $group => $items): ?>
<h2 class="h6 text-uppercase text-muted mt-4 mb-2"><?= esc($group) ?></h2>
<div class="row g-3">
    <?php foreach ($items as $item): ?>
    <div class="col-sm-6 col-lg-4 col-xl-3">
        <div class="card h-100 <?= $item['ready'] ? 'shadow-sm' : 'bg-light' ?>">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi <?= esc($item['icon']) ?> fs-4 text-primary me-2"></i>
                    <span class="fw-semibold"><?= esc($item['label']) ?></span>
                </div>
                <p class="small text-muted mb-3"><?= esc($item['desc']) ?></p>
                <?php if ($item['ready']): ?>
                <a href="<?= site_url($item['path']) ?>" class="btn btn-sm btn-primary stretched-link">Buka</a>
                <?php else: ?>
                <span class="badge text-bg-secondary">Segera tersedia</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?= $this->endSection() ?>