<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Mini ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f1f3f6; }
        .login-card { max-width: 400px; }
    </style>
</head>
<body>
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow-sm login-card w-100">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="bi bi-boxes fs-1 text-primary"></i>
                <h1 class="h4 mt-2 mb-0">Mini ERP</h1>
                <div class="text-muted small">PT Nusantara Distribusi</div>
            </div>

            <?= $this->include('partials/flash') ?>

            <form method="post" action="<?= site_url('login') ?>" autocomplete="off" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" maxlength="50"
                           value="<?= esc((string) session()->getFlashdata('old_username')) ?>" autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" maxlength="255">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
