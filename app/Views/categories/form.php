<?php
/**
 * Form tambah ($category null) dan ubah kategori (UI-03).
 *
 * @var string     $title
 * @var string     $action
 * @var array|null $category
 * @var array      $old
 * @var array      $errors
 */
$category = $category ?? null;
$old      = $old ?? [];
$errors   = $errors ?? [];
$isEdit   = $category !== null;
$value    = $old['name'] ?? ($category['name'] ?? '');
$invalid  = isset($errors['name']) ? ' is-invalid' : '';
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* Prefiks "cat-" supaya tidak bentrok dengan Bootstrap / dashboard (dx-) */
.cat-fhead {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.cat-fhead::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.cat-fhead>* {
    position: relative;
    z-index: 1;
}

.cat-fhead-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.cat-fhead h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.cat-fhead p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.cat-fhead .btn-back {
    border: 0;
    color: #fff;
    background: rgba(255, 255, 255, .18);
}

.cat-fhead .btn-back:hover {
    background: rgba(255, 255, 255, .3);
}

.cat-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
    gap: 1.1rem;
    margin-top: 1.1rem;
    align-items: start;
}

@media (max-width: 991.98px) {
    .cat-form-grid {
        grid-template-columns: 1fr;
    }
}

.cat-field-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .45rem;
}

.cat-input-wrap {
    position: relative;
}

.cat-input-wrap>i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    font-size: 1.1rem;
    pointer-events: none;
}

.cat-input-wrap .form-control {
    padding-left: 2.8rem;
}

.cat-help {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: .55rem;
    font-size: .8rem;
    color: var(--app-muted);
}

.cat-count {
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}

.cat-count.is-near {
    color: #b45309;
}

/* Pratinjau */
.cat-side-title {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #9aa1b5;
    margin-bottom: .75rem;
}

.cat-preview {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: .9rem 1rem;
    border: 1px dashed #cfd5e6;
    border-radius: 14px;
    background: #fafbff;
}

.cat-ico {
    flex: 0 0 auto;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: #eef0ff;
    color: #4338ca;
    font-size: 1rem;
    font-weight: 800;
}

.cat-preview-name {
    font-weight: 700;
    overflow-wrap: anywhere;
    min-width: 0;
}

.cat-preview-name.is-empty {
    color: #a3a9bb;
    font-weight: 600;
}

.cat-preview small {
    display: block;
    color: var(--app-muted);
    font-weight: 600;
}

.cat-tips {
    list-style: none;
    margin: 0;
    padding: 0;
}

.cat-tips li {
    display: flex;
    gap: .65rem;
    font-size: .85rem;
    color: #3b4358;
    margin-bottom: .7rem;
}

.cat-tips li:last-child {
    margin-bottom: 0;
}

.cat-tips i {
    flex: 0 0 auto;
    margin-top: .1rem;
    color: #4f46e5;
}

.cat-current {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--app-line);
    font-size: .82rem;
    color: var(--app-muted);
}

.cat-current b {
    color: var(--app-ink);
    overflow-wrap: anywhere;
}
</style>

<div class="cat-fhead d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="cat-fhead-ico" aria-hidden="true"><i
                class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-tag' ?>"></i></span>
        <div>
            <h2><?= esc($title) ?></h2>
            <p><?= $isEdit ? 'Perbarui nama kategori yang sudah ada.' : 'Tambahkan kategori baru untuk mengelompokkan produk.' ?>
            </p>
        </div>
    </div>
    <a href="<?= site_url('categories') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>
</div>

<div class="cat-form-grid">
    <div class="card">
        <div class="card-body p-4">
            <form method="post" action="<?= esc($action) ?>" id="catForm" autocomplete="off" novalidate>
                <?= csrf_field() ?>

                <div class="mb-4">
                    <label for="name" class="cat-field-label">Nama Kategori</label>
                    <div class="cat-input-wrap">
                        <i class="bi bi-tag" aria-hidden="true"></i>
                        <input type="text" class="form-control form-control-lg<?= $invalid ?>" id="name" name="name"
                            maxlength="100" value="<?= esc($value, 'attr') ?>" aria-describedby="nameHelp"
                            placeholder="Contoh: System Cooler" autofocus>
                        <div class="invalid-feedback"><?= esc($errors['name'] ?? '') ?></div>
                    </div>

                    <div id="nameHelp" class="cat-help">
                        <span>Nama harus unik, belum dipakai kategori lain.</span>
                        <span class="cat-count"><span id="nameCount">0</span>/100</span>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4" id="catSubmit">
                        <i
                            class="bi <?= $isEdit ? 'bi-check2-circle' : 'bi-plus-lg' ?> me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Kategori' ?>
                    </button>
                    <a href="<?= site_url('categories') ?>" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <aside class="card">
        <div class="card-body p-4">
            <div class="cat-side-title">Pratinjau</div>
            <div class="cat-preview" aria-live="polite">
                <span class="cat-ico" id="prevIco" aria-hidden="true">?</span>
                <div class="min-w-0">
                    <div class="cat-preview-name is-empty" id="prevName">Nama kategori</div>
                    <small>Begini tampilannya di daftar</small>
                </div>
            </div>

            <div class="cat-side-title mt-4">Tips penamaan</div>
            <ul class="cat-tips">
                <li><i class="bi bi-check2-circle"></i><span>Pakai nama yang singkat dan mudah dikenali, misalnya jenis
                        atau fungsi produk.</span></li>
                <li><i class="bi bi-check2-circle"></i><span>Hindari nama ganda atau hampir sama agar pencarian dan
                        laporan tidak membingungkan.</span></li>
                <li><i class="bi bi-check2-circle"></i><span>Kategori yang sudah dipakai produk tidak bisa dihapus,
                        tetapi namanya tetap bisa diubah.</span></li>
            </ul>

            <?php if ($isEdit): ?>
            <div class="cat-current">Nama saat ini: <b><?= esc((string) $category['name']) ?></b></div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<script>
(function() {
    'use strict';
    var input = document.getElementById('name');
    var count = document.getElementById('nameCount');
    var countWrap = count.parentNode;
    var prevName = document.getElementById('prevName');
    var prevIco = document.getElementById('prevIco');
    var form = document.getElementById('catForm');
    var btn = document.getElementById('catSubmit');

    function update() {
        var v = input.value.trim();
        count.textContent = input.value.length;
        countWrap.classList.toggle('is-near', input.value.length >= 90);
        prevName.textContent = v || 'Nama kategori';
        prevName.classList.toggle('is-empty', v === '');
        prevIco.textContent = v ? v.charAt(0).toUpperCase() : '?';
    }
    input.addEventListener('input', function() {
        update();
        input.classList.remove('is-invalid'); // kesalahan dari server hilang saat pengguna mengetik ulang
    });
    update();

    // Cegah klik ganda: nama kategori unik, simpan kedua akan gagal
    form.addEventListener('submit', function() {
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
    });
})();
</script>

<?= $this->endSection() ?>