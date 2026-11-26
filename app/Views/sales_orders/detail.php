<?php
/**
 * UI-20 Detail Sales Order (batal + kirim + edit). Variabel: $so (header + 'items', 'deliveries', 'deliverable'),
 * $canWrite, $canShip, $errors.
 */
$this->extend('layouts/main');

use Config\Documents;

$isOpen      = $so['status'] === Documents::SO_OPEN;
$isCancelled = $so['status'] === Documents::SO_CANCELLED;

$hasActiveDelivery = false;
foreach ($so['deliveries'] as $d) {
    if ($d['status'] === Documents::DL_ACTIVE) { $hasActiveDelivery = true; break; }
}

$canCancelSO = $canWrite && $isOpen && ! $hasActiveDelivery;

// ─── TAMBAHKAN VARIABEL INI UNTUK TOMBOL UBAH ───
$canEditSO   = $canWrite && $isOpen && ! $hasActiveDelivery;
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">
        <?= esc($so['so_number']) ?>
        <span class="ms-2 fs-6 align-middle"><?= status_badge($so['status']) ?></span>
    </h1>
    <div class="d-flex gap-2">
        <a href="<?= site_url('sales-orders') ?>" class="btn btn-secondary">Kembali</a>

        <?php if ($canShip && $so['deliverable']): ?>
        <a href="<?= site_url('deliveries/new/' . $so['id']) ?>" class="btn btn-success">
            <i class="bi bi-truck me-1"></i>Kirim Barang
        </a>
        <?php endif; ?>

        <?php // ─── TOMBOL UBAH SO BARU ─── ?>
        <?php if ($canEditSO): ?>
        <a href="<?= site_url('sales-orders/' . $so['id'] . '/edit') ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square me-1"></i>Ubah SO
        </a>
        <?php endif; ?>

        <?php if ($canCancelSO): ?>
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

<?php if ($canWrite && $isOpen && $hasActiveDelivery): ?>
<div class="alert alert-warning py-2">
    SO ini memiliki Delivery aktif sehingga tidak dapat diubah atau dibatalkan. <br>
    Silakan batalkan Delivery terlebih dahulu melalui halaman detail Delivery jika Anda ingin merevisi pesanan ini.
</div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Nomor SO</div>
                <div class="fw-semibold"><?= esc($so['so_number']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Tanggal</div>
                <div><?= esc(format_date($so['so_date'])) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Customer</div>
                <div><?= esc($so['customer_name']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Status</div>
                <div><?= status_badge($so['status']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Dibuat oleh</div>
                <div><?= esc($so['created_by_name']) ?></div>
                <div class="small text-muted"><?= esc(format_datetime($so['created_at'])) ?></div>
            </div>
            <?php if ($so['cancelled_at'] !== null): ?>
            <div class="col-md-12 col-lg-6">
                <div class="text-muted small">Dibatalkan oleh</div>
                <div><?= esc($so['cancelled_by_name'] ?? '-') ?>
                    <span class="small text-muted">(<?= esc(format_datetime($so['cancelled_at'])) ?>)</span>
                </div>
                <div class="small">Alasan: <?= esc($so['cancel_reason'] ?? '-') ?></div>
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
                    <th class="text-end">Dipesan</th>
                    <th class="text-end">Terkirim</th>
                    <th class="text-end">Sisa</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($so['items'] as $it): ?>
                <tr>
                    <td><span class="text-nowrap"><?= esc($it['code']) ?></span> - <?= esc($it['name']) ?></td>
                    <td><?= esc($it['unit']) ?></td>
                    <td class="text-end"><?= number_format($it['quantity'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($it['delivered'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($it['remaining'], 0, ',', '.') ?></td>
                    <td class="text-end text-nowrap"><?= esc(format_rupiah($it['unit_price'])) ?></td>
                    <td class="text-end text-nowrap"><?= esc(format_rupiah($it['subtotal'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">Total</th>
                    <th class="text-end text-nowrap"><?= esc(format_rupiah($so['total_amount'])) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if ($so['deliveries'] !== []): ?>
<h2 class="h6 mt-4 mb-2">Riwayat Pengiriman Barang</h2>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nomor Delivery</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th class="text-end">Total Dikirim</th>
                    <?php if ($canShip): ?><th class="text-end">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($so['deliveries'] as $dl): ?>
                <tr>
                    <td class="text-nowrap fw-semibold"><?= esc($dl['delivery_number']) ?></td>
                    <td class="text-nowrap"><?= esc(format_date($dl['delivery_date'])) ?></td>
                    <td>
                        <?= status_badge($dl['status']) ?>
                        <?php if ($dl['status'] === Documents::DL_CANCELLED && !empty($dl['cancel_reason'])): ?>
                        <div class="small text-danger mt-1" style="max-width: 250px; white-space: normal;">
                            <i class="bi bi-info-circle me-1"></i><?= esc($dl['cancel_reason']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= number_format($dl['total_qty'], 0, ',', '.') ?></td>
                    <?php if ($canShip): ?>
                    <td class="text-end">
                        <a href="<?= site_url('deliveries/' . $dl['id']) ?>"
                            class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($canCancelSO): ?>
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('sales-orders/' . $so['id'] . '/cancel') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="cancelTitle">Batalkan Sales Order</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Batalkan SO <strong><?= esc($so['so_number']) ?></strong>?</p>
                <p class="text-muted small mb-3">
                    Pembatalan tidak menghapus data. Status berubah menjadi <strong>Dibatalkan</strong>
                    dan tetap tercatat di histori.
                </p>
                <label for="so_cancel_reason" class="form-label">Alasan pembatalan <span
                        class="text-danger">*</span></label>
                <textarea id="so_cancel_reason" name="cancel_reason" rows="3" maxlength="500" required
                    class="form-control<?= isset($errors['cancel_reason']) ? ' is-invalid' : '' ?>"><?= esc(old('cancel_reason') ?? '') ?></textarea>
                <?php if (isset($errors['cancel_reason'])): ?>
                <div class="invalid-feedback d-block"><?= esc($errors['cancel_reason']) ?></div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                <button type="submit" class="btn btn-danger">Ya, Batalkan SO</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>