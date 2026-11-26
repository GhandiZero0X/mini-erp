<?php
/**
 * UI-12 Form Sales Order. Variabel: $title, $customers, $products, $action, $today, $errors, $old.
 * Baris detail dinamis + penanda Cukup/Kurang. Subtotal/total di layar hanya tampilan;
 * server selalu menghitung ulang (BR-10).
 */
$this->extend('layouts/main');

$customerSel = (int) ($old['customer_id'] ?? 0);
$dateVal     = (string) (($old['so_date'] ?? '') !== '' ? $old['so_date'] : $today);
$oldRows     = (isset($old['items']) && is_array($old['items'])) ? array_values($old['items']) : [];
$rowErrors   = (isset($errors['item_rows']) && is_array($errors['item_rows'])) ? $errors['item_rows'] : [];
$cannotSave  = $customers === [] || $products === [];

$priceText = static function ($v): string {
    $v = (string) $v;
    return str_ends_with($v, '.00') ? substr($v, 0, -3) : $v;
};

$err = static fn (string $f): string => isset($errors[$f]) ? '<div class="invalid-feedback d-block">' . esc($errors[$f]) . '</div>' : '';
$cls = static fn (string $f): string => isset($errors[$f]) ? ' is-invalid' : '';

ob_start();
?>
<option value="">- Pilih produk -</option>
<?php foreach ($products as $p): ?>
<option value="<?= (int) $p['id'] ?>" data-unit="<?= esc($p['unit'], 'attr') ?>"
    data-price="<?= esc($priceText($p['sale_price']), 'attr') ?>" data-stock="<?= (int) $p['stock_available'] ?>">
    <?= esc($p['code'] . ' - ' . $p['name']) ?></option>
<?php endforeach; ?>
<?php $productOptions = ob_get_clean(); ?>

<?= $this->section('content') ?>

<style>
/* Prefiks "so-" */
.so-fhead {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.so-fhead::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.so-fhead>* {
    position: relative;
    z-index: 1;
}

.so-fhead-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.so-fhead h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.so-fhead p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.so-fhead .btn-back {
    border: 0;
    color: #fff;
    background: rgba(255, 255, 255, .18);
}

.so-fhead .btn-back:hover {
    background: rgba(255, 255, 255, .3);
}

.so-form {
    margin-top: 1.1rem;
}

/* Seksi form */
.so-sec {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--app-line);
}

.so-sec:last-of-type {
    border-bottom: 0;
}

.so-sec-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.1rem;
}

.so-sec-ico {
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

.so-sec-head h3 {
    font-size: .98rem;
    font-weight: 800;
    margin: 0;
}

.so-sec-head p {
    margin: 0;
    font-size: .8rem;
    color: var(--app-muted);
}

.so-form .form-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .4rem;
}

.so-form .form-text {
    font-size: .78rem;
}

.so-form .input-group-text {
    background: #f8f9fd;
    border-color: #dfe3ee;
    color: var(--app-muted);
    font-weight: 700;
}

.so-form .form-control[disabled] {
    background: #f4f5fa;
    color: #6b7385;
    cursor: not-allowed;
}

/* Tabel item */
.so-items-table th {
    background: #f8f9fd;
    color: var(--app-muted);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 1px solid var(--app-line);
    white-space: nowrap;
}

.so-items-table td {
    vertical-align: middle;
    padding: .5rem .5rem;
}

.so-items-table .form-control-sm,
.so-items-table .form-select-sm {
    font-size: .85rem;
}

/* Footer total di tabel */
.so-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

