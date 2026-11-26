<?php
/**
 * UI-21 Detail Goods Receipt. Variabel: $gr (header + 'items', 'total_qty'), $errors (flash).
 * Tombol Batalkan: UC-10. Hanya untuk GR Aktif; stok akan dikurangi saat pembatalan.
 */
$this->extend('layouts/main');

use Config\Documents;

$canCancelGR = $gr['status'] === Documents::GR_ACTIVE && auth_can('goods_receipt', 'W');
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "grd-" (Goods Receipt Detail) */
.grd-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.grd-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.grd-head>* {
    position: relative;
    z-index: 1;
}

.grd-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.grd-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.grd-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

/* Toolbar aksi */
.grd-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
    background: #fafbff;
}

/* Info grid */
.grd-info {
    padding: 1.25rem 1.5rem;
}

.grd-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem 2rem;
}

.grd-info-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--app-muted);
    margin-bottom: .25rem;
}

.grd-info-value {
    font-weight: 600;
    color: var(--app-ink);
    overflow-wrap: anywhere;
}

.grd-info-sub {
    font-size: .78rem;
    color: var(--app-muted);
    margin-top: .15rem;
}

.grd-info-link {
    color: #4338ca;
    font-weight: 700;
    text-decoration: none;
}

.grd-info-link:hover {
    text-decoration: underline;
}

/* Tabel */
.grd-table td {
    vertical-align: middle;
}

