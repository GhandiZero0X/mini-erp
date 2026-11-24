<?php
/**
 * UI-08 Form Purchase Order. Variabel: $title, $suppliers, $products, $action, $today, $errors, $old, $preselect.
 * $preselect: baris produk terpilih dari dashboard (opsional, mis. tombol "Buat PO" di /dashboard).
 * Baris detail dinamis ditangani JavaScript di bawah. Subtotal/total di layar hanya tampilan;
 * server selalu menghitung ulang dari kuantitas x harga satuan (BR-10).
 */
$this->extend('layouts/main');

$supplierSel = (int) ($old['supplier_id'] ?? 0);
$dateVal     = (string) (($old['po_date'] ?? '') !== '' ? $old['po_date'] : $today);
$oldRows     = (isset($old['items']) && is_array($old['items'])) ? $old['items'] : [];
$rowErrors   = (isset($errors['item_rows']) && is_array($errors['item_rows'])) ? $errors['item_rows'] : [];
$cannotSave  = $suppliers === [] || $products === [];
$preselect   = $preselect ?? null;                            // ← BARU

// Harga default dari harga beli produk; ".00" disingkat
$priceText = static function ($v): string {
    $v = (string) $v;

    return str_ends_with($v, '.00') ? substr($v, 0, -3) : $v;
};

$err = static fn (string $f): string => isset($errors[$f]) ? '<div class="invalid-feedback d-block">' . esc($errors[$f]) . '</div>' : '';
$cls = static fn (string $f): string => isset($errors[$f]) ? ' is-invalid' : '';

// Opsi produk dipakai ulang di setiap baris.
// data-available = sisa kapasitas (max_stock - current - pending) bila ada; '' bila tanpa batas atas.
ob_start();
?>
<option value="">- Pilih produk -</option>
<?php foreach ($products as $p):
    $max       = $p['max_stock'] ?? null;
    $available = $p['available_room'] ?? null;
    $hasLimit  = $max !== null;
    $label     = $p['code'] . ' - ' . $p['name'];
    if ($hasLimit) {
        $label .= ' (tersisa ' . (int) $available . ' dari ' . (int) $max . ')';
    }
?>
<option value="<?= (int) $p['id'] ?>" data-unit="<?= esc($p['unit'], 'attr') ?>"
    data-price="<?= esc($priceText($p['purchase_price']), 'attr') ?>" data-max="<?= $hasLimit ? (int) $max : '' ?>"
    data-available="<?= $hasLimit ? (int) $available : '' ?>">
    <?= esc($label) ?>
</option>
<?php endforeach; ?>
<?php $productOptions = ob_get_clean(); ?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3"><?= esc($title) ?></h1>

<?php if ($cannotSave): ?>
<div class="alert alert-warning">
    Purchase Order memerlukan minimal satu supplier dan satu produk pada master data.
    <a href="<?= site_url('suppliers') ?>" class="alert-link">Supplier</a> |
    <a href="<?= site_url('products') ?>" class="alert-link">Produk</a>
</div>
<?php endif; ?>

