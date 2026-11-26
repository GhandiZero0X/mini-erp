<?php
/**
 * UI-10 Form Goods Receipt. Variabel: $title, $po (header + 'items' dengan 'received' dan 'remaining'),
 * $action, $today, $errors, $old.
 * Kuantitas diterima diisi manual (default 0): stok hanya bertambah sesuai barang yang benar-benar diterima (BR-03).
 */
$this->extend('layouts/main');

$dateVal = (string) (($old['receipt_date'] ?? '') !== '' ? $old['receipt_date'] : $today);
$oldQty  = (isset($old['quantities']) && is_array($old['quantities'])) ? $old['quantities'] : [];
$qtyErr  = (isset($errors['quantities']) && is_array($errors['quantities'])) ? $errors['quantities'] : [];
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "grf-" (Goods Receipt Form) */
.grf-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.grf-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.grf-head>* {
    position: relative;
    z-index: 1;
}

.grf-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.grf-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.grf-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.grf-head .btn-back {
    border: 0;
    color: #fff;
    background: rgba(255, 255, 255, .18);
}

.grf-head .btn-back:hover {
    background: rgba(255, 255, 255, .3);
}

/* SO Info card */
.grf-po-card {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding: 1rem 1.5rem;
    background: #fafbff;
    border-bottom: 1px solid var(--app-line);
}

.grf-po-item {
    flex: 1 1 150px;
}

.grf-po-label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--app-muted);
    margin-bottom: .15rem;
}

.grf-po-value {
    font-weight: 700;
    color: var(--app-ink);
    font-size: .92rem;
    overflow-wrap: anywhere;
}

/* Form section */
.grf-form {
    margin-top: 1.1rem;
}

.grf-sec {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--app-line);
}

.grf-sec:last-of-type {
    border-bottom: 0;
}

.grf-sec-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.1rem;
}

.grf-sec-ico {
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    background: #eef0ff;
    color: #4338ca;
    font-size: 1rem;
}

.grf-sec-head h3 {
    font-size: .98rem;
    font-weight: 800;
    margin: 0;
}

.grf-sec-head p {
    margin: 0;
    font-size: .8rem;
    color: var(--app-muted);
}

.grf-form .form-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .4rem;
}

.grf-form .input-group-text {
    background: #f8f9fd;
    border-color: #dfe3ee;
    color: var(--app-muted);
    font-weight: 700;
}

.grf-form .form-control[disabled] {
    background: #f4f5fa;
    color: #6b7385;
    cursor: not-allowed;
}

/* Tabel item */
.grf-items-table th {
    background: #f8f9fd;
    color: var(--app-muted);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 1px solid var(--app-line);
    white-space: nowrap;
}

.grf-items-table td {
    vertical-align: middle;
    padding: .5rem;
}

.grf-items-table .form-control-sm {
    font-size: .85rem;
}

.grf-row-done td {
    opacity: .6;
}

.grf-code {
    display: inline-block;
    padding: .15rem .5rem;
    border-radius: 6px;
    background: #f1f3f9;
    color: #3b4358;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .78rem;
    font-weight: 600;
    white-space: nowrap;
}

.grf-num {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    white-space: nowrap;
}

