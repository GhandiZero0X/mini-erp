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
    max-width: 560px;
    margin-top: 1.1rem;
}

.usr-sec {
    padding: 1.5rem 1.75rem;
}

.usr-form .form-label {
    font-size: .85rem;
    font-weight: 700;
    margin-bottom: .4rem;
}

.usr-form .input-group-text {
    background: #f8f9fd;
    border-color: #dfe3ee;
    color: var(--app-muted);
    font-weight: 700;
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
</style>

<div class="usr-fhead d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="usr-fhead-ico" aria-hidden="true"><i class="bi bi-key"></i></span>
        <div>
            <h2>Reset Password</h2>
            <p>Atur ulang kata sandi pengguna.</p>
        </div>
    </div>
    <a href="<?= site_url('users') ?>" class="btn btn-sm btn-back">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>
</div>

<div class="usr-form">
    <form method="post" action="<?= site_url('users/' . $user['id'] . '/reset-password') ?>" class="card"
        autocomplete="off" novalidate>
        <?= csrf_field() ?>

        <section class="usr-sec">
            <div class="mb-4">
                <p class="text-muted small mb-3">
                    Atur password baru untuk <strong class="text-dark"><?= esc($user['full_name']) ?></strong>
                    (<code><?= esc($user['username']) ?></code>).
                </p>

                <label for="password" class="form-label">Password Baru <span class="text-danger">*</span></label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                        id="password" name="password" maxlength="72" autocomplete="new-password" autofocus
                        placeholder="Masukkan password baru">
                    <button type="button" class="btn btn-outline-secondary border" id="togglePassword" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback"><?= esc($errors['password'] ?? '') ?></div>
                </div>
                <div class="form-text mt-1">Minimal 8 karakter.</div>
            </div>
        </section>

        <div class="usr-foot">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i>Simpan Password
            </button>
            <a href="<?= site_url('users') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
const toggle = document.getElementById('togglePassword');
toggle.addEventListener('click', () => {
    const input = document.getElementById('password');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>

<?= $this->endSection() ?>