.grd-code {
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

.grd-name {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.grd-qty {
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    white-space: nowrap;
}

.grd-qty.is-received {
    color: #0f8a5c;
}

/* Total footer */
.grd-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: #fff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

.grd-footer-label {
    font-size: .95rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.grd-footer-total {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f8a5c;
    font-variant-numeric: tabular-nums;
}

/* Alert status note */
.grd-note {
    display: flex;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    font-size: .86rem;
    margin-top: 1rem;
}

.grd-note i {
    flex: 0 0 auto;
    margin-top: .1rem;
}

.grd-note.is-info {
    background: #e3f8ef;
    color: #0f8a5c;
    border: 1px solid #a9e5c9;
}

.grd-note.is-muted {
    background: #f1f3f9;
    color: #475069;
    border: 1px solid #dfe3ee;
}

/* Modal */
.grd-del-ico {
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

@media (max-width: 767.98px) {
    .grd-head {
        padding: 1.1rem;
    }

    .grd-footer {
        flex-direction: column;
        gap: .5rem;
        text-align: center;
    }

    .grd-info-grid {
        grid-template-columns: 1fr;
    }

    .grd-actions .btn {
        flex: 1 1 auto;
    }
}
</style>

<!-- Header -->
<div class="grd-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="grd-head-ico" aria-hidden="true"><i class="bi bi-box-arrow-in-down"></i></span>
        <div>
            <h2><?= esc($gr['gr_number']) ?></h2>
            <p><?= esc($gr['supplier_name']) ?> &middot; <?= esc(format_date($gr['receipt_date'])) ?></p>
        </div>
    </div>
    <div><?= status_badge($gr['status']) ?></div>
</div>

<div class="mb-3"></div>

<?php if (session('error')): ?>
<div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-circle me-1"></i><?= esc(session('error')) ?>
</div>
<?php endif; ?>
<?php if (session('success')): ?>
<div class="alert alert-success py-2 mb-3"><i class="bi bi-check-circle me-1"></i><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="card overflow-hidden mb-3">
    <!-- Toolbar aksi -->
    <div class="grd-actions">
        <a href="<?= site_url('goods-receipts') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <a href="<?= site_url('purchase-orders/' . $gr['purchase_order_id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-text me-1"></i>Lihat PO
        </a>

        <?php if ($canCancelGR): ?>
        <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal"
            data-bs-target="#cancelModal">
            <i class="bi bi-x-circle me-1"></i>Batalkan
        </button>
        <?php endif; ?>
    </div>

    <!-- Info grid -->
    <div class="grd-info">
        <div class="grd-info-grid">
            <div>
                <div class="grd-info-label">Nomor GR</div>
                <div class="grd-info-value"><?= esc($gr['gr_number']) ?></div>
            </div>
            <div>
                <div class="grd-info-label">Tanggal Penerimaan</div>
                <div class="grd-info-value"><?= esc(format_date($gr['receipt_date'])) ?></div>
            </div>
            <div>
                <div class="grd-info-label">Nomor PO</div>
                <div class="grd-info-value">
                    <a href="<?= site_url('purchase-orders/' . $gr['purchase_order_id']) ?>" class="grd-info-link">
                        <?= esc($gr['po_number']) ?>
                    </a>
                </div>
            </div>
            <div>
                <div class="grd-info-label">Supplier</div>
                <div class="grd-info-value"><?= esc($gr['supplier_name']) ?></div>
            </div>
            <div>
                <div class="grd-info-label">Dicatat oleh</div>
                <div class="grd-info-value"><?= esc($gr['created_by_name']) ?></div>
                <div class="grd-info-sub"><?= esc(format_datetime($gr['created_at'])) ?></div>
            </div>

            <?php if ($gr['cancelled_at'] !== null): ?>
            <div style="grid-column: 1 / -1;">
                <div class="grd-info-label">Dibatalkan oleh</div>
                <div class="grd-info-value">
                    <?= esc($gr['cancelled_by_name'] ?? '-') ?>
                    <span class="grd-info-sub">(<?= esc(format_datetime($gr['cancelled_at'])) ?>)</span>
                </div>
                <div class="grd-info-sub">Alasan: <?= esc($gr['cancel_reason'] ?? '-') ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail Barang -->
<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0 grd-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th style="width: 90px;">Satuan</th>
                    <th class="text-end" style="width: 140px;">Dipesan (PO)</th>
                    <th class="text-end" style="width: 160px;">Diterima pada GR ini</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gr['items'] as $it): ?>
                <tr>
                    <td>
                        <span class="grd-code"><?= esc($it['code']) ?></span>
                        <span class="grd-name ms-1"><?= esc($it['name']) ?></span>
                    </td>
                    <td class="text-muted"><?= esc($it['unit']) ?></td>
                    <td class="text-end"><span class="grd-qty"><?= number_format($it['ordered'], 0, ',', '.') ?></span>
                    </td>
                    <td class="text-end"><span
                            class="grd-qty is-received"><?= number_format($it['quantity_received'], 0, ',', '.') ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="grd-footer">
        <span class="grd-footer-label">Total Diterima</span>
        <span class="grd-footer-total"><?= number_format($gr['total_qty'], 0, ',', '.') ?></span>
    </div>
</div>

<!-- Catatan Status -->
<?php if ($gr['status'] === Documents::GR_ACTIVE): ?>
<div class="grd-note is-info">
    <i class="bi bi-info-circle"></i>
    <div>Stok produk di atas sudah <strong>bertambah</strong> sesuai jumlah diterima dan tercatat pada histori mutasi
        stok.</div>
</div>
<?php elseif ($gr['status'] === Documents::GR_CANCELLED): ?>
<div class="grd-note is-muted">
    <i class="bi bi-x-circle"></i>
    <div>GR ini dibatalkan. Stok produk di atas sudah <strong>dikurangi kembali</strong> dan tercatat pada histori
        mutasi stok.</div>
</div>
<?php endif; ?>

<?php if ($canCancelGR): ?>
<!-- Modal Batalkan -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form method="post" action="<?= site_url('goods-receipts/' . $gr['id'] . '/cancel') ?>"
            class="modal-content border-0" style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="grd-del-ico"><i class="bi bi-x-circle"></i></div>
                <h5 class="fw-bold mb-2" id="cancelTitle">Batalkan Goods Receipt?</h5>
                <p class="mb-3 text-muted small">
                    GR <strong class="text-dark"><?= esc($gr['gr_number']) ?></strong> akan berubah status menjadi
                    <strong>Dibatalkan</strong>.<br>
                    Stok akan <strong>dikurangi</strong> sebesar kuantitas yang pernah diterima.
                    Pembatalan akan <strong>ditolak</strong> bila stok saat ini tidak mencukupi.
                </p>

                <div class="text-start">
                    <label for="gr_cancel_reason" class="form-label fw-semibold small">
                        Alasan pembatalan <span class="text-danger">*</span>
                    </label>
                    <textarea id="gr_cancel_reason" name="cancel_reason" rows="3" maxlength="500" required
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