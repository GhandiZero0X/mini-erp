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

$hasActiveGR = false;
foreach ($po['receipts'] as $r) {
    if ($r['status'] === Documents::GR_ACTIVE) {
        $hasActiveGR = true;
        break;
    }
}

$canCancelPO = $canWrite
    && !$isCancelled
    && in_array($po['status'], [Documents::PO_DRAFT, Documents::PO_APPROVED], true)
    && !$hasActiveGR;

$isAutoPO = str_starts_with($po['cancel_reason'] ?? '', 'AUTO-PO:');
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "pod-" (Purchase Order Detail) */
.pod-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.pod-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.pod-head>* {
    position: relative;
    z-index: 1;
}

.pod-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.pod-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.pod-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

/* Toolbar aksi */
.pod-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
    background: #fafbff;
}

/* Info grid */
.pod-info {
    padding: 1.25rem 1.5rem;
}

.pod-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem 2rem;
}

.pod-info-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--app-muted);
    margin-bottom: .25rem;
}

.pod-info-value {
    font-weight: 600;
    color: var(--app-ink);
    overflow-wrap: anywhere;
}

.pod-info-sub {
    font-size: .78rem;
    color: var(--app-muted);
    margin-top: .15rem;
}

/* Tabel */
.pod-table td {
    vertical-align: middle;
}

.pod-code {
    display: inline-block;
    padding: .2rem .55rem;
    border-radius: 8px;
    background: #f1f3f9;
    color: #3b4358;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .8rem;
    font-weight: 600;
    white-space: nowrap;
}

