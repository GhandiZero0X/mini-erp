<?php
$user    = auth_user();
$menu    = auth_menu();
$current = trim(uri_string(), '/');

/** Menu dianggap aktif bila URL saat ini sama dengan/di bawah path-nya. */
$isActive = static function (array $item) use ($current): bool {
    $path = $item['path'];
    if ($path === 'dashboard') {
        return $current === '' || $current === 'dashboard';
    }
    return $current === $path || str_starts_with($current, $path . '/');
};
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Beranda') ?> - NDT</title>
    <link rel="icon" type="image/png" href="<?= base_url('img/Logo.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
    body {
        background: #f1f3f6;
    }

    .sidebar {
        width: 250px;
        min-height: calc(100vh - 56px);
    }

    .sidebar .group-label {
        font-size: .72rem;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .brand-logo {
        display: block;
        height: 40px;
        width: auto;
        object-fit: contain;
    }
    </style>
</head>

<body>

    <!-- Bilah atas: nama aplikasi, nama & peran pengguna, tombol Logout (UI-02) -->
    <nav class="navbar bg-white border-bottom shadow-sm px-3">
        <span class="navbar-brand d-flex align-items-center mb-0 h1 text-dark">
            <img src="<?= base_url('img/Logo.png') ?>" alt="Logo PT Nusantara Distribusi" class="brand-logo me-2">
            Nusantara Distribusi Tracking
        </span>
        <div class="d-flex align-items-center gap-3 text-dark">
            <span class="small">
                <i class="bi bi-person-circle me-1"></i><?= esc($user['full_name']) ?>
                <span class="badge text-bg-secondary ms-1"><?= esc($user['role_name']) ?></span>
            </span>
            <!-- Logout memakai POST + token CSRF (keputusan UC-02) -->
            <form method="post" action="<?= site_url('logout') ?>" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </button>
            </form>
        </div>
    </nav>

    <div class="d-flex">
        <!-- Menu samping: hanya yang sesuai hak akses -->
        <aside class="sidebar bg-white border-end p-3">
            <?php $lastGroup = false; foreach ($menu as $item): ?>
            <?php if ($item['group'] !== $lastGroup && $item['group'] !== null): ?>
            <div class="group-label text-muted fw-semibold mt-3 mb-1"><?= esc($item['group']) ?></div>
            <?php endif; $lastGroup = $item['group']; ?>

            <?php if ($item['ready']): ?>
            <a href="<?= site_url($item['path']) ?>"
                class="d-block text-decoration-none rounded px-2 py-2 <?= $isActive($item) ? 'bg-primary text-white' : 'text-dark' ?>">
                <i class="bi <?= esc($item['icon']) ?> me-2"></i><?= esc($item['label']) ?>
            </a>
            <?php else: ?>
            <span class="d-block rounded px-2 py-2 text-muted" title="Halaman belum tersedia">
                <i class="bi <?= esc($item['icon']) ?> me-2"></i><?= esc($item['label']) ?>
                <span class="badge text-bg-light border ms-1">segera</span>
            </span>
            <?php endif; ?>
            <?php endforeach; ?>
        </aside>

        <main class="flex-grow-1 p-4">
            <?= $this->include('partials/flash') ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>