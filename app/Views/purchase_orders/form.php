<?php
/**
 * UI-08 Form Purchase Order. Variabel: $title, $suppliers, $products, $action, $today, $errors, $old, $preselect.
 */
$this->extend('layouts/main');

$supplierSel = (int) ($old['supplier_id'] ?? 0);
$dateVal     = (string) (($old['po_date'] ?? '') !== '' ? $old['po_date'] : $today);
$oldRows     = (isset($old['items']) && is_array($old['items'])) ? $old['items'] : [];
$rowErrors   = (isset($errors['item_rows']) && is_array($errors['item_rows'])) ? $errors['item_rows'] : [];
$cannotSave  = $suppliers === [] || $products === [];
$preselect   = $preselect ?? null;

$priceText = static function ($v): string {
    $v = (string) $v;
    return str_ends_with($v, '.00') ? substr($v, 0, -3) : $v;
};

$err = static fn (string $f): string => isset($errors[$f]) ? '<div class="invalid-feedback d-block">' . esc($errors[$f]) . '</div>' : '';
$cls = static fn (string $f): string => isset($errors[$f]) ? ' is-invalid' : '';

// Daftar produk dikirim SEKALI ke JS (bukan di-render per baris) agar ringan walau produk banyak.
$productsJs = array_map(static function (array $p) use ($priceText): array {
    $max       = $p['max_stock'] ?? null;
    $available = $p['available_room'] ?? null;
    $hasLimit  = $max !== null;
    return [
        'id'        => (string) (int) $p['id'],
        'code'      => (string) $p['code'],
        'name'      => (string) $p['name'],
        'label'     => $p['code'] . ' - ' . $p['name'],
        'unit'      => (string) $p['unit'],
        'price'     => $priceText($p['purchase_price']),
        'max'       => $hasLimit ? (int) $max : null,
        'available' => $hasLimit ? (int) $available : null,
    ];
}, $products);
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>

<?= $this->section('content') ?>

<!-- Tom Select (dropdown + pencarian). Bisa diunduh & disimpan lokal di public/ bila perlu offline. -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
/* Prefiks "pof-" (Purchase Order Form) */
.pof-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.pof-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.pof-head>* {
    position: relative;
    z-index: 1;
}

.pof-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.pof-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.pof-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.pof-head .btn-back {
    border: 0;
    color: #fff;
    background: rgba(255, 255, 255, .18);
}

.pof-head .btn-back:hover {
    background: rgba(255, 255, 255, .3);
}

/* Form section */
.pof-form {
    margin-top: 1.1rem;
}

.pof-sec {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--app-line);
}

.pof-sec:last-of-type {
    border-bottom: 0;
}

.pof-sec-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.1rem;
}

.pof-sec-ico {
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

.pof-sec-head h3 {
    font-size: .98rem;
    font-weight: 800;
    margin: 0;
}

.pof-sec-head p {
    margin: 0;
    font-size: .8rem;
    color: var(--app-muted);
}

.pof-form .form-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .4rem;
}

.pof-form .form-text {
    font-size: .78rem;
}

.pof-form .input-group-text {
    background: #f8f9fd;
    border-color: #dfe3ee;
    color: var(--app-muted);
    font-weight: 700;
}

.pof-form .form-control[disabled] {
    background: #f4f5fa;
    color: #6b7385;
    cursor: not-allowed;
}

/* Tabel item */
.pof-items-table th {
    background: #f8f9fd;
    color: var(--app-muted);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 1px solid var(--app-line);
    white-space: nowrap;
}

.pof-items-table td {
    vertical-align: middle;
    padding: .5rem;
}

.pof-items-table .form-control-sm {
    font-size: .85rem;
}

/* Total footer */
.pof-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
}

