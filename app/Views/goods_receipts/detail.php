<?php
/**
 * UI-21 Detail Goods Receipt. Variabel: $gr (header + 'items', 'total_qty'), $errors (flash).
 * Tombol Batalkan: UC-10. Hanya untuk GR Aktif; stok akan dikurangi saat pembatalan.
 */
$this->extend('layouts/main');

use Config\Documents;

// ─── BARU: syarat tombol Batalkan GR
$canCancelGR = $gr['status'] === Documents::GR_ACTIVE && auth_can('goods_receipt', 'W');
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">
        <?= esc($gr['gr_number']) ?>
        <span class="ms-2 fs-6 align-middle"><?= status_badge($gr['status']) ?></span>
    </h1>
    <div class="d-flex gap-2">
        <a href="<?= site_url('goods-receipts') ?>" class="btn btn-secondary">Kembali</a>
        <a href="<?= site_url('purchase-orders/' . $gr['purchase_order_id']) ?>" class="btn btn-outline-primary">Lihat
            PO</a>
        <?php if ($canCancelGR): // ─── BARU ?>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
            <i class="bi bi-x-circle me-1"></i>Batalkan
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (session('error')): // ─── BARU: pesan dari service (mis. stok tidak cukup) ?>
<div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): // ─── BARU ?>
<div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Nomor GR</div>
                <div class="fw-semibold"><?= esc($gr['gr_number']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Tanggal Penerimaan</div>
                <div><?= esc(format_date($gr['receipt_date'])) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Nomor PO</div>
                <div><a
                        href="<?= site_url('purchase-orders/' . $gr['purchase_order_id']) ?>"><?= esc($gr['po_number']) ?></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Supplier</div>
                <div><?= esc($gr['supplier_name']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Dicatat oleh</div>
                <div><?= esc($gr['created_by_name']) ?></div>
                <div class="small text-muted"><?= esc(format_datetime($gr['created_at'])) ?></div>
            </div>
            <?php if ($gr['cancelled_at'] !== null): ?>
            <div class="col-md-12 col-lg-6">
                <div class="text-muted small">Dibatalkan oleh</div>
                <div><?= esc($gr['cancelled_by_name'] ?? '-') ?>
                    <span class="small text-muted">(<?= esc(format_datetime($gr['cancelled_at'])) ?>)</span>
                </div>
                <div class="small">Alasan: <?= esc($gr['cancel_reason'] ?? '-') ?></div>
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
                    <th class="text-end">Dipesan (PO)</th>
                    <th class="text-end">Diterima pada GR ini</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gr['items'] as $it): ?>
                <tr>
                    <td><span class="text-nowrap"><?= esc($it['code']) ?></span> - <?= esc($it['name']) ?></td>
                    <td><?= esc($it['unit']) ?></td>
                    <td class="text-end"><?= number_format($it['ordered'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($it['quantity_received'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total diterima</th>
                    <th class="text-end"><?= number_format($gr['total_qty'], 0, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if ($gr['status'] === Documents::GR_ACTIVE): ?>
<p class="text-muted small mt-3 mb-0">Stok produk di atas sudah bertambah sesuai jumlah diterima dan tercatat pada
    histori mutasi stok.</p>
<?php elseif ($gr['status'] === Documents::GR_CANCELLED): // ─── BARU ?>
<p class="text-muted small mt-3 mb-0">GR ini dibatalkan. Stok produk di atas sudah dikurangi kembali dan tercatat pada
    histori mutasi stok.</p>
<?php endif; ?>

<?php if ($canCancelGR): // ─── BARU: modal batalkan GR ?>
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('goods-receipts/' . $gr['id'] . '/cancel') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="cancelTitle">Batalkan Goods Receipt</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Batalkan GR <strong><?= esc($gr['gr_number']) ?></strong>?</p>
                <p class="text-muted small mb-3">
                    Stok akan <strong>dikurangi</strong> sebesar kuantitas yang pernah diterima. Pembatalan
                    akan <strong>ditolak</strong> bila stok saat ini tidak mencukupi (misalnya barang sudah
                    terjual lewat Delivery).
                </p>
                <label for="gr_cancel_reason" class="form-label">
                    Alasan pembatalan <span class="text-danger">*</span>
                </label>
                <textarea id="gr_cancel_reason" name="cancel_reason" rows="3" maxlength="500" required
                    class="form-control<?= isset($errors['cancel_reason']) ? ' is-invalid' : '' ?>"><?= esc(old('cancel_reason') ?? '') ?></textarea>
                <?php if (isset($errors['cancel_reason'])): ?>
                <div class="invalid-feedback d-block"><?= esc($errors['cancel_reason']) ?></div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                <button type="submit" class="btn btn-danger">Ya, Batalkan GR</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>