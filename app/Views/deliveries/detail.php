<?php
/**
 * UI-22 Detail Delivery (batal & aktifkan). Variabel: $dl (header + 'items', 'total_qty'), $errors.
 */
$this->extend('layouts/main');

use Config\Documents;

$canCancelDL = in_array($dl['status'], [Documents::DL_ACTIVE, Documents::DL_SCHEDULED], true) && auth_can('delivery', 'W');
$canActivateDL = $dl['status'] === Documents::DL_SCHEDULED && auth_can('delivery', 'W');
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "dld-" (Delivery Detail) */
.dld-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.dld-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.dld-head>* {
    position: relative;
    z-index: 1;
}

.dld-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.dld-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.dld-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

/* Toolbar aksi */
.dld-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
    background: #fafbff;
}

/* Info grid */
.dld-info {
    padding: 1.25rem 1.5rem;
}

.dld-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem 2rem;
}

.dld-info-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--app-muted);
    margin-bottom: .25rem;
}

.dld-info-value {
    font-weight: 600;
    color: var(--app-ink);
    overflow-wrap: anywhere;
}

.dld-info-sub {
    font-size: .78rem;
    color: var(--app-muted);
    margin-top: .15rem;
}

.dld-info-link {
    color: #4338ca;
    font-weight: 700;
    text-decoration: none;
}

.dld-info-link:hover {
    text-decoration: underline;
}

/* Tabel */
.dld-table td {
    vertical-align: middle;
}

