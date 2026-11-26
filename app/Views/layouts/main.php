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

// Inisial untuk avatar (maks. 2 huruf dari nama lengkap)
$words    = preg_split('/\s+/', trim((string) ($user['full_name'] ?? ''))) ?: [];
$initials = mb_strtoupper(implode('', array_map(
    static fn (string $w): string => mb_substr($w, 0, 1),
    array_slice(array_filter($words, static fn ($w) => $w !== ''), 0, 2)
)));
$initials = $initials !== '' ? $initials : '?';

// Tanggal hari ini dalam bahasa Indonesia untuk bilah atas
$hari       = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulanPanjang = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggalHariIni = $hari[(int) date('w')] . ', ' . (int) date('j') . ' ' . $bulanPanjang[(int) date('n') - 1] . ' ' . date('Y');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title><?= esc($title ?? 'Beranda') ?> - NDT</title>
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
        --app-grad: linear-gradient(135deg, #6d4aed 0%, #4c6ef5 100%);
        --app-side-w: 272px;
        --app-shadow: 0 1px 2px rgba(30, 36, 51, .04), 0 8px 24px -12px rgba(30, 36, 51, .12);

        /* Bootstrap: selaraskan warna utama & font */
        --bs-body-font-family: "Plus Jakarta Sans", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --bs-body-color: var(--app-ink);
        --bs-primary: #4f46e5;
        --bs-primary-rgb: 79, 70, 229;
        --bs-link-color: #4338ca;
        --bs-link-hover-color: #3730a3;
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
     * Kerangka: sidebar + area utama
     * ===================================================================== */
    .app {
        display: flex;
        min-height: 100vh;
    }

    .app-main {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .app-content {
        flex: 1 1 auto;
        padding: 1.5rem 1.75rem 2.5rem;
        width: 100%;
        max-width: 1680px;
        margin: 0 auto;
    }

    /* ---------- Sidebar (putih) ---------- */
    .app-sidebar {
        --bs-offcanvas-width: 284px;
        --bs-offcanvas-bg: #fff;
        --bs-offcanvas-color: var(--app-ink);
        --bs-offcanvas-border-width: 0;
        background: #fff;
        color: var(--app-ink);
        border-right: 1px solid var(--app-line);
        display: flex;
        flex-direction: column;
    }

    @media (min-width: 992px) {
        .app-sidebar {
            width: var(--app-side-w);
            flex: 0 0 var(--app-side-w);
            position: sticky;
            top: 0;
            height: 100vh;
        }
    }

    .side-brand {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1.15rem 1.15rem 1rem;
        border-bottom: 1px solid var(--app-line);
    }

    /* PERUBAHAN: Menghapus kotak (background, border, shadow) pada logo */
    .side-logo {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
    }

    .side-logo img {
        max-width: 46px;
        max-height: 46px;
        object-fit: contain;
    }

    .side-name {
        line-height: 1.15;
        min-width: 0;
    }

    .side-name strong {
        display: block;
        font-size: .98rem;
        font-weight: 800;
        letter-spacing: .01em;
        color: var(--app-ink);
    }

    .side-name span {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--app-muted);
    }

    .side-nav {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: .6rem .85rem 1rem;
        scrollbar-width: thin;
        scrollbar-color: #d5d9e6 transparent;
    }

    .side-nav::-webkit-scrollbar {
        width: 6px;
    }

    .side-nav::-webkit-scrollbar-thumb {
        background: #d5d9e6;
        border-radius: 99px;
    }

    .group-label {
        margin: 1.1rem .65rem .4rem;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #9aa1b5;
    }

    .nav-link-x {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: .15rem;
        padding: .5rem .65rem;
        border-radius: 12px;
        color: #3b4358;
        font-size: .9rem;
        font-weight: 600;
        text-decoration: none;
        transition: background .18s ease, color .18s ease, transform .18s ease;
    }

    .nav-link-x .ico {
        flex: 0 0 auto;
        width: 32px;
        height: 32px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        font-size: 1rem;
        color: #6b7385;
        background: #f1f3f9;
        transition: background .18s ease, color .18s ease;
    }

    .nav-link-x:hover {
        background: #f4f5fc;
        color: var(--app-primary-dark);
        transform: translateX(2px);
    }

    .nav-link-x:hover .ico {
        background: #e7e9fb;
        color: var(--app-primary);
    }

    .nav-link-x.active {
        color: #fff;
        background: var(--app-grad);
        box-shadow: 0 10px 22px -10px rgba(109, 74, 237, .8);
    }

    .nav-link-x.active .ico {
        color: #fff;
        background: rgba(255, 255, 255, .22);
    }

    .nav-link-x:focus-visible,
    .btn:focus-visible {
        outline: 2px solid #a5b4fc;
        outline-offset: 2px;
    }

    .nav-link-x.disabled-x {
        cursor: not-allowed;
        color: #a3a9bb;
    }

    .nav-link-x.disabled-x:hover {
        background: transparent;
        color: #a3a9bb;
        transform: none;
    }

    .nav-link-x.disabled-x .ico,
    .nav-link-x.disabled-x:hover .ico {
        background: #f1f3f9;
        color: #b4bacb;
    }

    .soon {
        margin-left: auto;
        padding: .1rem .5rem;
        border-radius: 99px;
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #8a91a6;
        border: 1px solid var(--app-line);
    }

    .side-foot {
        padding: .85rem 1.15rem;
        font-size: .72rem;
        color: #9aa1b5;
        border-top: 1px solid var(--app-line);
    }

    /* ---------- Bilah atas ---------- */
    /* PERUBAHAN: Header diubah menjadi putih solid (tanpa transparansi/blur) */
    .app-top {
        position: sticky;
        top: 0;
        z-index: 1020;
        display: flex;
        align-items: center;
        gap: .9rem;
        padding: .75rem 1.75rem;
        background: #fff;
        border-bottom: 1px solid var(--app-line);
    }

    .top-toggle {
        display: inline-grid;
        place-items: center;
        width: 40px;
        height: 40px;
        border: 1px solid var(--app-line);
        border-radius: 12px;
        background: #fff;
        color: var(--app-ink);
        font-size: 1.25rem;
    }

    .top-title {
        min-width: 0;
        line-height: 1.2;
    }

    .top-title h1 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .top-title small {
        display: block;
        font-size: .74rem;
        font-weight: 500;
        color: var(--app-muted);
    }

    .top-user {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-left: auto;
        padding: .25rem .35rem .25rem .3rem;
        border: 1px solid var(--app-line);
        border-radius: 99px;
        background: #fff;
    }

    .avatar {
        flex: 0 0 auto;
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        font-size: .8rem;
        font-weight: 800;
        color: #fff;
        background: var(--app-grad);
    }

    .top-user .who {
        line-height: 1.15;
        padding-right: .2rem;
    }

    .top-user .who strong {
        display: block;
        font-size: .82rem;
        font-weight: 700;
        max-width: 180px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .top-user .who span {
        display: block;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--app-primary);
    }

    .btn-logout {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .38rem .85rem;
        border: 0;
        border-radius: 99px;
        font-size: .8rem;
        font-weight: 700;
        color: #475069;
        background: #f1f3f9;
        transition: background .18s ease, color .18s ease;
    }

    .btn-logout:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    @media (max-width: 991.98px) {

        .app-top,
        .app-content {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }

    @media (max-width: 575.98px) {

        .top-user .who,
        .btn-logout span {
            display: none;
        }

        .top-user {
            padding-right: .3rem;
        }
    }

    /* =====================================================================
     * Penyelarasan komponen Bootstrap untuk halaman lain (master data, dll.)
     * ===================================================================== */
    .btn {
        border-radius: 10px;
        font-weight: 600;
    }

    .btn-primary {
        --bs-btn-bg: #4f46e5;
        --bs-btn-border-color: #4f46e5;
        --bs-btn-hover-bg: #4338ca;
        --bs-btn-hover-border-color: #4338ca;
        --bs-btn-active-bg: #3730a3;
        --bs-btn-active-border-color: #3730a3;
        --bs-btn-disabled-bg: #4f46e5;
        --bs-btn-disabled-border-color: #4f46e5;
        --bs-btn-focus-shadow-rgb: 79, 70, 229;
    }

    .btn-outline-primary {
        --bs-btn-color: #4f46e5;
        --bs-btn-border-color: #4f46e5;
        --bs-btn-hover-bg: #4f46e5;
        --bs-btn-hover-border-color: #4f46e5;
        --bs-btn-active-bg: #4338ca;
        --bs-btn-active-border-color: #4338ca;
        --bs-btn-focus-shadow-rgb: 79, 70, 229;
    }

    .card {
        border: 1px solid var(--app-line);
        border-radius: 18px;
        box-shadow: var(--app-shadow);
    }

    .card-header {
        background: transparent;
        border-bottom: 1px solid var(--app-line);
        font-weight: 700;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        border-color: #dfe3ee;
    }

    .form-control:focus,
    .form-select:focus,
    .form-check-input:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 .25rem rgba(79, 70, 229, .15);
    }

    .form-check-input:checked {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .table>thead>tr>th {
        background: #f8f9fd;
        color: var(--app-muted);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        border-bottom: 1px solid var(--app-line);
        white-space: nowrap;
    }

    .table {
        --bs-table-hover-bg: rgba(79, 70, 229, .04);
        --bs-table-border-color: var(--app-line);
    }

    .alert {
        border: 0;
        border-radius: 14px;
        box-shadow: var(--app-shadow);
    }

    .badge {
        font-weight: 700;
    }

    .page-link {
        color: #4338ca;
    }

    .page-item.active .page-link {
        background: #4f46e5;
        border-color: #4f46e5;
    }

    @media (prefers-reduced-motion: reduce) {

        .nav-link-x,
        .nav-link-x .ico,
        .btn-logout {
            transition: none;
        }

        .nav-link-x:hover {
            transform: none;
        }
    }
    </style>
</head>

<body>
    <div class="app">

        <!-- Menu samping: hanya yang sesuai hak akses. Di layar kecil tampil sebagai panel geser (offcanvas). -->
        <aside id="appSidebar" class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" aria-label="Menu utama">
            <div class="side-brand">
                <span class="side-logo">
                    <img src="<?= base_url('img/Logo.png') ?>" alt="Logo PT Nusantara Distribusi">
                </span>
                <span class="side-name">
                    <strong>Nusantara Distribusi</strong>
                    <span>Tracking</span>
                </span>
                <button type="button" class="btn-close ms-auto d-lg-none" data-bs-dismiss="offcanvas"
                    data-bs-target="#appSidebar" aria-label="Tutup menu"></button>
            </div>

            <nav class="side-nav">
                <?php $lastGroup = false; foreach ($menu as $item): ?>
                <?php if ($item['group'] !== $lastGroup && $item['group'] !== null): ?>
                <div class="group-label"><?= esc($item['group']) ?></div>
                <?php endif; $lastGroup = $item['group']; ?>

                <?php if ($item['ready']): ?>
                <?php $active = $isActive($item); ?>
                <a href="<?= site_url($item['path']) ?>" class="nav-link-x<?= $active ? ' active' : '' ?>"
                    <?= $active ? 'aria-current="page"' : '' ?>>
                    <span class="ico"><i class="bi <?= esc($item['icon']) ?>"></i></span>
                    <span><?= esc($item['label']) ?></span>
                </a>
                <?php else: ?>
                <span class="nav-link-x disabled-x" title="Halaman belum tersedia" aria-disabled="true">
                    <span class="ico"><i class="bi <?= esc($item['icon']) ?>"></i></span>
                    <span><?= esc($item['label']) ?></span>
                    <span class="soon">segera</span>
                </span>
                <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="side-foot">&copy; <?= date('Y') ?> PT Nusantara Distribusi</div>
        </aside>

        <div class="app-main">
            <!-- Bilah atas: judul halaman, tanggal, nama & peran pengguna, tombol Logout (UI-02) -->
            <header class="app-top">
                <button type="button" class="top-toggle d-lg-none" data-bs-toggle="offcanvas"
                    data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>

                <div class="top-title">
                    <h1><?= esc($title ?? 'Beranda') ?></h1>
                    <small><i class="bi bi-calendar3 me-1"></i><?= esc($tanggalHariIni) ?></small>
                </div>

                <div class="top-user">
                    <span class="avatar" aria-hidden="true"><?= esc($initials) ?></span>
                    <span class="who">
                        <strong><?= esc($user['full_name']) ?></strong>
                        <span><?= esc($user['role_name']) ?></span>
                    </span>
                    <!-- Logout memakai POST + token CSRF (keputusan UC-02) -->
                    <form method="post" action="<?= site_url('logout') ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-logout" title="Keluar">
                            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <main class="app-content">
                <?= $this->include('partials/flash') ?>
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>