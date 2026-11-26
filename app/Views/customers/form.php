<?php
/** UI-06 Customer (form tambah/ubah). Variabel: $customer (null bila tambah), $action, $errors, $old. */
$this->extend('layouts/main');

// Nilai isian: input lama (setelah validasi gagal) > data tersimpan > kosong
$val = static function (string $key) use ($old, $customer): string {
    return array_key_exists($key, $old) ? (string) $old[$key] : (string) ($customer[$key] ?? '');
};

$err = static fn (string $f): string => isset($errors[$f]) ? '<div class="invalid-feedback d-block">' . esc($errors[$f]) . '</div>' : '';
$cls = static fn (string $f): string => isset($errors[$f]) ? ' is-invalid' : '';

$isEdit = $customer !== null;
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "cus-" */
.cus-fhead {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.cus-fhead::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.cus-fhead>* {
    position: relative;
    z-index: 1;
}

.cus-fhead-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.cus-fhead h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.cus-fhead p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.cus-fhead .btn-back {
    border: 0;
    color: #fff;
    background: rgba(255, 255, 255, .18);
}

.cus-fhead .btn-back:hover {
    background: rgba(255, 255, 255, .3);
}

.cus-form {
    max-width: 800px;
    margin-top: 1.1rem;
}

/* Seksi form */
.cus-sec {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--app-line);
}

.cus-sec:last-of-type {
    border-bottom: 0;
}

.cus-sec-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.1rem;
}

.cus-sec-ico {
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

.cus-sec-head h3 {
    font-size: .98rem;
    font-weight: 800;
    margin: 0;
}

.cus-sec-head p {
    margin: 0;
    font-size: .8rem;
    color: var(--app-muted);
}

.cus-form .form-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .4rem;
}

.cus-form .form-text {
    font-size: .78rem;
}

.cus-form .input-group-text {
    background: #f8f9fd;
    border-color: #dfe3ee;
    color: var(--app-muted);
    font-weight: 700;
}

.cus-foot {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1.15rem 1.75rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

@media (max-width: 575.98px) {
    .cus-fhead {
        padding: 1.1rem;
    }

    .cus-sec,
    .cus-foot {
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }
}
</style>

<div class="cus-fhead d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="cus-fhead-ico" aria-hidden="true">
            <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus' ?>"></i>
        </span>
        <div>
            <h2><?= esc($title) ?></h2>
            <p><?= $isEdit ? 'Perbarui data kontak dan alamat customer.' : 'Lengkapi data customer baru untuk keperluan penjualan.' ?>
            </p>
        </div>
    </div>
    <a href="<?= site_url('customers') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>
</div>

<div class="cus-form">
    <form method="post" action="<?= esc($action, 'attr') ?>" class="card" novalidate>
        <?= csrf_field() ?>

        <!-- 1. Identitas -->
        <section class="cus-sec">
            <div class="cus-sec-head">
                <span class="cus-sec-ico"><i class="bi bi-person-badge"></i></span>
                <div>
                    <h3>Identitas Customer</h3>
                    <p>Nama lengkap pelanggan atau nama perusahaan.</p>
                </div>
            </div>

            <div class="mb-0">
                <label for="name" class="form-label">Nama Customer <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" id="name" name="name" maxlength="150" autocomplete="off" autofocus
                        class="form-control<?= $cls('name') ?>" value="<?= esc($val('name'), 'attr') ?>"
                        placeholder="Contoh: Budi Santoso / PT Sejahtera">
                </div>
                <?= $err('name') ?>
            </div>
        </section>

        <!-- 2. Kontak & Alamat -->
        <section class="cus-sec">
            <div class="cus-sec-head">
                <span class="cus-sec-ico"><i class="bi bi-geo-alt"></i></span>
                <div>
                    <h3>Kontak & Alamat</h3>
                    <p>Informasi komunikasi dan alamat pengiriman.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">Telepon</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" id="phone" name="phone" maxlength="20" inputmode="tel" autocomplete="off"
                            class="form-control<?= $cls('phone') ?>" value="<?= esc($val('phone'), 'attr') ?>"
                            placeholder="08xx...">
                    </div>
                    <?= $err('phone') ?>
                    <div class="form-text">Opsional. Angka, spasi, "+" dan "-", 8-15 digit.</div>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="text" id="email" name="email" maxlength="100" inputmode="email" autocomplete="off"
                            class="form-control<?= $cls('email') ?>" value="<?= esc($val('email'), 'attr') ?>"
                            placeholder="email@contoh.com">
                    </div>
                    <?= $err('email') ?>
                    <div class="form-text">Opsional.</div>
                </div>

                <div class="col-12">
                    <label for="address" class="form-label">Alamat Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-map"></i></span>
                        <textarea id="address" name="address" rows="3" maxlength="500"
                            class="form-control<?= $cls('address') ?>"
                            placeholder="Jalan, nomor, kota..."><?= esc($val('address')) ?></textarea>
                    </div>
                    <?= $err('address') ?>
                </div>
            </div>
        </section>

        <div class="cus-foot">
            <button type="submit" class="btn btn-primary px-4">
                <i
                    class="bi <?= $isEdit ? 'bi-check2-circle' : 'bi-plus-lg' ?> me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Customer' ?>
            </button>
            <a href="<?= site_url('customers') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>