<?php
/** Form tambah ($category null) dan ubah kategori (UI-03). */
$value   = $old['name'] ?? ($category['name'] ?? '');
$invalid = isset($errors['name']) ? ' is-invalid' : '';
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h1 class="h4 mb-3"><?= esc($title) ?></h1>

<div class="card shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" autocomplete="off" novalidate>
            <?= csrf_field() ?>

            <div class="mb-4">
                <label for="name" class="form-label">Nama Kategori</label>
                <input type="text" class="form-control<?= $invalid ?>" id="name" name="name"
                       maxlength="100" value="<?= esc($value) ?>" autofocus>
                <div class="invalid-feedback"><?= esc($errors['name'] ?? '') ?></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="<?= site_url('categories') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
