<?php
/**
 * UI-13 Form Delivery. Variabel: $title, $so (header + 'items' dengan 'remaining', 'stock_available'),
 * $action, $today, $errors, $old.
 */
$this->extend('layouts/main');

$dateVal = (string) (($old['delivery_date'] ?? '') !== '' ? $old['delivery_date'] : $today);
$oldQty  = (isset($old['quantities']) && is_array($old['quantities'])) ? $old['quantities'] : [];
$qtyErr  = (isset($errors['quantities']) && is_array($errors['quantities'])) ? $errors['quantities'] : [];
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "dlf-" (Delivery Form) */
.dlf-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.dlf-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.dlf-head>* {
    position: relative;
    z-index: 1;
}

.dlf-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.dlf-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.dlf-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.dlf-head .btn-back {
    border: 0;
    color: #fff;
    background: rgba(255, 255, 255, .18);
}

.dlf-head .btn-back:hover {
    background: rgba(255, 255, 255, .3);
}

/* SO Info card */
.dlf-so-card {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding: 1rem 1.5rem;
    background: #fafbff;
    border-bottom: 1px solid var(--app-line);
}

.dlf-so-item {
    flex: 1 1 150px;
}

.dlf-so-label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--app-muted);
    margin-bottom: .15rem;
}

.dlf-so-value {
    font-weight: 700;
    color: var(--app-ink);
    font-size: .92rem;
    overflow-wrap: anywhere;
}

/* Form section */
.dlf-form {
    margin-top: 1.1rem;
}

.dlf-sec {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--app-line);
}

.dlf-sec:last-of-type {
    border-bottom: 0;
}

.dlf-sec-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.1rem;
}

.dlf-sec-ico {
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

.dlf-sec-head h3 {
    font-size: .98rem;
    font-weight: 800;
    margin: 0;
}

.dlf-sec-head p {
    margin: 0;
    font-size: .8rem;
    color: var(--app-muted);
}

.dlf-form .form-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .4rem;
}

.dlf-form .input-group-text {
    background: #f8f9fd;
    border-color: #dfe3ee;
    color: var(--app-muted);
    font-weight: 700;
}

.dlf-form .form-control[disabled] {
    background: #f4f5fa;
    color: #6b7385;
    cursor: not-allowed;
}

/* Tabel item */
.dlf-items-table th {
    background: #f8f9fd;
    color: var(--app-muted);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 1px solid var(--app-line);
    white-space: nowrap;
}

.dlf-items-table td {
    vertical-align: middle;
    padding: .5rem;
}

.dlf-items-table .form-control-sm {
    font-size: .85rem;
}

.dlf-row-done td {
    opacity: .6;
}

