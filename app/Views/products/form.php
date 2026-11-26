<?php
/**
 * UI-04 Produk (form tambah/ubah). Variabel: $product, $categories, $units, $action, $errors, $old.
 * Produk baru berstok 0; stok hanya berubah lewat GR dan Delivery (BR-06).
 * Batas bawah & atas adalah parameter untuk reorder alert, bukan saldo stok.
 */
$this->extend('layouts/main');

$isEdit = $product !== null;
$locked = $isEdit && !empty($product['code_locked']);

$val = static function (string $key) use ($old, $product): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }
    $v = (string) ($product[$key] ?? '');
    if (in_array($key, ['purchase_price', 'selling_price'], true) && str_ends_with($v, '.00')) {
        $v = substr($v, 0, -3);
    }

    return $v;
};

$err = static fn(string $f): string => isset($errors[$f]) ? '<div class="invalid-feedback d-block">' . esc($errors[$f]) . '</div>' : '';
$cls = static fn(string $f): string => isset($errors[$f]) ? ' is-invalid' : '';

$selectedCategory = (int) $val('category_id');
$selectedUnit = (int) $val('unit_id');
$cannotSave = ($categories === [] || $units === []);
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "prd-" supaya tidak bentrok dengan Bootstrap / dashboard (dx-) / kategori (cat-) */
.prd-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.prd-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.prd-head>* {
    position: relative;
    z-index: 1;
}

.prd-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.prd-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.prd-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.prd-head .btn-back {
    border: 0;
    color: #fff;
    background: rgba(255, 255, 255, .18);
}

.prd-head .btn-back:hover {
    background: rgba(255, 255, 255, .3);
}

.prd-form {
    max-width: 860px;
    margin-top: 1.1rem;
}

/* ---------- Seksi form ---------- */
.prd-sec {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--app-line);
}

.prd-sec:last-of-type {
    border-bottom: 0;
}

.prd-sec-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.1rem;
}

.prd-sec-ico {
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

.prd-sec-head h3 {
    font-size: .98rem;
    font-weight: 800;
    margin: 0;
}

.prd-sec-head p {
    margin: 0;
    font-size: .8rem;
    color: var(--app-muted);
}

.prd-form .form-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .4rem;
}

.prd-form .form-text {
    font-size: .78rem;
}

.prd-form .input-group-text {
    background: #f8f9fd;
    border-color: #dfe3ee;
    color: var(--app-muted);
    font-weight: 700;
    font-size: .85rem;
}

.prd-form .input-group>.form-control {
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
}

.prd-form .input-group>.input-group-text {
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
}

.prd-form .form-control[readonly] {
    background: #f4f5fa;
    color: #6b7385;
    cursor: not-allowed;
}

.prd-code-input {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-weight: 600;
    letter-spacing: .02em;
}

/* Ringkasan margin langsung */
.prd-margin {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-top: .25rem;
    padding: .7rem .95rem;
    border-radius: 12px;
    background: #f8f9fd;
    border: 1px dashed #cfd5e6;
    font-size: .84rem;
    color: var(--app-muted);
}

.prd-margin i {
    font-size: 1rem;
}

.prd-margin b {
    color: var(--app-ink);
}

.prd-margin.is-up {
    background: #e9f9f1;
    border-color: #a9e5c9;
    color: #0f8a5c;
}

.prd-margin.is-up b {
    color: #0b6b47;
}

.prd-margin.is-down {
    background: #fdeceb;
    border-color: #f5b8b2;
    color: #b91c1c;
}

.prd-margin.is-down b {
    color: #991b1b;
}

.prd-note {
    display: flex;
    gap: .6rem;
    padding: .75rem .95rem;
    border-radius: 12px;
    background: #eef0ff;
    color: #3b3f8f;
    font-size: .82rem;
}

.prd-note i {
    flex: 0 0 auto;
    margin-top: .1rem;
}

.prd-warn {
    display: none;
    margin-top: .4rem;
    font-size: .78rem;
    font-weight: 600;
    color: #b45309;
}

