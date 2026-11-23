<?php
/**
 * UI-22 Detail Delivery (batal). Variabel: $dl (header + 'items', 'total_qty'), $errors.
 */
$this->extend('layouts/main');

use Config\Documents;

$canCancelDL = $dl['status'] === Documents::DL_ACTIVE && auth_can('delivery', 'W');
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">
        <?= esc($dl['delivery_number']) ?>
        <span class="ms-2 fs-6 align-middle"><?= status_badge($dl['status']) ?></span>
    </h1>
    <div class="d-flex gap-2">
        <a href="<?= site_url('deliveries') ?>" class="btn btn-secondary">Kembali</a>
        <a href="<?= site_url('sales-orders/' . $dl['sales_order_id']) ?>" class="btn btn-outline-primary">Lihat SO</a>
        <?php if ($canCancelDL): ?>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
            <i class="bi bi-x-circle me-1"></i>Batalkan
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (session('error')): ?>
<div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
<div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Nomor Delivery</div>
                <div class="fw-semibold"><?= esc($dl['delivery_number']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Tanggal Pengiriman</div>
                <div><?= esc(format_date($dl['delivery_date'])) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Nomor SO</div>
                <div><a href="<?= site_url('sales-orders/' . $dl['sales_order_id']) ?>"><?= esc($dl['so_number']) ?></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Customer</div>
                <div><?= esc($dl['customer_name']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Dicatat oleh</div>
                <div><?= esc($dl['created_by_name']) ?></div>
                <div class="small text-muted"><?= esc(format_datetime($dl['created_at'])) ?></div>
            </div>
            <?php if ($dl['cancelled_at'] !== null): ?>
            <div class="col-md-12 col-lg-6">
                <div class="text-muted small">Dibatalkan oleh</div>
                <div><?= esc($dl['cancelled_by_name'] ?? '-') ?>
                    <span class="small text-muted">(<?= esc(format_datetime($dl['cancelled_at'])) ?>)</span>
                </div>
                <div class="small">Alasan: <?= esc($dl['cancel_reason'] ?? '-') ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produk</th>
                    <th>Satuan</th>
                    <th class="text-end">Dipesan (SO)</th>
                    <th class="text-end">Dikirim pada DL ini</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dl['items'] as $it): ?>
                <tr>
                    <td><span class="text-nowrap"><?= esc($it['code']) ?></span> - <?= esc($it['name']) ?></td>
                    <td><?= esc($it['unit']) ?></td>
                    <td class="text-end"><?= number_format($it['ordered'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($it['quantity_delivered'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total dikirim</th>
                    <th class="text-end"><?= number_format($dl['total_qty'], 0, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if ($dl['status'] === Documents::DL_ACTIVE): ?>
<p class="text-muted small mt-3 mb-0">Stok produk di atas sudah berkurang sesuai jumlah dikirim dan tercatat pada
    histori mutasi stok.</p>
<?php elseif ($dl['status'] === Documents::DL_CANCELLED): ?>
<p class="text-muted small mt-3 mb-0">Delivery ini dibatalkan. Stok produk di atas sudah dikembalikan dan tercatat pada
    histori mutasi stok.</p>
<?php endif; ?>

<?php if ($canCancelDL): ?>
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('deliveries/' . $dl['id'] . '/cancel') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="cancelTitle">Batalkan Delivery</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Batalkan Delivery <strong><?= esc($dl['delivery_number']) ?></strong>?</p>
                <p class="text-muted small mb-3">
                    Stok akan <strong>dikembalikan</strong> sebesar kuantitas yang pernah dikirim.
                    Data tidak dihapus, hanya berubah status menjadi <strong>Dibatalkan</strong>.
                </p>
                <label for="dl_cancel_reason" class="form-label">
                    Alasan pembatalan <span class="text-danger">*</span>
                </label>
                <textarea id="dl_cancel_reason" name="cancel_reason" rows="3" maxlength="500" required
                    class="form-control<?= isset($errors['cancel_reason']) ? ' is-invalid' : '' ?>"><?= esc(old('cancel_reason') ?? '') ?></textarea>
                <?php if (isset($errors['cancel_reason'])): ?>
                <div class="invalid-feedback d-block"><?= esc($errors['cancel_reason']) ?></div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                <button type="submit" class="btn btn-danger">Ya, Batalkan Delivery</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>