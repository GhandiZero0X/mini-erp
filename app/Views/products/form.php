<?php
/**
 * UI-04 Produk (form tambah/ubah). Variabel: $product (null bila tambah), $categories, $action, $errors, $old.
 * CATATAN: samakan nama layout/section dengan Views/categories/form.php milik Anda.
 * Tidak ada kolom stok: produk baru berstok 0, stok hanya berubah lewat GR dan Delivery (BR-06).
 */
$this->extend('layouts/main');

$isEdit = $product !== null;
$locked = $isEdit && ! empty($product['code_locked']);

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

$err = static fn (string $f): string => isset($errors[$f]) ? '<div class="invalid-feedback d-block">' . esc($errors[$f]) . '</div>' : '';
$cls = static fn (string $f): string => isset($errors[$f]) ? ' is-invalid' : '';

$selectedCategory = (int) $val('category_id');
$selectedUnit     = (int) $val('unit_id');
?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3"><?= esc($title) ?></h1>

<?php if ($categories === []): ?>
<div class="alert alert-warning">
    Belum ada kategori. Tambahkan kategori terlebih dahulu sebelum membuat produk.
    <a href="<?= site_url('categories/new') ?>" class="alert-link">Tambah kategori</a>
</div>
<?php endif; ?>

<?php if ($units === []): ?>
<div class="alert alert-warning">
    Belum ada satuan. Tambahkan satuan terlebih dahulu sebelum membuat produk.
    <a href="<?= site_url('units/new') ?>" class="alert-link">Tambah satuan</a>
</div>
<?php endif; ?>

<form method="post" action="<?= esc($action, 'attr') ?>" class="card card-body" style="max-width: 720px" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="code" class="form-label">Kode <span class="text-danger">*</span></label>
        <input type="text" id="code" name="code" maxlength="30" autocomplete="off"
            class="form-control<?= $cls('code') ?>" value="<?= esc($val('code'), 'attr') ?>"
            <?= $locked ? 'readonly' : 'autofocus' ?>>
        <?= $err('code') ?>
        <?php if ($locked): ?>
        <div class="form-text">Kode tidak dapat diubah karena produk sudah digunakan pada transaksi.</div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" maxlength="150" autocomplete="off"
            class="form-control<?= $cls('name') ?>" value="<?= esc($val('name'), 'attr') ?>"
            <?= $locked ? 'autofocus' : '' ?>>
        <?= $err('name') ?>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
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
        <div class="col-md-6 mb-3">
            <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
            <select id="category_id" name="category_id" class="form-select<?= $cls('category_id') ?>">
                <option value="">- Pilih kategori -</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $selectedCategory === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= esc($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?= $err('category_id') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="purchase_price" class="form-label">Harga Beli (Rp) <span class="text-danger">*</span></label>
            <input type="text" inputmode="decimal" id="purchase_price" name="purchase_price" autocomplete="off"
                class="form-control<?= $cls('purchase_price') ?>" value="<?= esc($val('purchase_price'), 'attr') ?>">
            <?= $err('purchase_price') ?>
            <div class="form-text">Angka saja, tanpa pemisah ribuan. Desimal dengan titik (maks. 2 angka).</div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="selling_price" class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
            <input type="text" inputmode="decimal" id="selling_price" name="selling_price" autocomplete="off"
                class="form-control<?= $cls('selling_price') ?>" value="<?= esc($val('selling_price'), 'attr') ?>">
            <?= $err('selling_price') ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"
            <?= ($categories === [] || $units === []) ? 'disabled' : '' ?>>Simpan</button>
        <a href="<?= site_url('products') ?>" class="btn btn-secondary">Batal</a>
    </div>
</form>

<?= $this->endSection() ?>