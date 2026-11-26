<?php
/**
 * Form tambah (mode=create) dan ubah (mode=edit) pengguna (UI-23).
 * Saat ubah: username dikunci (VR-34) dan tidak ada kolom password (gunakan Reset Password).
 */
$isEdit = $mode === 'edit';
$val    = static fn (string $key, $fallback = '') => $old[$key] ?? $fallback;
$invalid = static fn (string $key) => isset($errors[$key]) ? ' is-invalid' : '';
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* Prefiks "usr-" */
.usr-fhead {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.usr-fhead::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.usr-fhead>* {
    position: relative;
    z-index: 1;
}

.usr-fhead-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.usr-fhead h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.usr-fhead p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.usr-fhead .btn-back {
    border: 0;
    color: #fff;
    background: rgba(255, 255, 255, .18);
}

.usr-fhead .btn-back:hover {
    background: rgba(255, 255, 255, .3);
}

.usr-form {
    max-width: 800px;
    margin-top: 1.1rem;
}

/* Seksi form */
.usr-sec {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--app-line);
}

.usr-sec:last-of-type {
    border-bottom: 0;
}

.usr-sec-head {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.1rem;
}

.usr-sec-ico {
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

.usr-sec-head h3 {
    font-size: .98rem;
    font-weight: 800;
    margin: 0;
}

.usr-sec-head p {
    margin: 0;
    font-size: .8rem;
    color: var(--app-muted);
}

.usr-form .form-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .4rem;
}

.usr-form .form-text {
    font-size: .78rem;
}

.usr-form .input-group-text {
    background: #f8f9fd;
    border-color: #dfe3ee;
    color: var(--app-muted);
    font-weight: 700;
}

.usr-form .form-control[disabled] {
    background: #f4f5fa;
    color: #6b7385;
    cursor: not-allowed;
}

.usr-foot {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: 1.15rem 1.75rem;
    background: #fafbff;
    border-top: 1px solid var(--app-line);
    border-radius: 0 0 18px 18px;
}

@media (max-width: 575.98px) {
    .usr-fhead {
        padding: 1.1rem;
    }

    .usr-sec,
    .usr-foot {
        padding-left: 1.1rem;
        padding-right: 1.1rem;
    }
}
</style>

<div class="usr-fhead d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="usr-fhead-ico" aria-hidden="true">
            <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus' ?>"></i>
        </span>
        <div>
            <h2><?= esc($title) ?></h2>
            <p><?= $isEdit ? 'Perbarui data dan hak akses pengguna.' : 'Lengkapi data pengguna baru.' ?></p>
        </div>
    </div>
    <a href="<?= site_url('users') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>
</div>

<div class="usr-form">
    <form method="post" action="<?= $action ?>" class="card" autocomplete="off" novalidate>
        <?= csrf_field() ?>

        <!-- 1. Identitas -->
        <section class="usr-sec">
            <div class="usr-sec-head">
                <span class="usr-sec-ico"><i class="bi bi-person-badge"></i></span>
                <div>
                    <h3>Identitas Pengguna</h3>
                    <p>Nama lengkap dan username untuk login.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control<?= $invalid('full_name') ?>" id="full_name"
                            name="full_name" maxlength="100"
                            value="<?= esc($val('full_name', $user['full_name'] ?? '')) ?>" autofocus
                            placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="invalid-feedback d-block"><?= esc($errors['full_name'] ?? '') ?></div>
                </div>

                <div class="col-md-6">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                        <?php if ($isEdit): ?>
                        <input type="text" class="form-control" id="username" value="<?= esc($user['username']) ?>"
                            disabled>
                        <?php else: ?>
                        <input type="text" class="form-control<?= $invalid('username') ?>" id="username" name="username"
                            maxlength="50" value="<?= esc($val('username')) ?>" placeholder="Contoh: budi.santoso">
                        <?php endif; ?>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="form-text mt-1">Username tidak dapat diubah setelah akun dibuat.</div>
                    <?php else: ?>
                    <div class="invalid-feedback d-block"><?= esc($errors['username'] ?? '') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 2. Keamanan & Akses -->
        <section class="usr-sec">
            <div class="usr-sec-head">
                <span class="usr-sec-ico"><i class="bi bi-shield-lock"></i></span>
                <div>
                    <h3>Keamanan & Hak Akses</h3>
                    <p>Atur password awal dan peran pengguna dalam sistem.</p>
                </div>
            </div>

            <div class="row g-3">
                <?php if (! $isEdit): ?>
                <div class="col-md-6">
                    <label for="password" class="form-label">Password Awal <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control<?= $invalid('password') ?>" id="password"
                            name="password" maxlength="72" autocomplete="new-password" placeholder="Minimal 8 karakter">
                        <button type="button" class="btn btn-outline-secondary border" id="togglePassword"
                            tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="invalid-feedback"><?= esc($errors['password'] ?? '') ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="<?= $isEdit ? 'col-md-12' : 'col-md-6' ?>">
                    <label for="role_id" class="form-label">Peran <span class="text-danger">*</span></label>
                    <?php
                    // Saat ubah: pilih peran saat ini; setelah gagal validasi: pertahankan pilihan terakhir
                    $roleIdByCode = array_column($roles, 'id', 'code');
                    $selected     = (int) $val('role_id', $user ? ($roleIdByCode[$user['role_code']] ?? 0) : 0);
                    ?>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-gear"></i></span>
                        <select class="form-select<?= $invalid('role_id') ?>" id="role_id" name="role_id">
                            <option value="">- Pilih peran -</option>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= $selected === (int) $r['id'] ? 'selected' : '' ?>>
                                <?= esc($r['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="invalid-feedback d-block"><?= esc($errors['role_id'] ?? '') ?></div>
                </div>
            </div>
        </section>

        <div class="usr-foot">
            <button type="submit" class="btn btn-primary px-4">
                <i
                    class="bi <?= $isEdit ? 'bi-check2-circle' : 'bi-plus-lg' ?> me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Pengguna' ?>
            </button>
            <a href="<?= site_url('users') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
// Tombol tampilkan/sembunyikan password agar Admin tidak salah ketik password awal
const toggle = document.getElementById('togglePassword');
if (toggle) {
    toggle.addEventListener('click', () => {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
}
</script>

<?= $this->endSection() ?>