.pof-total-label {
    font-size: .95rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.pof-total-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

/* Footer action */
.pof-foot {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1.15rem 1.75rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

/* Alert */
.pof-alert-warn {
    display: flex;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    background: #fff4d6;
    color: #946500;
    font-size: .86rem;
    border: 1px solid #fde8b0;
    margin-bottom: 1rem;
}

.pof-alert-warn i {
    flex: 0 0 auto;
    margin-top: .1rem;
}

.pof-alert-warn a {
    color: #946500;
    font-weight: 700;
    text-decoration: underline;
}

@media (max-width: 767.98px) {
    .pof-head {
        padding: 1.1rem;
    }

    .pof-sec,
    .pof-foot {
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }

    .pof-total-row {
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }
}

/* Tom Select: menyatu dengan input-group & state error */
.input-group>.ts-wrapper {
    flex: 1 1 auto;
    width: 1%;
    min-width: 0;
}

.input-group>.ts-wrapper .ts-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.ts-wrapper.is-invalid .ts-control {
    border-color: #dc3545;
}

/* Field tampil seperti select biasa: tanpa kursor ketik, pencarian ada di dalam dropdown */
.ts-wrapper.single .ts-control,
.ts-wrapper.single .ts-control * {
    cursor: pointer;
}

.ts-wrapper.single .ts-control>input {
    caret-color: transparent;
}

.ts-dropdown .dropdown-input {
    margin: .4rem;
    width: calc(100% - .8rem);
    border-radius: 8px;
}

.item-row .ts-wrapper {
    min-width: 260px;
}

.item-row .ts-control {
    font-size: .85rem;
    padding-top: .25rem;
    padding-bottom: .25rem;
}

.ts-dropdown {
    z-index: 2000;
}

.ts-dropdown .pof-ts-sub {
    font-size: .78rem;
    color: #6b7385;
}

.ts-dropdown .pof-ts-sub.is-full {
    color: #dc3545;
    font-weight: 600;
}
</style>

<!-- Header -->
<div class="pof-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="pof-head-ico" aria-hidden="true"><i class="bi bi-file-earmark-plus"></i></span>
        <div>
            <h2><?= esc($title) ?></h2>
            <p>Lengkapi data pembelian dan daftar produk yang akan dipesan.</p>
        </div>
    </div>
    <a href="<?= site_url('purchase-orders') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>
</div>

<div class="mb-3"></div>

<?php if ($cannotSave): ?>
<div class="pof-alert-warn">
    <i class="bi bi-exclamation-triangle"></i>
    <div>
        Purchase Order memerlukan minimal satu supplier dan satu produk pada master data.
        <a href="<?= site_url('suppliers') ?>">Supplier</a> &middot;
        <a href="<?= site_url('products') ?>">Produk</a>
    </div>
</div>
<?php endif; ?>

<div class="pof-form">
    <form method="post" action="<?= esc($action, 'attr') ?>" class="card" novalidate>
        <?= csrf_field() ?>

        <!-- 1. Informasi PO -->
        <section class="pof-sec">
            <div class="pof-sec-head">
                <span class="pof-sec-ico"><i class="bi bi-info-circle"></i></span>
                <div>
                    <h3>Informasi Purchase Order</h3>
                    <p>Tanggal dan supplier tujuan pesanan.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nomor PO</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                        <input type="text" class="form-control" value="Otomatis saat disimpan" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="po_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        <input type="date" id="po_date" name="po_date" class="form-control<?= $cls('po_date') ?>"
                            value="<?= esc($dateVal, 'attr') ?>">
                    </div>
                    <?= $err('po_date') ?>
                </div>
                <div class="col-md-4">
                    <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shop"></i></span>
                        <select id="supplier_id" name="supplier_id" class="form-select<?= $cls('supplier_id') ?>">
                            <option value="">- Pilih supplier -</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"
                                <?= $supplierSel === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= esc($s['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?= $err('supplier_id') ?>
                </div>
            </div>
        </section>

        <!-- 2. Detail Produk -->
        <section class="pof-sec">
            <div class="pof-sec-head">
                <span class="pof-sec-ico"><i class="bi bi-box-seam"></i></span>
                <div>
                    <h3>Detail Produk</h3>
                    <p>Tambahkan produk yang akan dipesan beserta kuantitas dan harga.</p>
                </div>
            </div>

            <?= $err('items') ?>

            <div class="table-responsive">
                <table class="table align-middle mb-2 pof-items-table">
                    <thead>
                        <tr>
                            <th style="min-width: 260px;">Produk</th>
                            <th style="width: 80px;">Satuan</th>
                            <th style="width: 130px;" class="text-end">Kuantitas</th>
                            <th style="width: 180px;" class="text-end">Harga Satuan</th>
                            <th style="width: 170px;" class="text-end">Subtotal</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm" id="addRow">
                <i class="bi bi-plus-lg me-1"></i>Tambah Baris
            </button>
        </section>

        <div class="pof-total-row">
            <span class="pof-total-label">Total Pesanan</span>
            <span class="pof-total-value" id="grandTotal">Rp 0</span>
        </div>

        <div class="pof-foot">
            <button type="submit" class="btn btn-primary px-4" <?= $cannotSave ? 'disabled' : '' ?>>
                <i class="bi bi-check2-circle me-1"></i>Simpan PO
            </button>
            <a href="<?= site_url('purchase-orders') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr class="item-row">
        <td style="min-width: 260px;">
            <select class="js-product" name="items[__IDX__][product_id]" autocomplete="off"></select>
            <div class="form-text js-capacity"></div>
            <div class="invalid-feedback d-block js-err-product_id"></div>
        </td>
        <td class="js-unit text-muted text-center">-</td>
        <td>
            <input type="text" inputmode="numeric" autocomplete="off"
                class="form-control form-control-sm text-end js-qty" name="items[__IDX__][quantity]" placeholder="0">
            <div class="invalid-feedback d-block js-err-quantity"></div>
        </td>
        <td>
            <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control form-control-sm text-end js-price" name="items[__IDX__][unit_price]"
                placeholder="0">
            <div class="invalid-feedback d-block js-err-unit_price"></div>
        </td>
        <td class="text-end text-nowrap fw-bold js-subtotal">Rp 0</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger js-remove" title="Hapus baris"
                aria-label="Hapus baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
(function() {
    var body = document.getElementById('itemsBody');
    var tplHtml = document.getElementById('rowTemplate').innerHTML;
    var totalCell = document.getElementById('grandTotal');
    var initial =
        <?= json_encode($oldRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var rowErrors =
        <?= json_encode($rowErrors, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var preselect =
        <?= json_encode($preselect, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var nextIdx = 0;

    // Produk: dikirim sekali dari server, dicari berdasarkan id
    var products =
        <?= json_encode($productsJs, $jsonFlags) ?>;
    var productMap = {};
    products.forEach(function(p) {
        productMap[p.id] = p;
    });

    // Supplier: dropdown yang bisa diketik
    new TomSelect('#supplier_id', {
        plugins: ['dropdown_input'], // kotak cari di dalam daftar, field tampil seperti select biasa
        maxOptions: 100,
        placeholder: '- Pilih supplier -',
        render: {
            no_results: function() {
                return '<div class="no-results">Supplier tidak ditemukan</div>';
            }
        }
    });

    function rupiah(cents) {
        return 'Rp ' + (cents / 100).toLocaleString('id-ID', {
            maximumFractionDigits: 2
        });
    }

    function lineCents(tr) {
        var q = tr.querySelector('.js-qty').value.trim();
        var p = tr.querySelector('.js-price').value.trim();
        if (!/^[1-9]\d*$/.test(q) || !/^\d+(\.\d{1,2})?$/.test(p)) {
            return 0;
        }
        return parseInt(q, 10) * Math.round(parseFloat(p) * 100);
    }

    function recalc() {
        var total = 0;
        body.querySelectorAll('tr.item-row').forEach(function(tr) {
            var c = lineCents(tr);
            tr.querySelector('.js-subtotal').textContent = rupiah(c);
            total += c;
        });
        totalCell.textContent = rupiah(total);
    }

    function setError(tr, field, message) {
        var box = tr.querySelector('.js-err-' + field);
        if (box) box.textContent = message || '';
        var input = field === 'product_id' ? tr.querySelector('.js-product') :
            field === 'quantity' ? tr.querySelector('.js-qty') : tr.querySelector('.js-price');
        if (input) input.classList.toggle('is-invalid', !!message);
        if (field === 'product_id') {
            var w = tr.querySelector('.ts-wrapper');
            if (w) w.classList.toggle('is-invalid', !!message);
        }
    }

    function selectedProduct(tr) {
        return productMap[tr.querySelector('.js-product').value] || null;
    }

    function syncCapacity(tr) {
        var p = selectedProduct(tr);
        var box = tr.querySelector('.js-capacity');

        if (!box) return;
        if (!p || p.available === null || p.max === null) {
            box.textContent = '';
            box.className = 'form-text js-capacity';
            return;
        }

        var a = p.available;
        var max = p.max;
        if (a <= 0) {
            box.textContent = '⚠ Stok sudah mencapai batas maksimal.';
            box.className = 'form-text text-danger fw-semibold js-capacity';
        } else {
            box.textContent = 'Sisa kapasitas: ' + a + ' dari ' + max;
            box.className = 'form-text js-capacity';
        }
    }

    function syncUnit(tr) {
        var p = selectedProduct(tr);
        tr.querySelector('.js-unit').textContent = p ? p.unit : '-';
        syncCapacity(tr);
    }

    function addRow(idx, data, errs) {
        if (idx === null || idx === undefined) idx = nextIdx;
        var n = parseInt(idx, 10);
        if (!isNaN(n) && n >= nextIdx) nextIdx = n + 1;

        var holder = document.createElement('template');
        holder.innerHTML = tplHtml.split('__IDX__').join(String(idx));
        var tr = holder.content.firstElementChild;
        body.appendChild(tr);

        var sel = tr.querySelector('.js-product');
        var qty = tr.querySelector('.js-qty');
        var prc = tr.querySelector('.js-price');

        // Dropdown produk yang bisa diketik (cari berdasarkan kode atau nama)
        tr._ts = new TomSelect(sel, {
            plugins: ['dropdown_input'],
            options: products,
            items: (data && data.product_id) ? [String(data.product_id)] : [],
            valueField: 'id',
            labelField: 'label',
            searchField: ['code', 'name'],
            maxOptions: 50,
            create: false,
            dropdownParent: 'body', // agar tidak terpotong oleh .table-responsive
            placeholder: '- Pilih produk -',
            render: {
                option: function(p, esc) {
                    var sub = '';
                    if (p.max !== null && p.available !== null) {
                        sub = (p.available <= 0) ?
                            '<div class="pof-ts-sub is-full">Stok sudah mencapai batas maksimal</div>' :
                            '<div class="pof-ts-sub">Tersisa ' + p.available + ' dari ' + p.max + ' ' +
                            esc(p.unit) + '</div>';
                    }
                    return '<div><div class="fw-semibold">' + esc(p.code) + ' - ' + esc(p.name) +
                        '</div>' + sub + '</div>';
                },
                item: function(p, esc) {
                    return '<div>' + esc(p.label) + '</div>';
                },
                no_results: function() {
                    return '<div class="no-results">Produk tidak ditemukan</div>';
                }
            }
        });

        if (data) {
            qty.value = data.quantity || '';
            prc.value = data.unit_price || '';
        }
        syncUnit(tr);
        tr.dataset.auto = '';

        if (errs) {
            Object.keys(errs).forEach(function(f) {
                setError(tr, f, errs[f]);
            });
        }

        sel.addEventListener('change', function() {
            syncUnit(tr);
            var p = productMap[sel.value];
            var def = p ? p.price : '';
            if (prc.value.trim() === '' || prc.value === tr.dataset.auto) {
                prc.value = def;
                tr.dataset.auto = def;
            }
            setError(tr, 'product_id', '');
            recalc();
        });
        qty.addEventListener('input', function() {
            setError(tr, 'quantity', '');
            recalc();
        });
        prc.addEventListener('input', function() {
            setError(tr, 'unit_price', '');
            recalc();
        });
        tr.querySelector('.js-remove').addEventListener('click', function() {
            if (tr._ts) tr._ts.destroy();
            tr.remove();
            if (!body.querySelector('tr.item-row')) addRow();
            recalc();
        });
        return tr;
    }

    document.getElementById('addRow').addEventListener('click', function() {
        addRow()._ts.focus();
    });

    var keys = Object.keys(initial || {});
    if (keys.length === 0) {
        var firstTr = addRow();
        if (preselect && preselect.id) {
            firstTr._ts.setValue(String(preselect.id)); // memicu event change -> satuan & harga terisi
            firstTr.querySelector('.js-qty').focus();
        }
    } else {
        keys.forEach(function(k) {
            addRow(k, initial[k], rowErrors[k]);
        });
    }
    recalc();
})();
</script>

<?= $this->endSection() ?>