<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>Login - NDT</title>
    <link rel="icon" type="image/png" href="<?= base_url('img/Logo.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
    /* =====================================================================
     * Token desain (selaras dengan dashboard)
     * ===================================================================== */
    :root {
        --app-ink: #1e2433;
        --app-muted: #6b7385;
        --app-line: #e9ecf4;
        --app-bg: #f1f3f9;
        --app-primary: #4f46e5;
        --app-primary-dark: #4338ca;
        --app-shadow: 0 1px 2px rgba(30, 36, 51, .04), 0 10px 30px -10px rgba(30, 36, 51, .12);

        /* Bootstrap: selaraskan warna utama & font */
        --bs-body-font-family: "Plus Jakarta Sans", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --bs-body-color: var(--app-ink);
        --bs-primary: #4f46e5;
        --bs-primary-rgb: 79, 70, 229;
        --bs-border-color: var(--app-line);
    }

    body {
        background:
            radial-gradient(900px 420px at 100% -10%, rgba(109, 74, 237, .08), transparent 60%),
            radial-gradient(700px 380px at -10% 110%, rgba(14, 165, 198, .07), transparent 60%),
            var(--app-bg);
        background-attachment: fixed;
        color: var(--app-ink);
        -webkit-font-smoothing: antialiased;
    }

    /* =====================================================================
     * Kartu Login
     * ===================================================================== */
    .login-card {
        max-width: 420px;
        border: 1px solid var(--app-line);
        border-radius: 20px;
        box-shadow: var(--app-shadow);
        background: #fff;
    }

    .login-logo {
        height: 80px;
        max-width: 100%;
        width: auto;
        object-fit: contain;
        /* Memastikan tidak ada kotak/latar pada logo */
        background: transparent;
    }

    .brand-title {
        font-weight: 800;
        letter-spacing: -.02em;
        color: var(--app-ink);
    }

    .brand-subtitle {
        font-size: .85rem;
        font-weight: 500;
        color: var(--app-muted);
    }

    /* =====================================================================
     * Form & Input
     * ===================================================================== */
    .form-label {
        font-size: .85rem;
        font-weight: 600;
        color: #3b4358;
    }

    .input-group-text {
        border-radius: 10px 0 0 10px;
        border-color: #dfe3ee;
        background-color: #f8f9fd;
        color: var(--app-muted);
        transition: border-color .18s ease, color .18s ease;
    }

    .input-group .form-control {
        border-radius: 0 10px 10px 0;
        border-color: #dfe3ee;
        padding: .65rem 1rem;
        font-size: .95rem;
    }

    /* Efek fokus pada seluruh grup input */
    .input-group:focus-within .input-group-text {
        border-color: #818cf8;
        color: var(--app-primary);
    }

    .form-control:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 .25rem rgba(79, 70, 229, .15);
    }

    /* =====================================================================
     * Tombol
     * ===================================================================== */
    .btn {
        border-radius: 10px;
        font-weight: 700;
        letter-spacing: .01em;
    }

    .btn-primary {
        --bs-btn-bg: #4f46e5;
        --bs-btn-border-color: #4f46e5;
        --bs-btn-hover-bg: #4338ca;
        --bs-btn-hover-border-color: #4338ca;
        --bs-btn-active-bg: #3730a3;
        --bs-btn-active-border-color: #3730a3;
        --bs-btn-focus-shadow-rgb: 79, 70, 229;
        padding: .65rem 1rem;
    }

    .btn:focus-visible {
        outline: 2px solid #a5b4fc;
        outline-offset: 2px;
    }
    </style>
</head>

<body>
    <div class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="card login-card w-100">
            <div class="card-body p-4 p-md-5">
                <!-- Logo & Judul -->
                <div class="text-center mb-4">
                    <img src="<?= base_url('img/Logo.png') ?>" alt="Logo PT Nusantara Distribusi"
                        class="login-logo mb-3">
                    <h1 class="h4 brand-title mb-1">Nusantara Distribusi</h1>
                    <div class="brand-subtitle">Tracking System</div>
                </div>

                <!-- Flash Message -->
                <?= $this->include('partials/flash') ?>

                <!-- Form Login -->
                <form method="post" action="<?= site_url('login') ?>" autocomplete="off" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" maxlength="50"
                                placeholder="Masukkan username"
                                value="<?= esc((string) session()->getFlashdata('old_username')) ?>" autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" maxlength="255"
                                placeholder="Masukkan password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Sistem
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>