.prd-foot {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1.15rem 1.75rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

@media (max-width: 575.98px) {
    .prd-head {
        padding: 1.1rem;
    }

    .prd-sec,
    .prd-foot {
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }
}
</style>

<div class="prd-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="prd-head-ico" aria-hidden="true"><i
                class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-box-seam' ?>"></i></span>
        <div>
            <h2><?= esc($title) ?></h2>
            <p><?= $isEdit ? 'Perbarui data, harga, dan batas stok produk.' : 'Lengkapi data produk baru. Stok awal selalu 0.' ?>
            </p>
        </div>
    </div>
    <a href="<?= site_url('products') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>
</div>

<div class="prd-form">
    <?php if ($categories === []): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Belum ada kategori. Tambahkan kategori terlebih dahulu sebelum membuat produk.
        <a href="<?= site_url('categories/new') ?>" class="alert-link">Tambah kategori</a>
    </div>
    <?php endif; ?>

    <?php if ($units === []): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Belum ada satuan. Tambahkan satuan terlebih dahulu sebelum membuat produk.
        <a href="<?= site_url('units/new') ?>" class="alert-link">Tambah satuan</a>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= esc($action, 'attr') ?>" class="card" id="prdForm" novalidate>
        <?= csrf_field() ?>

        <!-- 1. Identitas -->
        <section class="prd-sec">
            <div class="prd-sec-head">
                <span class="prd-sec-ico"><i class="bi bi-upc-scan"></i></span>
                <div>
                    <h3>Identitas Produk</h3>
                    <p>Kode unik dan nama yang tampil di seluruh transaksi.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="code" class="form-label">Kode <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi <?= $locked ? 'bi-lock' : 'bi-hash' ?>"></i></span>
                        <input type="text" id="code" name="code" maxlength="30" autocomplete="off"
                            class="form-control prd-code-input<?= $cls('code') ?>"
                            value="<?= esc($val('code'), 'attr') ?>" <?= $locked ? 'readonly' : 'autofocus' ?>>
                    </div>
                    <?= $err('code') ?>
                    <?php if ($locked): ?>
                    <div class="form-text">Kode tidak dapat diubah karena produk sudah digunakan pada transaksi.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" maxlength="150" autocomplete="off"
                        class="form-control<?= $cls('name') ?>" value="<?= esc($val('name'), 'attr') ?>"
                        <?= $locked ? 'autofocus' : '' ?>>
                    <?= $err('name') ?>
                </div>
            </div>
        </section>

        <!-- 2. Klasifikasi -->
        <section class="prd-sec">
            <div class="prd-sec-head">
                <span class="prd-sec-ico"><i class="bi bi-tags"></i></span>
                <div>
                    <h3>Klasifikasi</h3>
                    <p>Satuan hitung dan kategori untuk pencarian serta laporan.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="unit_id" class="form-label">Satuan <span class="text-danger">*</span></label>
                    <select id="unit_id" name="unit_id" class="form-select<?= $cls('unit_id') ?>">
                        <option value="">- Pilih satuan -</option>
                        <?php foreach ($units as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= $selectedUnit === (int) $u['id'] ? 'selected' : '' ?>>
                            <?= esc($u['code'] . ' - ' . $u['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('unit_id') ?>
                </div>
                <div class="col-md-6">
                    <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select id="category_id" name="category_id" class="form-select<?= $cls('category_id') ?>">
                        <option value="">- Pilih kategori -</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"
                            <?= $selectedCategory === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('category_id') ?>
                </div>
            </div>
        </section>

        <!-- 3. Harga -->
        <section class="prd-sec">
            <div class="prd-sec-head">
                <span class="prd-sec-ico"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <h3>Harga</h3>
                    <p>Angka saja, tanpa pemisah ribuan. Desimal dengan titik (maks. 2 angka).</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="purchase_price" class="form-label">Harga Beli <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="decimal" id="purchase_price" name="purchase_price"
                            autocomplete="off" class="form-control<?= $cls('purchase_price') ?>"
                            value="<?= esc($val('purchase_price'), 'attr') ?>" placeholder="0">
                    </div>
                    <?= $err('purchase_price') ?>
                </div>
                <div class="col-md-6">
                    <label for="selling_price" class="form-label">Harga Jual <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="decimal" id="selling_price" name="selling_price"
                            autocomplete="off" class="form-control<?= $cls('selling_price') ?>"
                            value="<?= esc($val('selling_price'), 'attr') ?>" placeholder="0">
                    </div>
                    <?= $err('selling_price') ?>
                </div>
                <div class="col-12">
                    <div class="prd-margin" id="prdMargin" aria-live="polite">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span id="prdMarginText">Isi harga beli dan harga jual untuk melihat margin.</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Pengawasan stok -->
        <section class="prd-sec">
            <div class="prd-sec-head">
                <span class="prd-sec-ico"><i class="bi bi-speedometer2"></i></span>
                <div>
                    <h3>Pengawasan Stok</h3>
                    <p>Parameter peringatan reorder, bukan saldo stok.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="min_stock" class="form-label">Batas Bawah (Minimum)</label>
                    <input type="number" min="0" id="min_stock" name="min_stock"
                        class="form-control<?= $cls('min_stock') ?>" value="<?= esc($val('min_stock'), 'attr') ?>"
                        placeholder="0">
                    <?= $err('min_stock') ?>
                    <div class="form-text">Stok di bawah/sama dengan angka ini &rarr; perlu Purchase Order.</div>
                </div>
                <div class="col-md-6">
                    <label for="max_stock" class="form-label">Batas Atas (Maksimum)</label>
                    <input type="number" min="0" id="max_stock" name="max_stock"
                        class="form-control<?= $cls('max_stock') ?>" value="<?= esc($val('max_stock'), 'attr') ?>"
                        placeholder="Kosongkan jika tidak ada batas">
                    <?= $err('max_stock') ?>
                    <div class="form-text">Kosongkan bila produk tidak memiliki batas atas.</div>
                    <div class="prd-warn" id="prdStockWarn"><i class="bi bi-exclamation-triangle me-1"></i>Batas atas
                        lebih kecil dari batas bawah.</div>
                </div>
                <div class="col-12">
                    <div class="prd-note">
                        <i class="bi bi-info-circle"></i>
                        <span>Stok hanya berubah lewat Goods Receipt dan Delivery. Produk baru selalu dimulai dari stok
                            0.</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="prd-foot">
            <button type="submit" class="btn btn-primary px-4" id="prdSubmit" <?= $cannotSave ? 'disabled' : '' ?>>
                <i
                    class="bi <?= $isEdit ? 'bi-check2-circle' : 'bi-plus-lg' ?> me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' ?>
            </button>
            <a href="<?= site_url('products') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
(function() {
    'use strict';
    var buy = document.getElementById('purchase_price');
    var sell = document.getElementById('selling_price');
    var box = document.getElementById('prdMargin');
    var text = document.getElementById('prdMarginText');
    var minEl = document.getElementById('min_stock');
    var maxEl = document.getElementById('max_stock');
    var warn = document.getElementById('prdStockWarn');
    var form = document.getElementById('prdForm');
    var btn = document.getElementById('prdSubmit');

    function rp(n) {
        return 'Rp ' + n.toLocaleString('id-ID', {
            maximumFractionDigits: 2
        });
    }

    // Margin langsung: hanya pratinjau, validasi tetap di server
    function margin() {
        var b = parseFloat(buy.value.replace(',', '.'));
        var s = parseFloat(sell.value.replace(',', '.'));
        box.classList.remove('is-up', 'is-down');
        if (isNaN(b) || isNaN(s) || b <= 0) {
            text.textContent = 'Isi harga beli dan harga jual untuk melihat margin.';
            return;
        }
        var diff = s - b;
        var pct = (diff / b) * 100;
        var sign = diff > 0 ? '+' : '';
        text.innerHTML = 'Margin per unit: <b>' + sign + rp(diff) + '</b> (<b>' + sign +
            pct.toLocaleString('id-ID', {
                maximumFractionDigits: 1
            }) + '%</b>)' +
            (diff < 0 ? ' &mdash; harga jual di bawah harga beli' : '');
        if (diff > 0) box.classList.add('is-up');
        if (diff < 0) box.classList.add('is-down');
    }

    function stock() {
        var mn = parseInt(minEl.value, 10);
        var mx = parseInt(maxEl.value, 10);
        warn.style.display = (!isNaN(mn) && !isNaN(mx) && mx < mn) ? 'block' : 'none';
    }

    [buy, sell].forEach(function(el) {
        el.addEventListener('input', margin);
    });
    [minEl, maxEl].forEach(function(el) {
        el.addEventListener('input', stock);
    });
    margin();
    stock();

    // Cegah klik ganda: kode produk unik, simpan kedua akan gagal
    form.addEventListener('submit', function() {
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
    });
})();
</script>

<?= $this->endSection() ?>