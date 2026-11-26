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
$canEditSO   = $canWrite && $isOpen && ! $hasActiveDelivery;
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "sod-" (SO Detail) */
.sod-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.sod-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.sod-head>* {
    position: relative;
    z-index: 1;
}

.sod-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.sod-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.sod-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

/* Toolbar aksi */
.sod-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
    background: #fafbff;
}

/* Info grid */
.sod-info {
    padding: 1.25rem 1.5rem;
}

.sod-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem 2rem;
}

.sod-info-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--app-muted);
    margin-bottom: .25rem;
}

.sod-info-value {
    font-weight: 600;
    color: var(--app-ink);
    overflow-wrap: anywhere;
}

.sod-info-sub {
    font-size: .78rem;
    color: var(--app-muted);
    margin-top: .15rem;
}

/* Tabel */
.sod-table td {
    vertical-align: middle;
}

.sod-code {
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

.sod-name {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.sod-qty {
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    white-space: nowrap;
}

.sod-qty.is-delivered {
    color: #0f8a5c;
}

.sod-qty.is-remaining {
    color: var(--app-ink);
}

.sod-price,
.sod-subtotal {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.sod-subtotal {
    font-weight: 700;
}

/* Total footer */
.sod-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: #fff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

.sod-footer-label {
    font-size: .95rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.sod-footer-total {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

/* Section title */
.sod-section-title {
    font-size: .95rem;
    font-weight: 800;
    color: var(--app-ink);
    margin: 1.5rem 0 .75rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}

/* Modal hapus */
.sod-del-ico {
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

/* Alert custom */
.sod-alert-warn {
    display: flex;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    background: #fff4d6;
    color: #946500;
    font-size: .86rem;
    border: 1px solid #fde8b0;
}

.sod-alert-warn i {
    flex: 0 0 auto;
    margin-top: .1rem;
}

@media (max-width: 767.98px) {
    .sod-head {
        padding: 1.1rem;
    }

    .sod-footer {
        flex-direction: column;
        gap: .5rem;
        text-align: center;
    }

    .sod-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Header -->
<div class="sod-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="sod-head-ico" aria-hidden="true"><i class="bi bi-receipt"></i></span>
        <div>
            <h2><?= esc($so['so_number']) ?></h2>
            <p><?= esc($so['customer_name']) ?> &middot; <?= esc(format_date($so['so_date'])) ?></p>
        </div>
    </div>
    <div><?= status_badge($so['status']) ?></div>
</div>

<div class="mb-3"></div>

<?php if (session('error')): ?>
<div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-circle me-1"></i><?= esc(session('error')) ?>
</div>
<?php endif; ?>
<?php if (session('success')): ?>
<div class="alert alert-success py-2 mb-3"><i class="bi bi-check-circle me-1"></i><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if ($canWrite && $isOpen && $hasActiveDelivery): ?>
<div class="sod-alert-warn mb-3">
    <i class="bi bi-info-circle"></i>
    <div>
        SO ini memiliki Delivery aktif sehingga tidak dapat diubah atau dibatalkan.<br>
        Silakan batalkan Delivery terlebih dahulu melalui halaman detail Delivery jika Anda ingin merevisi pesanan ini.
    </div>
</div>
<?php endif; ?>

<div class="card overflow-hidden mb-3">
    <!-- Toolbar aksi -->
    <div class="sod-actions">
        <a href="<?= site_url('sales-orders') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>

        <?php if ($canShip && $so['deliverable']): ?>
        <a href="<?= site_url('deliveries/new/' . $so['id']) ?>" class="btn btn-success">
            <i class="bi bi-truck me-1"></i>Kirim Barang
        </a>
        <?php endif; ?>

        <?php if ($canEditSO): ?>
        <a href="<?= site_url('sales-orders/' . $so['id'] . '/edit') ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square me-1"></i>Ubah SO
        </a>
        <?php endif; ?>

        <?php if ($canCancelSO): ?>
        <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal"
            data-bs-target="#cancelModal">
            <i class="bi bi-x-circle me-1"></i>Batalkan
        </button>
        <?php endif; ?>
    </div>

    <!-- Info grid -->
    <div class="sod-info">
        <div class="sod-info-grid">
            <div>
                <div class="sod-info-label">Nomor SO</div>
                <div class="sod-info-value"><?= esc($so['so_number']) ?></div>
            </div>
            <div>
                <div class="sod-info-label">Tanggal</div>
                <div class="sod-info-value"><?= esc(format_date($so['so_date'])) ?></div>
            </div>
            <div>
                <div class="sod-info-label">Customer</div>
                <div class="sod-info-value"><?= esc($so['customer_name']) ?></div>
            </div>
            <div>
                <div class="sod-info-label">Status</div>
                <div class="sod-info-value"><?= status_badge($so['status']) ?></div>
            </div>
            <div>
                <div class="sod-info-label">Dibuat oleh</div>
                <div class="sod-info-value"><?= esc($so['created_by_name']) ?></div>
                <div class="sod-info-sub"><?= esc(format_datetime($so['created_at'])) ?></div>
            </div>

            <?php if ($so['cancelled_at'] !== null): ?>
            <div style="grid-column: 1 / -1;">
                <div class="sod-info-label">Dibatalkan oleh</div>
                <div class="sod-info-value">
                    <?= esc($so['cancelled_by_name'] ?? '-') ?>
                    <span class="sod-info-sub">(<?= esc(format_datetime($so['cancelled_at'])) ?>)</span>
                </div>
                <div class="sod-info-sub">Alasan: <?= esc($so['cancel_reason'] ?? '-') ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail Item -->
<h2 class="sod-section-title"><i class="bi bi-list-check text-primary"></i> Detail Produk</h2>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0 sod-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th style="width: 90px;">Satuan</th>
                    <th class="text-end" style="width: 110px;">Dipesan</th>
                    <th class="text-end" style="width: 110px;">Terkirim</th>
                    <th class="text-end" style="width: 110px;">Sisa</th>
                    <th class="text-end" style="width: 150px;">Harga Satuan</th>
                    <th class="text-end" style="width: 160px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($so['items'] as $it): ?>
                <tr>
                    <td>
                        <span class="sod-code"><?= esc($it['code']) ?></span>
                        <span class="sod-name ms-1"><?= esc($it['name']) ?></span>
                    </td>
                    <td class="text-muted"><?= esc($it['unit']) ?></td>
                    <td class="text-end"><span class="sod-qty"><?= number_format($it['quantity'], 0, ',', '.') ?></span>
                    </td>
                    <td class="text-end"><span
                            class="sod-qty is-delivered"><?= number_format($it['delivered'], 0, ',', '.') ?></span></td>
                    <td class="text-end"><span
                            class="sod-qty is-remaining"><?= number_format($it['remaining'], 0, ',', '.') ?></span></td>
                    <td class="text-end"><span class="sod-price"><?= esc(format_rupiah($it['unit_price'])) ?></span>
                    </td>
                    <td class="text-end"><span class="sod-subtotal"><?= esc(format_rupiah($it['subtotal'])) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="sod-footer">
        <span class="sod-footer-label">Total</span>
        <span class="sod-footer-total"><?= esc(format_rupiah($so['total_amount'])) ?></span>
    </div>
</div>

<?php if ($so['deliveries'] !== []): ?>
<h2 class="sod-section-title"><i class="bi bi-truck text-primary"></i> Riwayat Pengiriman</h2>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sod-table">
            <thead>
                <tr>
                    <th style="width: 160px;">Nomor Delivery</th>
                    <th style="width: 130px;">Tanggal</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 130px;">Total Dikirim</th>
                    <?php if ($canShip): ?><th class="text-end" style="width: 100px;">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($so['deliveries'] as $dl): ?>
                <tr>
                    <td><span class="sod-code"><?= esc($dl['delivery_number']) ?></span></td>
                    <td><span class="text-muted small"><?= esc(format_date($dl['delivery_date'])) ?></span></td>
                    <td>
                        <?= status_badge($dl['status']) ?>
                        <?php if ($dl['status'] === Documents::DL_CANCELLED && !empty($dl['cancel_reason'])): ?>
                        <div class="small text-danger mt-1" style="max-width: 250px; white-space: normal;">
                            <i class="bi bi-info-circle me-1"></i><?= esc($dl['cancel_reason']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><span
                            class="sod-qty"><?= number_format($dl['total_qty'], 0, ',', '.') ?></span></td>
                    <?php if ($canShip): ?>
                    <td class="text-end">
                        <a href="<?= site_url('deliveries/' . $dl['id']) ?>" class="so-act is-detail"
                            title="Lihat Detail Delivery"
                            aria-label="Detail <?= esc($dl['delivery_number'], 'attr') ?>">
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

<?php if ($canCancelSO): ?>
<!-- Modal Batalkan -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form method="post" action="<?= site_url('sales-orders/' . $so['id'] . '/cancel') ?>"
            class="modal-content border-0" style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="sod-del-ico"><i class="bi bi-x-circle"></i></div>
                <h5 class="fw-bold mb-2" id="cancelTitle">Batalkan Sales Order?</h5>
                <p class="mb-3 text-muted small">
                    SO <strong class="text-dark"><?= esc($so['so_number']) ?></strong> akan berubah status menjadi
                    <strong>Dibatalkan</strong> dan tetap tercatat di histori.
                </p>

                <div class="text-start">
                    <label for="so_cancel_reason" class="form-label fw-semibold small">
                        Alasan pembatalan <span class="text-danger">*</span>
                    </label>
                    <textarea id="so_cancel_reason" name="cancel_reason" rows="3" maxlength="500" required
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