.dlf-code {
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

.dlf-num {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    white-space: nowrap;
}

.dlf-num.is-low {
    color: #b91c1c;
    font-weight: 700;
}

/* Schedule note */
.dlf-schedule-note {
    display: flex;
    gap: .75rem;
    padding: .9rem 1.1rem;
    border-radius: 12px;
    background: #eef0ff;
    color: #3b3f8f;
    font-size: .84rem;
    border: 1px solid #d6d9f5;
    margin-top: .5rem;
}

.dlf-schedule-note i {
    flex: 0 0 auto;
    margin-top: .1rem;
}

/* Footer action */
.dlf-foot {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1.15rem 1.75rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

.dlf-help {
    padding: 0 1.75rem 1.25rem;
    font-size: .82rem;
    color: var(--app-muted);
    line-height: 1.5;
}

@media (max-width: 767.98px) {
    .dlf-head {
        padding: 1.1rem;
    }

    .dlf-sec,
    .dlf-foot,
    .dlf-help {
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }

    .dlf-so-card {
        padding: 1rem 1.1rem;
    }
}
</style>

<!-- Header -->
<div class="dlf-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="dlf-head-ico" aria-hidden="true"><i class="bi bi-truck"></i></span>
        <div>
            <h2><?= esc($title) ?></h2>
            <p>Tentukan barang yang akan dikirim dari Sales Order ini.</p>
        </div>
    </div>
    <a href="<?= site_url('sales-orders') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar SO
    </a>
</div>

<div class="mb-3"></div>

<?php if (session('error')): ?>
<div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-circle me-1"></i><?= esc(session('error')) ?>
</div>
<?php endif; ?>

<div class="dlf-form">
    <form method="post" action="<?= esc($action, 'attr') ?>" class="card" novalidate>
        <?= csrf_field() ?>

        <!-- SO Summary -->
        <div class="dlf-so-card">
            <div class="dlf-so-item">
                <div class="dlf-so-label">Nomor SO</div>
                <div class="dlf-so-value"><?= esc($so['so_number']) ?></div>
            </div>
            <div class="dlf-so-item">
                <div class="dlf-so-label">Customer</div>
                <div class="dlf-so-value"><?= esc($so['customer_name']) ?></div>
            </div>
            <div class="dlf-so-item">
                <div class="dlf-so-label">Tanggal SO</div>
                <div class="dlf-so-value"><?= esc(format_date($so['so_date'])) ?></div>
            </div>
        </div>

        <!-- 1. Informasi Delivery -->
        <section class="dlf-sec">
            <div class="dlf-sec-head">
                <span class="dlf-sec-ico"><i class="bi bi-calendar-event"></i></span>
                <div>
                    <h3>Informasi Pengiriman</h3>
                    <p>Nomor otomatis dan tanggal pengiriman barang.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nomor Delivery</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                        <input type="text" class="form-control" value="Otomatis saat disimpan" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="delivery_date" class="form-label">Tanggal Pengiriman <span
                            class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        <input type="date" id="delivery_date" name="delivery_date"
                            class="form-control<?= isset($errors['delivery_date']) ? ' is-invalid' : '' ?>"
                            value="<?= esc($dateVal, 'attr') ?>">
                        <?php if (isset($errors['delivery_date'])): ?>
                        <div class="invalid-feedback d-block"><?= esc($errors['delivery_date']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div id="schedule-note" class="dlf-schedule-note d-none">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            Tanggal di masa depan &mdash; status akan menjadi <strong>Terjadwal</strong>.
                            Stok akan dialokasikan namun barang belum dikirim.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Barang Dikirim -->
        <section class="dlf-sec">
            <div class="dlf-sec-head">
                <span class="dlf-sec-ico"><i class="bi bi-box-seam"></i></span>
                <div>
                    <h3>Barang Dikirim</h3>
                    <p>Isi kuantitas yang benar-benar dikirim pada pengiriman ini.</p>
                </div>
            </div>

            <?php if (isset($errors['items'])): ?>
            <div class="alert alert-danger py-2"><?= esc($errors['items']) ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table align-middle mb-0 dlf-items-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="width: 80px;">Satuan</th>
                            <th class="text-end" style="width: 100px;">Dipesan</th>
                            <th class="text-end" style="width: 110px;">Sudah</th>
                            <th class="text-end" style="width: 100px;">Sisa</th>
                            <th class="text-end" style="width: 110px;">Stok</th>
                            <th style="width: 170px;" class="text-end">Dikirim Sekarang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($so['items'] as $it):
                            $id       = (int) $it['id'];
                            $value    = array_key_exists($id, $oldQty) ? (string) $oldQty[$id] : '0';
                            $done     = $it['remaining'] === 0;
                            $lowStock = $it['stock_available'] < $it['remaining'];
                        ?>
                        <tr class="<?= $done ? 'dlf-row-done' : '' ?>">
                            <td>
                                <span class="dlf-code"><?= esc($it['code']) ?></span>
                                <span class="ms-1 fw-semibold"><?= esc($it['name']) ?></span>
                            </td>
                            <td class="text-muted"><?= esc($it['unit']) ?></td>
                            <td class="text-end"><span
                                    class="dlf-num"><?= number_format($it['quantity'], 0, ',', '.') ?></span></td>
                            <td class="text-end"><span
                                    class="dlf-num"><?= number_format($it['delivered'], 0, ',', '.') ?></span></td>
                            <td class="text-end"><span
                                    class="dlf-num"><?= number_format($it['remaining'], 0, ',', '.') ?></span></td>
                            <td class="text-end">
                                <span class="dlf-num <?= $lowStock ? 'is-low' : '' ?>">
                                    <?= number_format($it['stock_available'], 0, ',', '.') ?>
                                </span>
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

        <div class="dlf-help">
            <i class="bi bi-info-circle me-1"></i>
            Isi jumlah yang benar-benar dikirim (maksimal sebesar sisa; isi 0 bila belum dikirim).
            Stok berkurang sesuai jumlah ini. Bila stok tidak mencukupi, sistem akan menolak seluruh pengiriman.
        </div>

        <div class="dlf-foot">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i>Simpan Delivery
            </button>
            <a href="<?= site_url('sales-orders') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dateInput = document.getElementById('delivery_date');
    var noteDiv = document.getElementById('schedule-note');
    if (!dateInput || !noteDiv) return;

    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var yyyy = today.getFullYear();
    var todayStr = yyyy + '-' + mm + '-' + dd;

    function checkSchedule() {
        if (dateInput.value > todayStr) {
            noteDiv.classList.remove('d-none');
        } else {
            noteDiv.classList.add('d-none');
        }
    }
    checkSchedule();
    dateInput.addEventListener('change', checkSchedule);
});
</script>

<?= $this->endSection() ?>