.pod-name {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.pod-qty {
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    white-space: nowrap;
}

.pod-qty.is-received {
    color: #0f8a5c;
}

.pod-price,
.pod-subtotal {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.pod-subtotal {
    font-weight: 700;
}

/* Total footer */
.pod-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: #fff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

.pod-footer-label {
    font-size: .95rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.pod-footer-total {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

/* Section title */
.pod-section-title {
    font-size: .95rem;
    font-weight: 800;
    color: var(--app-ink);
    margin: 1.5rem 0 .75rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}

/* Alert custom */
.pod-alert {
    display: flex;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    font-size: .86rem;
    margin-bottom: 1rem;
}

.pod-alert i {
    flex: 0 0 auto;
    margin-top: .1rem;
}

.pod-alert.is-info {
    background: #eef0ff;
    color: #3b3f8f;
    border: 1px solid #d6d9f5;
}

.pod-alert.is-warn {
    background: #fff4d6;
    color: #946500;
    border: 1px solid #fde8b0;
}

.pod-alert.is-auto {
    background: linear-gradient(135deg, #fff4d6, #ffedd5);
    color: #92400e;
    border: 1px solid #fcd34d;
}

.pod-alert.is-auto code {
    background: rgba(0, 0, 0, .06);
    padding: .1rem .35rem;
    border-radius: 4px;
    font-size: .78rem;
}

/* Modal */
.pod-del-ico {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 18px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 1.5rem;
}

.pod-approve-ico {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 18px;
    background: #e3f8ef;
    color: #0f8a5c;
    font-size: 1.5rem;
}

@media (max-width: 767.98px) {
    .pod-head {
        padding: 1.1rem;
    }

    .pod-footer {
        flex-direction: column;
        gap: .5rem;
        text-align: center;
    }

    .pod-info-grid {
        grid-template-columns: 1fr;
    }

    .pod-actions .btn {
        flex: 1 1 auto;
    }
}
</style>

<!-- Header -->
<div class="pod-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="pod-head-ico" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
        <div>
            <h2><?= esc($po['po_number']) ?></h2>
            <p><?= esc($po['supplier_name']) ?> &middot; <?= esc(format_date($po['po_date'])) ?></p>
        </div>
    </div>
    <div><?= status_badge($po['status']) ?></div>
</div>

<div class="mb-3"></div>

<?php if (session('error')): ?>
<div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-circle me-1"></i><?= esc(session('error')) ?>
</div>
<?php endif; ?>
<?php if (session('success')): ?>
<div class="alert alert-success py-2 mb-3"><i class="bi bi-check-circle me-1"></i><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if ($isAutoPO): ?>
<div class="pod-alert is-auto">
    <i class="bi bi-robot"></i>
    <div>
        <strong>Sistem (Auto-PO):</strong> PO ini dibuat otomatis karena ada Sales Order yang kekurangan stok.
        Silakan periksa Supplier, kuantitas, lalu setujui (Approve) jika sudah sesuai.
        <div class="mt-1"><code><?= esc($po['cancel_reason']) ?></code></div>
    </div>
</div>
<?php endif; ?>

<?php if ($isDraft): ?>
<div class="pod-alert is-info">
    <i class="bi bi-info-circle"></i>
    <div>PO berstatus Draft belum dapat diterima barangnya dan tidak mengubah stok.</div>
</div>
<?php endif; ?>

<?php if ($canWrite && $po['status'] === Documents::PO_APPROVED && $hasActiveGR): ?>
<div class="pod-alert is-warn">
    <i class="bi bi-exclamation-triangle"></i>
    <div>
        PO ini memiliki Goods Receipt aktif sehingga tidak dapat dibatalkan.
        Batalkan seluruh GR terlebih dahulu melalui halaman detail masing-masing GR.
    </div>
</div>
<?php endif; ?>

<div class="card overflow-hidden mb-3">
    <!-- Toolbar aksi -->
    <div class="pod-actions">
        <a href="<?= site_url('purchase-orders') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>

        <?php if ($canReceive && $po['receivable']): ?>
        <a href="<?= site_url('goods-receipts/new/' . $po['id']) ?>" class="btn btn-success">
            <i class="bi bi-box-arrow-in-down me-1"></i>Terima Barang
        </a>
        <?php endif; ?>

        <?php if ($canWrite && $isDraft): ?>
        <a href="<?= site_url('purchase-orders/' . $po['id'] . '/edit') ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square me-1"></i>Ubah
        </a>
        <?php endif; ?>

        <?php if ($canApprove && $isDraft): ?>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
            <i class="bi bi-check2-circle me-1"></i>Setujui
        </button>
        <?php endif; ?>

        <?php if ($canCancelPO): ?>
        <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal"
            data-bs-target="#cancelModal">
            <i class="bi bi-x-circle me-1"></i>Batalkan
        </button>
        <?php endif; ?>
    </div>

    <!-- Info grid -->
    <div class="pod-info">
        <div class="pod-info-grid">
            <div>
                <div class="pod-info-label">Nomor PO</div>
                <div class="pod-info-value"><?= esc($po['po_number']) ?></div>
            </div>
            <div>
                <div class="pod-info-label">Tanggal</div>
                <div class="pod-info-value"><?= esc(format_date($po['po_date'])) ?></div>
            </div>
            <div>
                <div class="pod-info-label">Supplier</div>
                <div class="pod-info-value"><?= esc($po['supplier_name']) ?></div>
            </div>
            <div>
                <div class="pod-info-label">Status</div>
                <div class="pod-info-value"><?= status_badge($po['status']) ?></div>
            </div>
            <div>
                <div class="pod-info-label">Dibuat oleh</div>
                <div class="pod-info-value"><?= esc($po['created_by_name']) ?></div>
                <div class="pod-info-sub"><?= esc(format_datetime($po['created_at'])) ?></div>
            </div>

            <?php if ($po['approved_at'] !== null): ?>
            <div>
                <div class="pod-info-label">Disetujui oleh</div>
                <div class="pod-info-value"><?= esc($po['approved_by_name'] ?? '-') ?></div>
                <div class="pod-info-sub"><?= esc(format_datetime($po['approved_at'])) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($po['cancelled_at'] !== null): ?>
            <div style="grid-column: 1 / -1;">
                <div class="pod-info-label">Dibatalkan oleh</div>
                <div class="pod-info-value">
                    <?= esc($po['cancelled_by_name'] ?? '-') ?>
                    <span class="pod-info-sub">(<?= esc(format_datetime($po['cancelled_at'])) ?>)</span>
                </div>
                <div class="pod-info-sub">Alasan: <?= esc($po['cancel_reason'] ?? '-') ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail Item -->
<h2 class="pod-section-title"><i class="bi bi-list-check text-primary"></i> Detail Produk</h2>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0 pod-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th style="width: 90px;">Satuan</th>
                    <th class="text-end" style="width: 110px;">Dipesan</th>
                    <th class="text-end" style="width: 110px;">Diterima</th>
                    <th class="text-end" style="width: 110px;">Sisa</th>
                    <th class="text-end" style="width: 150px;">Harga Satuan</th>
                    <th class="text-end" style="width: 160px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($po['items'] as $it): ?>
                <tr>
                    <td>
                        <span class="pod-code"><?= esc($it['code']) ?></span>
                        <span class="pod-name ms-1"><?= esc($it['name']) ?></span>
                    </td>
                    <td class="text-muted"><?= esc($it['unit']) ?></td>
                    <td class="text-end"><span class="pod-qty"><?= number_format($it['quantity'], 0, ',', '.') ?></span>
                    </td>
                    <td class="text-end"><span
                            class="pod-qty is-received"><?= number_format($it['received'], 0, ',', '.') ?></span></td>
                    <td class="text-end"><span
                            class="pod-qty"><?= number_format($it['remaining'], 0, ',', '.') ?></span></td>
                    <td class="text-end"><span class="pod-price"><?= esc(format_rupiah($it['unit_price'])) ?></span>
                    </td>
                    <td class="text-end"><span class="pod-subtotal"><?= esc(format_rupiah($it['subtotal'])) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="pod-footer">
        <span class="pod-footer-label">Total</span>
        <span class="pod-footer-total"><?= esc(format_rupiah($po['total_amount'])) ?></span>
    </div>
</div>

<?php if ($po['receipts'] !== []): ?>
<h2 class="pod-section-title"><i class="bi bi-box-arrow-in-down text-primary"></i> Riwayat Penerimaan Barang</h2>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 pod-table">
            <thead>
                <tr>
                    <th style="width: 160px;">Nomor GR</th>
                    <th style="width: 130px;">Tanggal</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 130px;">Total Diterima</th>
                    <?php if ($canReceive): ?><th class="text-end" style="width: 100px;">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($po['receipts'] as $gr): ?>
                <tr>
                    <td><span class="pod-code"><?= esc($gr['gr_number']) ?></span></td>
                    <td><span class="text-muted small"><?= esc(format_date($gr['receipt_date'])) ?></span></td>
                    <td><?= status_badge($gr['status']) ?></td>
                    <td class="text-end"><span
                            class="pod-qty"><?= number_format($gr['total_qty'], 0, ',', '.') ?></span></td>
                    <?php if ($canReceive): ?>
                    <td class="text-end">
                        <a href="<?= site_url('goods-receipts/' . $gr['id']) ?>" class="po-act is-detail"
                            title="Lihat Detail GR" aria-label="Detail <?= esc($gr['gr_number'], 'attr') ?>">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($canApprove && $isDraft): ?>
<!-- Modal Setujui -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form method="post" action="<?= site_url('purchase-orders/' . $po['id'] . '/approve') ?>"
            class="modal-content border-0" style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="pod-approve-ico"><i class="bi bi-check2-circle"></i></div>
                <h5 class="fw-bold mb-2" id="approveTitle">Setujui Purchase Order?</h5>
                <p class="mb-3 text-muted small">
                    PO <strong class="text-dark"><?= esc($po['po_number']) ?></strong> yang sudah disetujui
                    <strong>tidak dapat diedit</strong> dan siap diproses penerimaan barangnya.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill m-0"
                    data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success flex-fill m-0">
                    <i class="bi bi-check2 me-1"></i>Setujui
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canCancelPO): ?>
<!-- Modal Batalkan -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form method="post" action="<?= site_url('purchase-orders/' . $po['id'] . '/cancel') ?>"
            class="modal-content border-0" style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="pod-del-ico"><i class="bi bi-x-circle"></i></div>
                <h5 class="fw-bold mb-2" id="cancelTitle">Batalkan Purchase Order?</h5>
                <p class="mb-3 text-muted small">
                    PO <strong class="text-dark"><?= esc($po['po_number']) ?></strong> akan berubah status menjadi
                    <strong>Dibatalkan</strong> dan tetap tercatat di histori.
                </p>

                <div class="text-start">
                    <label for="po_cancel_reason" class="form-label fw-semibold small">
                        Alasan pembatalan <span class="text-danger">*</span>
                    </label>
                    <textarea id="po_cancel_reason" name="cancel_reason" rows="3" maxlength="500" required
                        class="form-control<?= isset($errors['cancel_reason']) ? ' is-invalid' : '' ?>"
                        placeholder="Masukkan alasan pembatalan..."><?= esc(old('cancel_reason') ?? '') ?></textarea>
                    <?php if (isset($errors['cancel_reason'])): ?>
                    <div class="invalid-feedback d-block"><?= esc($errors['cancel_reason']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill m-0"
                    data-bs-dismiss="modal">Tidak</button>
                <button type="submit" class="btn btn-danger flex-fill m-0">Ya, Batalkan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>