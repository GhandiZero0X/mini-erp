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

<h1 class="h4 mb-3"><?= esc($title) ?></h1>

<div class="card shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="<?= $action ?>" autocomplete="off" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="full_name" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control<?= $invalid('full_name') ?>" id="full_name" name="full_name"
                       maxlength="100" value="<?= esc($val('full_name', $user['full_name'] ?? '')) ?>" autofocus>
                <div class="invalid-feedback"><?= esc($errors['full_name'] ?? '') ?></div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <?php if ($isEdit): ?>
                    <input type="text" class="form-control" id="username" value="<?= esc($user['username']) ?>" disabled>
                    <div class="form-text">Username tidak dapat diubah setelah akun dibuat.</div>
                <?php else: ?>
                    <input type="text" class="form-control<?= $invalid('username') ?>" id="username" name="username"
                           maxlength="50" value="<?= esc($val('username')) ?>">
                    <div class="invalid-feedback"><?= esc($errors['username'] ?? '') ?></div>
                <?php endif; ?>
            </div>

            <?php if (! $isEdit): ?>
                <div class="mb-3">
                    <label for="password" class="form-label">Password Awal</label>
                    <div class="input-group has-validation">
                        <input type="password" class="form-control<?= $invalid('password') ?>" id="password" name="password"
                               maxlength="72" autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="invalid-feedback"><?= esc($errors['password'] ?? '') ?></div>
                    </div>
                    <div class="form-text">Minimal 8 karakter.</div>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="role_id" class="form-label">Peran</label>
                <?php
                // Saat ubah: pilih peran saat ini; setelah gagal validasi: pertahankan pilihan terakhir
                $roleIdByCode = array_column($roles, 'id', 'code');
                $selected     = (int) $val('role_id', $user ? ($roleIdByCode[$user['role_code']] ?? 0) : 0);
                ?>
                <select class="form-select<?= $invalid('role_id') ?>" id="role_id" name="role_id">
                    <option value="">- Pilih peran -</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['id'] ?>" <?= $selected === (int) $r['id'] ? 'selected' : '' ?>>
                            <?= esc($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= esc($errors['role_id'] ?? '') ?></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="<?= site_url('users') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Tombol tampilkan/sembunyikan password agar Admin tidak salah ketik password awal
    const toggle = document.getElementById('togglePassword');
    if (toggle) {
        toggle.addEventListener('click', () => {
            const input = document.getElementById('password');
            const show  = input.type === 'password';
            input.type  = show ? 'text' : 'password';
            toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }
</script>

<?= $this->endSection() ?>
