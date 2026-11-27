<?php
/**
 * Master Satuan (form tambah/ubah). Hanya Admin.
 * @var array|null $unit    null = tambah, array = ubah
 * @var string     $action
 * @var array      $errors  field => pesan
 * @var array      $old     isian sebelumnya (setelah gagal validasi)
 */
$isEdit = $unit !== null;
$val    = static fn (string $f): string => (string) ($old[$f] ?? ($unit[$f] ?? ''));
$err    = static fn (string $f): ?string => $errors[$f] ?? null;
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* Prefiks "unf-" supaya tidak bentrok */
.unf-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.unf-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.unf-head>* {
    position: relative;
    z-index: 1;
}

.unf-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.unf-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.unf-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.unf-card {
    max-width: 640px;
}

.unf-card .form-label {
    font-weight: 700;
    font-size: .86rem;
}

.unf-card .form-text {
    font-size: .78rem;
}

.unf-code {
    text-transform: uppercase;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    letter-spacing: .05em;
}

.unf-req {
    color: #dc2626;
}
</style>

<div class="unf-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="unf-head-ico" aria-hidden="true"><i class="bi bi-rulers"></i></span>
        <div>
            <h2><?= $isEdit ? 'Ubah Satuan' : 'Tambah Satuan' ?></h2>
            <p><?= $isEdit ? 'Perbarui kode atau nama satuan.' : 'Isi kode dan nama satuan baru.' ?></p>
        </div>
    </div>
</div>

<div class="mb-3"></div>

<div class="card unf-card">
    <div class="card-body p-4">
        <form method="post" action="<?= esc($action, 'attr') ?>" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="code" class="form-label">Kode Satuan <span class="unf-req">*</span></label>
                <input type="text" id="code" name="code"
                    class="form-control unf-code<?= $err('code') ? ' is-invalid' : '' ?>" maxlength="20"
                    value="<?= esc($val('code'), 'attr') ?>" placeholder="Contoh: PCS" autocomplete="off" required
                    autofocus oninput="this.value = this.value.toUpperCase()">
                <?php if ($err('code')): ?>
                <div class="invalid-feedback"><?= esc($err('code')) ?></div>
                <?php else: ?>
                <div class="form-text">Huruf dan angka saja, tanpa spasi. Otomatis huruf kapital.</div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="name" class="form-label">Nama Satuan <span class="unf-req">*</span></label>
                <input type="text" id="name" name="name" class="form-control<?= $err('name') ? ' is-invalid' : '' ?>"
                    maxlength="50" value="<?= esc($val('name'), 'attr') ?>" placeholder="Contoh: Pieces"
                    autocomplete="off" required>
                <?php if ($err('name')): ?>
                <div class="invalid-feedback"><?= esc($err('name')) ?></div>
                <?php else: ?>
                <div class="form-text">Nama lengkap yang tampil pada pilihan satuan di data produk.</div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
                </button>
                <a href="<?= site_url('units') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
