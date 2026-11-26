<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* Prefiks "usr-" */
.usr-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.usr-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.usr-head>* {
    position: relative;
    z-index: 1;
}

.usr-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.usr-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.usr-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.usr-head .btn-light {
    color: #4338ca;
    font-weight: 700;
    border: 0;
    box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .5);
}

.usr-head .btn-light:hover {
    background: #fff;
    transform: translateY(-1px);
}

/* ---------- Tabel ---------- */
.usr-table td {
    vertical-align: middle;
}

.usr-name {
    font-weight: 700;
    overflow-wrap: anywhere;
}

.usr-code {
    display: inline-block;
    padding: .2rem .55rem;
    border-radius: 8px;
    background: #f1f3f9;
    color: #3b4358;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .8rem;
    font-weight: 600;
    white-space: nowrap;
}

/* Badge kustom */
.usr-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .25rem .7rem;
    border-radius: 99px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .02em;
}

.usr-badge-aktif {
    background: #e3f8ef;
    color: #0f8a5c;
}

.usr-badge-nonaktif {
    background: #f1f3f9;
    color: #475069;
}

.usr-badge-locked {
    background: #fee2e2;
    color: #b91c1c;
}

.usr-badge-me {
    background: #eef0ff;
    color: #4338ca;
    border: 1px solid #d6d9f5;
}

/* Tombol aksi berikon */
.usr-act {
    display: inline-grid;
    place-items: center;
    width: 34px;
    height: 34px;
    padding: 0;
    border: 0;
    border-radius: 10px;
    background: #f1f3f9;
    color: #475069;
    font-size: .95rem;
    transition: background .15s ease, color .15s ease, transform .15s ease;
}

.usr-act:hover {
    transform: translateY(-1px);
}

.usr-act.is-edit:hover {
    background: #e7e9fb;
    color: #4338ca;
}

.usr-act.is-key:hover {
    background: #fff4d6;
    color: #946500;
}

.usr-act.is-unlock:hover {
    background: #fff4d6;
    color: #946500;
}

.usr-act.is-off:hover {
    background: #fee2e2;
    color: #b91c1c;
}

.usr-act.is-on:hover {
    background: #e3f8ef;
    color: #0f8a5c;
}

/* ---------- Kosong ---------- */
.usr-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.usr-empty-ico {
    width: 60px;
    height: 60px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 18px;
    background: #eef0ff;
    color: #4338ca;
    font-size: 1.6rem;
}

.usr-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.usr-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Ponsel ---------- */
@media (max-width: 767.98px) {
    .usr-head {
        padding: 1.1rem;
    }

    .usr-table,
    .usr-table tbody,
    .usr-table tr,
    .usr-table td {
        display: block;
        width: 100%;
    }

    .usr-table thead {
        display: none;
    }

    .usr-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .usr-table tbody tr:last-child {
        border-bottom: 0;
    }

    .usr-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .usr-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .usr-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .usr-table td.usr-td-act {
        display: flex;
        justify-content: flex-start;
        gap: .5rem;
        margin-top: .6rem;
    }
}
</style>

<!-- Header -->
<div class="usr-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="usr-head-ico" aria-hidden="true"><i class="bi bi-people-fill"></i></span>
        <div>
            <h2>Pengguna</h2>
            <p>Kelola akun, peran, dan status akses pengguna sistem.</p>
        </div>
    </div>

    <a href="<?= site_url('users/new') ?>" class="btn btn-light">
        <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
    </a>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 usr-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Peran</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users === []): ?>
                <tr>
                    <td colspan="5" class="p-0">
                        <div class="usr-empty">
                            <div class="usr-empty-ico"><i class="bi bi-person-x"></i></div>
                            <h3>Belum ada pengguna</h3>
                            <p>Tambahkan pengguna pertama untuk mulai mengelola akses sistem.</p>
                            <a href="<?= site_url('users/new') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($users as $u): ?>
                <?php $isMe = $u['id'] === $meId; ?>
                <tr>
                    <td data-th="Nama">
                        <div class="usr-name"><?= esc($u['full_name']) ?></div>
                        <?php if ($isMe): ?>
                        <span class="usr-badge usr-badge-me mt-1"><i class="bi bi-person-check"></i> Anda</span>
                        <?php endif; ?>
                    </td>
                    <td data-th="Username"><span class="usr-code"><?= esc($u['username']) ?></span></td>
                    <td data-th="Peran"><span class="fw-semibold text-dark"><?= esc($u['role_name']) ?></span></td>
                    <td data-th="Status">
                        <?php if ($u['is_active']): ?>
                        <span class="usr-badge usr-badge-aktif"><i class="bi bi-check-circle"></i> Aktif</span>
                        <?php else: ?>
                        <span class="usr-badge usr-badge-nonaktif"><i class="bi bi-dash-circle"></i> Nonaktif</span>
                        <?php endif; ?>

                        <?php if ($u['is_locked']): ?>
                        <span class="usr-badge usr-badge-locked"><i class="bi bi-lock"></i> Terkunci</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap usr-td-act">
                        <a href="<?= site_url('users/' . $u['id'] . '/edit') ?>" class="usr-act is-edit"
                            title="Ubah Pengguna" aria-label="Ubah <?= esc($u['full_name'], 'attr') ?>">
                            <i class="bi bi-pencil-square"></i>
                        </a>

                        <a href="<?= site_url('users/' . $u['id'] . '/reset-password') ?>" class="usr-act is-key"
                            title="Reset Password" aria-label="Reset Password <?= esc($u['full_name'], 'attr') ?>">
                            <i class="bi bi-key"></i>
                        </a>

                        <?php if ($u['is_locked']): ?>
                        <form method="post" action="<?= site_url('users/' . $u['id'] . '/unlock') ?>" class="d-inline"
                            onsubmit="return confirm('<?= esc('Buka kunci akun ' . $u['username'] . '?', 'js') ?>')">
                            <?= csrf_field() ?>
                            <button type="submit" class="usr-act is-unlock" title="Buka Kunci"
                                aria-label="Buka Kunci <?= esc($u['full_name'], 'attr') ?>">
                                <i class="bi bi-unlock"></i>
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if (!$isMe): ?>
                        <form method="post" action="<?= site_url('users/' . $u['id'] . '/toggle') ?>" class="d-inline"
                            onsubmit="return confirm('<?= esc(($u['is_active'] ? 'Nonaktifkan' : 'Aktifkan') . ' akun ' . $u['username'] . '?', 'js') ?>')">
                            <?= csrf_field() ?>
                            <button type="submit" class="usr-act <?= $u['is_active'] ? 'is-off' : 'is-on' ?>"
                                title="<?= $u['is_active'] ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>"
                                aria-label="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> <?= esc($u['full_name'], 'attr') ?>">
                                <i class="bi <?= $u['is_active'] ? 'bi-power' : 'bi-check2-circle' ?>"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-end border-top-0 py-3">
        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>