.so-total-label {
    font-size: .95rem;
    font-weight: 700;
    color: var(--app-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.so-total-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

/* Footer action */
.so-foot {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1.15rem 1.75rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

/* Alert custom */
.so-alert-warn {
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

.so-alert-warn i {
    flex: 0 0 auto;
    margin-top: .1rem;
}

.so-alert-warn a {
    color: #946500;
    font-weight: 700;
    text-decoration: underline;
}

@media (max-width: 767.98px) {
    .so-fhead {
        padding: 1.1rem;
    }

    .so-sec,
    .so-foot {
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }
}
</style>

<div class="so-fhead d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="so-fhead-ico" aria-hidden="true"><i class="bi bi-receipt"></i></span>
        <div>
            <h2><?= esc($title) ?></h2>
            <p>Lengkapi data pesanan dan daftar produk yang akan dijual.</p>
        </div>
    </div>
    <a href="<?= site_url('sales-orders') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>
</div>

<div class="so-form">
    <?php if ($cannotSave): ?>
    <div class="so-alert-warn">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            Sales Order memerlukan minimal satu customer dan satu produk pada master data.
            <a href="<?= site_url('customers') ?>">Customer</a> &middot;
            <a href="<?= site_url('products') ?>">Produk</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (session('error')): ?>
    <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-circle me-1"></i><?= esc(session('error')) ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= esc($action, 'attr') ?>" class="card" novalidate>
        <?= csrf_field() ?>

        <!-- 1. Informasi SO -->
        <section class="so-sec">
            <div class="so-sec-head">
                <span class="so-sec-ico"><i class="bi bi-info-circle"></i></span>
                <div>
                    <h3>Informasi Sales Order</h3>
                    <p>Tanggal dan customer tujuan pesanan.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nomor SO</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                        <input type="text" class="form-control" value="Otomatis saat disimpan" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="so_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        <input type="date" id="so_date" name="so_date" class="form-control<?= $cls('so_date') ?>"
                            value="<?= esc($dateVal, 'attr') ?>">
                    </div>
                    <?= $err('so_date') ?>
                </div>
                <div class="col-md-4">
                    <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <select id="customer_id" name="customer_id" class="form-select<?= $cls('customer_id') ?>">
                            <option value="">- Pilih customer -</option>
                            <?php foreach ($customers as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= $customerSel === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?= $err('customer_id') ?>
                </div>
            </div>
        </section>

        <!-- 2. Detail Produk -->
        <section class="so-sec">
            <div class="so-sec-head">
                <span class="so-sec-ico"><i class="bi bi-cart-plus"></i></span>
                <div>
                    <h3>Detail Produk</h3>
                    <p>Tambahkan produk yang dipesan beserta kuantitas dan harga.</p>
                </div>
            </div>

            <?= $err('items') ?>

            <div class="table-responsive">
                <table class="table align-middle mb-2 so-items-table">
                    <thead>
                        <tr>
                            <th style="min-width: 260px;">Produk</th>
                            <th style="width: 80px;">Satuan</th>
                            <th style="width: 130px;" class="text-end">Kuantitas</th>
                            <th style="width: 140px;" class="text-center">Stok</th>
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

        <div class="so-total-row">
            <span class="so-total-label">Total Pesanan</span>
            <span class="so-total-value" id="grandTotal">Rp 0</span>
        </div>

        <div class="so-foot">
            <button type="submit" class="btn btn-primary px-4" <?= $cannotSave ? 'disabled' : '' ?>>
                <i class="bi bi-check2-circle me-1"></i>Simpan SO
            </button>
            <a href="<?= site_url('sales-orders') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr class="item-row">
        <td style="min-width: 260px;">
            <select class="form-select form-select-sm js-product" name="items[__IDX__][product_id]">
                <?= $productOptions ?>
            </select>
            <div class="invalid-feedback d-block js-err-product_id"></div>
        </td>
        <td class="js-unit text-muted text-center">-</td>
        <td>
            <input type="text" inputmode="numeric" autocomplete="off"
                class="form-control form-control-sm text-end js-qty" name="items[__IDX__][quantity]" placeholder="0">
            <div class="invalid-feedback d-block js-err-quantity"></div>
        </td>
        <td class="text-center">
            <span class="badge text-bg-secondary js-stock-badge">-</span>
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
    var nextIdx = 0;

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
    }

    function syncUnit(tr) {
        var opt = tr.querySelector('.js-product').selectedOptions[0];
        tr.querySelector('.js-unit').textContent = (opt && opt.dataset.unit) ? opt.dataset.unit : '-';
    }

    function checkStock(tr) {
        var opt = tr.querySelector('.js-product').selectedOptions[0];
        var badge = tr.querySelector('.js-stock-badge');
        if (!opt || !opt.value) {
            badge.textContent = '-';
            badge.className = 'badge text-bg-secondary js-stock-badge';
            return;
        }
        var stock = parseInt(opt.dataset.stock || '0', 10);
        var qty = parseInt(tr.querySelector('.js-qty').value || '0', 10);
        if (qty > stock) {
            badge.textContent = 'Kurang (' + stock + ')';
            badge.className = 'badge text-bg-warning js-stock-badge';
        } else {
            badge.textContent = 'Cukup (' + stock + ')';
            badge.className = 'badge text-bg-success js-stock-badge';
        }
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
            checkStock(tr);
        });
        qty.addEventListener('input', function() {
            setError(tr, 'quantity', '');
            recalc();
            checkStock(tr);
        });
        prc.addEventListener('input', function() {
            setError(tr, 'unit_price', '');
            recalc();
        });
        tr.querySelector('.js-remove').addEventListener('click', function() {
            tr.remove();
            if (!body.querySelector('tr.item-row')) addRow();
            recalc();
        });

        checkStock(tr);
        return tr;
    }

    document.getElementById('addRow').addEventListener('click', function() {
        addRow().querySelector('.js-product').focus();
    });

    var keys = Object.keys(initial || {});
    if (keys.length === 0) {
        addRow();
    } else {
        keys.forEach(function(k) {
            addRow(k, initial[k], rowErrors[k]);
        });
    }
    recalc();
})();
</script>

<?= $this->endSection() ?>