<form method="post" action="<?= esc($action, 'attr') ?>" class="card card-body" novalidate>
    <?= csrf_field() ?>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Nomor PO</label>
            <input type="text" class="form-control" value="Otomatis saat disimpan" disabled>
        </div>
        <div class="col-md-4 mb-3">
            <label for="po_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" id="po_date" name="po_date" class="form-control<?= $cls('po_date') ?>"
                value="<?= esc($dateVal, 'attr') ?>">
            <?= $err('po_date') ?>
        </div>
        <div class="col-md-4 mb-3">
            <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
            <select id="supplier_id" name="supplier_id" class="form-select<?= $cls('supplier_id') ?>">
                <option value="">- Pilih supplier -</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= $supplierSel === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= esc($s['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?= $err('supplier_id') ?>
        </div>
    </div>

    <h2 class="h6 mt-2">Detail Produk</h2>
    <?= $err('items') ?>

    <div class="table-responsive mb-2">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produk</th>
                    <th style="width: 80px;">Satuan</th>
                    <th style="width: 130px;" class="text-end">Kuantitas</th>
                    <th style="width: 180px;" class="text-end">Harga Satuan (Rp)</th>
                    <th style="width: 170px;" class="text-end">Subtotal</th>
                    <th style="width: 50px;"></th>
                </tr>
            </thead>
            <tbody id="itemsBody"></tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end text-nowrap" id="grandTotal">Rp 0</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mb-4">
        <button type="button" class="btn btn-outline-primary btn-sm" id="addRow">
            <i class="bi bi-plus-lg me-1"></i>Tambah Baris
        </button>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" <?= $cannotSave ? 'disabled' : '' ?>>Simpan</button>
        <a href="<?= site_url('purchase-orders') ?>" class="btn btn-secondary">Batal</a>
    </div>
</form>

<template id="rowTemplate">
    <tr class="item-row">
        <td style="min-width: 260px;">
            <select class="form-select form-select-sm js-product" name="items[__IDX__][product_id]">
                <?= $productOptions ?>
            </select>
            <div class="form-text js-capacity"></div>
            <div class="invalid-feedback d-block js-err-product_id"></div>
        </td>
        <td class="js-unit text-muted">-</td>
        <td>
            <input type="text" inputmode="numeric" autocomplete="off"
                class="form-control form-control-sm text-end js-qty" name="items[__IDX__][quantity]">
            <div class="invalid-feedback d-block js-err-quantity"></div>
        </td>
        <td>
            <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control form-control-sm text-end js-price" name="items[__IDX__][unit_price]">
            <div class="invalid-feedback d-block js-err-unit_price"></div>
        </td>
        <td class="text-end text-nowrap js-subtotal">Rp 0</td>
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
        <?= json_encode($preselect, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>; // ← BARU
    var nextIdx = 0;

    // Hanya tampilan. Server menghitung ulang (BR-10).
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
        if (box) {
            box.textContent = message || '';
        }
        var input = field === 'product_id' ? tr.querySelector('.js-product') :
            field === 'quantity' ? tr.querySelector('.js-qty') : tr.querySelector('.js-price');
        if (input) {
            input.classList.toggle('is-invalid', !!message);
        }
    }

    // ← BARU: tampilkan info kapasitas di bawah dropdown
    function syncCapacity(tr) {
        var opt = tr.querySelector('.js-product').selectedOptions[0];
        var box = tr.querySelector('.js-capacity');
        var avail = opt ? opt.dataset.available : '';
        var max = opt ? opt.dataset.max : '';

        if (!box) {
            return;
        }
        if (avail === '' || avail === undefined || max === '') {
            box.textContent = '';
            box.className = 'form-text js-capacity';
            return;
        }

        var a = parseInt(avail, 10);
        if (a <= 0) {
            box.textContent = '⚠ Stok sudah mencapai batas maksimal.';
            box.className = 'form-text text-danger fw-semibold js-capacity';
        } else {
            box.textContent = 'Sisa kapasitas: ' + a + ' dari ' + max;
            box.className = 'form-text js-capacity';
        }
    }

    function syncUnit(tr) {
        var opt = tr.querySelector('.js-product').selectedOptions[0];
        tr.querySelector('.js-unit').textContent = (opt && opt.dataset.unit) ? opt.dataset.unit : '-';
        syncCapacity(tr);
    }

    function addRow(idx, data, errs) {
        if (idx === null || idx === undefined) {
            idx = nextIdx;
        }
        var n = parseInt(idx, 10);
        if (!isNaN(n) && n >= nextIdx) {
            nextIdx = n + 1;
        }

        var holder = document.createElement('template');
        holder.innerHTML = tplHtml.split('__IDX__').join(String(idx));
        var tr = holder.content.firstElementChild;
        body.appendChild(tr);

        var sel = tr.querySelector('.js-product');
        var qty = tr.querySelector('.js-qty');
        var prc = tr.querySelector('.js-price');

        if (data) {
            sel.value = String(data.product_id || '');
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
            var opt = sel.selectedOptions[0];
            var def = (opt && opt.dataset.price) ? opt.dataset.price : '';
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
            tr.remove();
            if (!body.querySelector('tr.item-row')) {
                addRow();
            }
            recalc();
        });
        return tr;
    }

    document.getElementById('addRow').addEventListener('click', function() {
        addRow().querySelector('.js-product').focus();
    });

    var keys = Object.keys(initial || {});
    if (keys.length === 0) {
        // ← BARU: pre-select produk dari dashboard bila ada ?product_id=X
        var firstTr = addRow();
        if (preselect && preselect.id) {
            var sel = firstTr.querySelector('.js-product');
            sel.value = String(preselect.id);
            // Trigger change supaya unit, harga, dan kapasitas ikut terisi
            sel.dispatchEvent(new Event('change'));
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