.dld-code {
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

.dld-name {
    font-weight: 600;
    overflow-wrap: anywhere;
}

.dld-qty {
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    white-space: nowrap;
}

.dld-qty.is-shipped {
    color: #0f8a5c;
}

/* Total footer */
.dld-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: #fff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

.dld-footer-label {
    font-size: .95rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.dld-footer-total {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

/* Alert status note */
.dld-note {
    display: flex;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    font-size: .86rem;
    margin-top: 1rem;
}

.dld-note i {
    flex: 0 0 auto;
    margin-top: .1rem;
}

.dld-note.is-info {
    background: #eef0ff;
    color: #3b3f8f;
    border: 1px solid #d6d9f5;
}

.dld-note.is-warn {
    background: #fff4d6;
    color: #946500;
    border: 1px solid #fde8b0;
}

.dld-note.is-muted {
    background: #f1f3f9;
    color: #475069;
    border: 1px solid #dfe3ee;
}

/* Modal */
.dld-del-ico {
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
    .dld-head {
        padding: 1.1rem;
    }

    .dld-footer {
        flex-direction: column;
        gap: .5rem;
        text-align: center;
    }

    .dld-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Header -->
<div class="dld-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="dld-head-ico" aria-hidden="true"><i class="bi bi-truck"></i></span>
        <div>
            <h2><?= esc($dl['delivery_number']) ?></h2>
            <p><?= esc($dl['customer_name']) ?> &middot; <?= esc(format_date($dl['delivery_date'])) ?></p>
        </div>
    </div>
    <div><?= status_badge($dl['status']) ?></div>
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
    <div class="dld-actions">
        <a href="<?= site_url('deliveries') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <a href="<?= site_url('sales-orders/' . $dl['sales_order_id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-receipt me-1"></i>Lihat SO
        </a>

        <?php if ($canActivateDL): ?>
        <form method="post" action="<?= site_url('deliveries/' . $dl['id'] . '/activate') ?>" class="d-inline m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary"
                onclick="return confirm('Aktifkan pengiriman ini sekarang? Status akan berubah menjadi Aktif.')">
                <i class="bi bi-play-circle me-1"></i>Mulai Pengiriman
            </button>
        </form>
        <?php endif; ?>

        <?php if ($canCancelDL): ?>
        <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal"
            data-bs-target="#cancelModal">
            <i class="bi bi-x-circle me-1"></i>Batalkan
        </button>
        <?php endif; ?>
    </div>

    <!-- Info grid -->
    <div class="dld-info">
        <div class="dld-info-grid">
            <div>
                <div class="dld-info-label">Nomor Delivery</div>
                <div class="dld-info-value"><?= esc($dl['delivery_number']) ?></div>
            </div>
            <div>
                <div class="dld-info-label">Tanggal Pengiriman</div>
                <div class="dld-info-value"><?= esc(format_date($dl['delivery_date'])) ?></div>
            </div>
            <div>
                <div class="dld-info-label">Nomor SO</div>
                <div class="dld-info-value">
                    <a href="<?= site_url('sales-orders/' . $dl['sales_order_id']) ?>" class="dld-info-link">
                        <?= esc($dl['so_number']) ?>
                    </a>
                </div>
            </div>
            <div>
                <div class="dld-info-label">Customer</div>
                <div class="dld-info-value"><?= esc($dl['customer_name']) ?></div>
            </div>
            <div>
                <div class="dld-info-label">Dicatat oleh</div>
                <div class="dld-info-value"><?= esc($dl['created_by_name']) ?></div>
                <div class="dld-info-sub"><?= esc(format_datetime($dl['created_at'])) ?></div>
            </div>

            <?php if ($dl['cancelled_at'] !== null): ?>
            <div style="grid-column: 1 / -1;">
                <div class="dld-info-label">Dibatalkan oleh</div>
                <div class="dld-info-value">
                    <?= esc($dl['cancelled_by_name'] ?? '-') ?>
                    <span class="dld-info-sub">(<?= esc(format_datetime($dl['cancelled_at'])) ?>)</span>
                </div>
                <div class="dld-info-sub">Alasan: <?= esc($dl['cancel_reason'] ?? '-') ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail Barang -->
<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0 dld-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th style="width: 90px;">Satuan</th>
                    <th class="text-end" style="width: 140px;">Dipesan (SO)</th>
                    <th class="text-end" style="width: 150px;">Dikirim pada DL ini</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dl['items'] as $it): ?>
                <tr>
                    <td>
                        <span class="dld-code"><?= esc($it['code']) ?></span>
                        <span class="dld-name ms-1"><?= esc($it['name']) ?></span>
                    </td>
                    <td class="text-muted"><?= esc($it['unit']) ?></td>
                    <td class="text-end"><span class="dld-qty"><?= number_format($it['ordered'], 0, ',', '.') ?></span>
                    </td>
                    <td class="text-end"><span
                            class="dld-qty is-shipped"><?= number_format($it['quantity_delivered'], 0, ',', '.') ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="dld-footer">
        <span class="dld-footer-label">Total Dikirim</span>
        <span class="dld-footer-total"><?= number_format($dl['total_qty'], 0, ',', '.') ?></span>
    </div>
</div>

<!-- Catatan Status -->
<?php if ($dl['status'] === Documents::DL_ACTIVE): ?>
<div class="dld-note is-info">
    <i class="bi bi-info-circle"></i>
    <div>Stok produk di atas sudah berkurang sesuai jumlah dikirim dan tercatat pada histori mutasi stok.</div>
</div>
<?php elseif ($dl['status'] === Documents::DL_SCHEDULED): ?>
<div class="dld-note is-warn">
    <i class="bi bi-clock-history"></i>
    <div>
        <strong>Delivery berstatus Terjadwal.</strong> Stok fisik sudah dialokasikan dan dikurangi dari gudang,
        namun barang belum dikirim. Tekan "Mulai Pengiriman" untuk mengaktifkan.
    </div>
</div>
<?php elseif ($dl['status'] === Documents::DL_CANCELLED): ?>
<div class="dld-note is-muted">
    <i class="bi bi-x-circle"></i>
    <div>Delivery ini dibatalkan. Stok produk di atas sudah dikembalikan dan tercatat pada histori mutasi stok.</div>
</div>
<?php endif; ?>

<?php if ($canCancelDL): ?>
<!-- Modal Batalkan -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form method="post" action="<?= site_url('deliveries/' . $dl['id'] . '/cancel') ?>"
            class="modal-content border-0" style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="dld-del-ico"><i class="bi bi-x-circle"></i></div>
                <h5 class="fw-bold mb-2" id="cancelTitle">Batalkan Delivery?</h5>
                <p class="mb-3 text-muted small">
                    Delivery <strong class="text-dark"><?= esc($dl['delivery_number']) ?></strong> akan berubah status
                    menjadi <strong>Dibatalkan</strong>.<br>
                    Stok akan <strong>dikembalikan</strong> sebesar kuantitas yang pernah dikirim.
                </p>

                <div class="text-start">
                    <label for="dl_cancel_reason" class="form-label fw-semibold small">
                        Alasan pembatalan <span class="text-danger">*</span>
                    </label>
                    <textarea id="dl_cancel_reason" name="cancel_reason" rows="3" maxlength="500" required
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