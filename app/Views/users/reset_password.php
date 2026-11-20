<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h1 class="h4 mb-3">Reset Password</h1>

<div class="card shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <p class="mb-4">
            Atur password baru untuk <strong><?= esc($user['full_name']) ?></strong>
            (<code><?= esc($user['username']) ?></code>).
        </p>

        <form method="post" action="<?= site_url('users/' . $user['id'] . '/reset-password') ?>" autocomplete="off" novalidate>
            <?= csrf_field() ?>

            <div class="mb-4">
                <label for="password" class="form-label">Password Baru</label>
                <div class="input-group has-validation">
                    <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                           id="password" name="password" maxlength="72" autocomplete="new-password" autofocus>
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback"><?= esc($errors['password'] ?? '') ?></div>
                </div>
                <div class="form-text">Minimal 8 karakter.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan Password</button>
                <a href="<?= site_url('users') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    const toggle = document.getElementById('togglePassword');
    toggle.addEventListener('click', () => {
        const input = document.getElementById('password');
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>

<?= $this->endSection() ?>
