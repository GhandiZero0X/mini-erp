<?php
/**
 * UI-09 Detail Purchase Order (setujui / batalkan). Variabel: $po (header + 'items', 'receipts', 'receivable'),
 * $canWrite, $canReceive, $errors (flash).
 * Tombol Batalkan: UC-10. Bisa untuk Draft atau Approved, asalkan tidak ada GR aktif.
 */
$this->extend('layouts/main');

use Config\Documents;

$isDraft = $po['status'] === Documents::PO_DRAFT;
$isCancelled = $po['status'] === Documents::PO_CANCELLED;

// ─── BARU: cek apakah masih ada GR aktif (pembatalan PO diblokir)
$hasActiveGR = false;
foreach ($po['receipts'] as $r) {
    if ($r['status'] === Documents::GR_ACTIVE) {
        $hasActiveGR = true;
        break;
    }
}

// ─── BARU: syarat tombol Batalkan
$canCancelPO = $canWrite
    && !$isCancelled
    && in_array($po['status'], [Documents::PO_DRAFT, Documents::PO_APPROVED], true)
    && !$hasActiveGR;
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">
        <?= esc($po['po_number']) ?>
        <span class="ms-2 fs-6 align-middle"><?= status_badge($po['status']) ?></span>
    </h1>
    <div class="d-flex gap-2">
        <a href="<?= site_url('purchase-orders') ?>" class="btn btn-secondary">Kembali</a>
        <?php if ($canReceive && $po['receivable']): ?>
        <a href="<?= site_url('goods-receipts/new/' . $po['id']) ?>" class="btn btn-success">
            <i class="bi bi-box-arrow-in-down me-1"></i>Terima Barang
        </a>
        <?php endif; ?>
        <?php if ($canApprove && $isDraft): ?>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
            <i class="bi bi-check2-circle me-1"></i>Setujui
        </button>
        <?php endif; ?>
        <?php if ($canCancelPO): // ─── BARU ?>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
            <i class="bi bi-x-circle me-1"></i>Batalkan
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($isDraft): ?>
<div class="alert alert-info py-2">
    PO berstatus Draft belum dapat diterima barangnya dan tidak mengubah stok.
</div>
<?php endif; ?>

<?php if ($canWrite && $po['status'] === Documents::PO_APPROVED && $hasActiveGR): // ─── BARU ?>
<div class="alert alert-warning py-2">
    PO ini memiliki Goods Receipt aktif sehingga tidak dapat dibatalkan.
    Batalkan seluruh GR terlebih dahulu melalui halaman detail masing-masing GR.
</div>
<?php endif; ?>

<?php if (session('error')): // ─── BARU: tampilkan pesan error dari service ?>
<div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): // ─── BARU ?>
<div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Nomor PO</div>
                <div class="fw-semibold"><?= esc($po['po_number']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Tanggal</div>
                <div><?= esc(format_date($po['po_date'])) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Supplier</div>
                <div><?= esc($po['supplier_name']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Status</div>
                <div><?= status_badge($po['status']) ?></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Dibuat oleh</div>
                <div><?= esc($po['created_by_name']) ?></div>
                <div class="small text-muted"><?= esc(format_datetime($po['created_at'])) ?></div>
            </div>
            <?php if ($po['approved_at'] !== null): ?>
            <div class="col-md-6 col-lg-3">
                <div class="text-muted small">Disetujui oleh</div>
                <div><?= esc($po['approved_by_name'] ?? '-') ?></div>
                <div class="small text-muted"><?= esc(format_datetime($po['approved_at'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($po['cancelled_at'] !== null): ?>
            <div class="col-md-12 col-lg-6">
                <div class="text-muted small">Dibatalkan oleh</div>
                <div><?= esc($po['cancelled_by_name'] ?? '-') ?>
                    <span class="small text-muted">(<?= esc(format_datetime($po['cancelled_at'])) ?>)</span>
                </div>
                <div class="small">Alasan: <?= esc($po['cancel_reason'] ?? '-') ?></div>
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
                    <th class="text-end">Diterima</th>
                    <th class="text-end">Sisa</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($po['items'] as $it): ?>
                <tr>
                    <td><span class="text-nowrap"><?= esc($it['code']) ?></span> - <?= esc($it['name']) ?></td>
                    <td><?= esc($it['unit']) ?></td>
                    <td class="text-end"><?= number_format($it['quantity'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($it['received'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($it['remaining'], 0, ',', '.') ?></td>
                    <td class="text-end text-nowrap"><?= esc(format_rupiah($it['unit_price'])) ?></td>
                    <td class="text-end text-nowrap"><?= esc(format_rupiah($it['subtotal'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">Total</th>
                    <th class="text-end text-nowrap"><?= esc(format_rupiah($po['total_amount'])) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if ($po['receipts'] !== []): ?>
<h2 class="h6 mt-4 mb-2">Riwayat Penerimaan Barang</h2>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nomor GR</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th class="text-end">Total Diterima</th>
                    <?php if ($canReceive): ?>
                    <th class="text-end">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($po['receipts'] as $gr): ?>
                <tr>
                    <td class="text-nowrap fw-semibold"><?= esc($gr['gr_number']) ?></td>
                    <td class="text-nowrap"><?= esc(format_date($gr['receipt_date'])) ?></td>
                    <td><?= status_badge($gr['status']) ?></td>
                    <td class="text-end"><?= number_format($gr['total_qty'], 0, ',', '.') ?></td>
                    <?php if ($canReceive): ?>
                    <td class="text-end"><a href="<?= site_url('goods-receipts/' . $gr['id']) ?>"
                            class="btn btn-sm btn-outline-primary">Detail</a></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($canApprove && $isDraft): ?>
<!-- Dialog konfirmasi setujui (UC-08 langkah 4) -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('purchase-orders/' . $po['id'] . '/approve') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="approveTitle">Setujui Purchase Order</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                Setujui PO <strong><?= esc($po['po_number']) ?></strong>? PO yang sudah disetujui tidak dapat diedit
                dan dapat diproses penerimaan barangnya.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Setujui</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canCancelPO): // ─── BARU: modal batalkan ?>
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('purchase-orders/' . $po['id'] . '/cancel') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="cancelTitle">Batalkan Purchase Order</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Batalkan PO <strong><?= esc($po['po_number']) ?></strong>?</p>
                <p class="text-muted small mb-3">
                    Pembatalan tidak menghapus data. Status berubah menjadi <strong>Dibatalkan</strong>
                    dan tetap tercatat di histori.
                </p>
                <label for="po_cancel_reason" class="form-label">
                    Alasan pembatalan <span class="text-danger">*</span>
                </label>
                <textarea id="po_cancel_reason" name="cancel_reason" rows="3" maxlength="500" required
                    class="form-control<?= isset($errors['cancel_reason']) ? ' is-invalid' : '' ?>"><?= esc(old('cancel_reason') ?? '') ?></textarea>
                <?php if (isset($errors['cancel_reason'])): ?>
                <div class="invalid-feedback d-block"><?= esc($errors['cancel_reason']) ?></div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                <button type="submit" class="btn btn-danger">Ya, Batalkan PO</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>