/* Footer action */
.grf-foot {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1.15rem 1.75rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

.grf-help {
    padding: 0 1.75rem 1.25rem;
    font-size: .82rem;
    color: var(--app-muted);
    line-height: 1.5;
}

@media (max-width: 767.98px) {
    .grf-head {
        padding: 1.1rem;
    }

    .grf-sec,
    .grf-foot,
    .grf-help {
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }

    .grf-po-card {
        padding: 1rem 1.1rem;
    }
}
</style>

<!-- Header -->
<div class="grf-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="grf-head-ico" aria-hidden="true"><i class="bi bi-box-arrow-in-down"></i></span>
        <div>
            <h2><?= esc($title) ?></h2>
            <p>Tentukan barang yang benar-benar diterima dari supplier.</p>
        </div>
    </div>
    <a href="<?= site_url('purchase-orders') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar PO
    </a>
</div>

<div class="mb-3"></div>

<div class="grf-form">
    <form method="post" action="<?= esc($action, 'attr') ?>" class="card" novalidate>
        <?= csrf_field() ?>

        <!-- PO Summary -->
        <div class="grf-po-card">
            <div class="grf-po-item">
                <div class="grf-po-label">Nomor PO</div>
                <div class="grf-po-value"><?= esc($po['po_number']) ?></div>
            </div>
            <div class="grf-po-item">
                <div class="grf-po-label">Supplier</div>
                <div class="grf-po-value"><?= esc($po['supplier_name']) ?></div>
            </div>
            <div class="grf-po-item">
                <div class="grf-po-label">Tanggal PO</div>
                <div class="grf-po-value"><?= esc(format_date($po['po_date'])) ?></div>
            </div>
        </div>

        <!-- 1. Informasi Penerimaan -->
        <section class="grf-sec">
            <div class="grf-sec-head">
                <span class="grf-sec-ico"><i class="bi bi-calendar-event"></i></span>
                <div>
                    <h3>Informasi Penerimaan</h3>
                    <p>Nomor otomatis dan tanggal penerimaan barang.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nomor GR</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                        <input type="text" class="form-control" value="Otomatis saat disimpan" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="receipt_date" class="form-label">Tanggal Penerimaan <span
                            class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        <input type="date" id="receipt_date" name="receipt_date"
                            class="form-control<?= isset($errors['receipt_date']) ? ' is-invalid' : '' ?>"
                            value="<?= esc($dateVal, 'attr') ?>">
                        <?php if (isset($errors['receipt_date'])): ?>
                        <div class="invalid-feedback d-block"><?= esc($errors['receipt_date']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Barang Diterima -->
        <section class="grf-sec">
            <div class="grf-sec-head">
                <span class="grf-sec-ico"><i class="bi bi-box-seam"></i></span>
                <div>
                    <h3>Barang Diterima</h3>
                    <p>Isi kuantitas yang benar-benar diterima pada penerimaan ini.</p>
                </div>
            </div>

            <?php if (isset($errors['items'])): ?>
            <div class="alert alert-danger py-2"><?= esc($errors['items']) ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table align-middle mb-0 grf-items-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="width: 80px;">Satuan</th>
                            <th class="text-end" style="width: 100px;">Dipesan</th>
                            <th class="text-end" style="width: 110px;">Sudah</th>
                            <th class="text-end" style="width: 100px;">Sisa</th>
                            <th style="width: 170px;" class="text-end">Diterima Sekarang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($po['items'] as $it):
                            $id    = (int) $it['id'];
                            $value = array_key_exists($id, $oldQty) ? (string) $oldQty[$id] : '0';
                            $done  = $it['remaining'] === 0;
                        ?>
                        <tr class="<?= $done ? 'grf-row-done' : '' ?>">
                            <td>
                                <span class="grf-code"><?= esc($it['code']) ?></span>
                                <span class="ms-1 fw-semibold"><?= esc($it['name']) ?></span>
                            </td>
                            <td class="text-muted"><?= esc($it['unit']) ?></td>
                            <td class="text-end"><span
                                    class="grf-num"><?= number_format($it['quantity'], 0, ',', '.') ?></span></td>
                            <td class="text-end"><span
                                    class="grf-num"><?= number_format($it['received'], 0, ',', '.') ?></span></td>
                            <td class="text-end"><span
                                    class="grf-num fw-bold"><?= number_format($it['remaining'], 0, ',', '.') ?></span>
                            </td>
                            <td>
                                <input type="text" inputmode="numeric" autocomplete="off" name="quantities[<?= $id ?>]"
                                    value="<?= esc($value, 'attr') ?>"
                                    class="form-control form-control-sm text-end<?= isset($qtyErr[$id]) ? ' is-invalid' : '' ?>"
                                    <?= $done ? 'readonly' : '' ?>>
                                <?php if (isset($qtyErr[$id])): ?>
                                <div class="invalid-feedback d-block"><?= esc($qtyErr[$id]) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grf-help">
            <i class="bi bi-info-circle me-1"></i>
            Isi jumlah yang benar-benar diterima (maksimal sebesar sisa; isi 0 bila belum datang).
            Stok bertambah sesuai jumlah yang Anda masukkan.
        </div>

        <div class="grf-foot">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i>Simpan Penerimaan
            </button>
            <a href="<?= site_url('purchase-orders') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>