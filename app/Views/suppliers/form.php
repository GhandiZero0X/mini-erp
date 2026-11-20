<?php
/** UI-05 Supplier (form tambah/ubah). Variabel: $supplier (null bila tambah), $action, $errors, $old. */
$this->extend('layouts/main');

// Nilai isian: input lama (setelah validasi gagal) > data tersimpan > kosong
$val = static function (string $key) use ($old, $supplier): string {
    return array_key_exists($key, $old) ? (string) $old[$key] : (string) ($supplier[$key] ?? '');
};

$err = static fn (string $f): string => isset($errors[$f]) ? '<div class="invalid-feedback d-block">' . esc($errors[$f]) . '</div>' : '';
$cls = static fn (string $f): string => isset($errors[$f]) ? ' is-invalid' : '';
?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3"><?= esc($title) ?></h1>

<form method="post" action="<?= esc($action, 'attr') ?>" class="card card-body" style="max-width: 720px" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" maxlength="150" autocomplete="off" autofocus
               class="form-control<?= $cls('name') ?>" value="<?= esc($val('name'), 'attr') ?>">
        <?= $err('name') ?>
    </div>

    <div class="mb-3">
        <label for="address" class="form-label">Alamat</label>
        <textarea id="address" name="address" rows="3" maxlength="500"
                  class="form-control<?= $cls('address') ?>"><?= esc($val('address')) ?></textarea>
        <?= $err('address') ?>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="phone" class="form-label">Telepon</label>
            <input type="text" id="phone" name="phone" maxlength="20" inputmode="tel" autocomplete="off"
                   class="form-control<?= $cls('phone') ?>" value="<?= esc($val('phone'), 'attr') ?>">
            <?= $err('phone') ?>
            <div class="form-text">Opsional. Angka, spasi, "+" dan "-", 8-15 digit.</div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="text" id="email" name="email" maxlength="100" inputmode="email" autocomplete="off"
                   class="form-control<?= $cls('email') ?>" value="<?= esc($val('email'), 'attr') ?>">
            <?= $err('email') ?>
            <div class="form-text">Opsional.</div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?= site_url('suppliers') ?>" class="btn btn-secondary">Batal</a>
    </div>
</form>

<?= $this->endSection() ?>
