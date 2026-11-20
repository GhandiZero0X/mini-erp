<?php
/**
 * @var array $rows
 * @var \CodeIgniter\Pager\Pager|null $pager
 * @var bool $canWrite
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