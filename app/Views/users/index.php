<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Pengguna</h1>
    <a href="<?= site_url('users/new') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Peran</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($users === []): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data pengguna.</td></tr>
            <?php endif; ?>

            <?php foreach ($users as $u): ?>
                <?php $isMe = $u['id'] === $meId; ?>
                <tr>
                    <td>
                        <?= esc($u['full_name']) ?>
                        <?php if ($isMe): ?><span class="badge text-bg-info ms-1">Anda</span><?php endif; ?>
                    </td>
                    <td><code><?= esc($u['username']) ?></code></td>
                    <td><?= esc($u['role_name']) ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge text-bg-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Nonaktif</span>
                        <?php endif; ?>
                        <?php if ($u['is_locked']): ?>
                            <span class="badge text-bg-danger">Terkunci</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= site_url('users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
                        <a href="<?= site_url('users/' . $u['id'] . '/reset-password') ?>" class="btn btn-sm btn-outline-secondary">Reset Password</a>

                        <?php if ($u['is_locked']): ?>
                            <form method="post" action="<?= site_url('users/' . $u['id'] . '/unlock') ?>" class="d-inline"
                                  onsubmit="return confirm('<?= esc('Buka kunci akun ' . $u['username'] . '?', 'js') ?>')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-warning">Buka Kunci</button>
                            </form>
                        <?php endif; ?>

                        <?php if (! $isMe): // Admin tidak boleh menonaktifkan dirinya sendiri (VR-37) ?>
                            <form method="post" action="<?= site_url('users/' . $u['id'] . '/toggle') ?>" class="d-inline"
                                  onsubmit="return confirm('<?= esc(($u['is_active'] ? 'Nonaktifkan' : 'Aktifkan') . ' akun ' . $u['username'] . '?', 'js') ?>')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                    <?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $pageCount = $pager ? $pager->getPageCount() : 1;
    $current   = $pager ? $pager->getCurrentPage() : 1;
    if ($pageCount > 1): ?>
        <div class="card-footer bg-white">
            <nav aria-label="Halaman">
                <ul class="pagination pagination-sm mb-0 justify-content-end">
                    <?php for ($i = 1; $i <= $pageCount; $i++): ?>
                        <li class="page-item <?= $i === $current ? 'active' : '' ?>">
                            <a class="page-link" href="<?= site_url('users') . '?page=' . $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
