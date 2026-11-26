<?php
/** UI-05 Supplier (daftar). Variabel: $rows, $pager, $canWrite. */
$this->extend('layouts/main');

$search  = $search ?? '';
$perPage = $pager ? ((int) $pager->getPerPage() ?: 10) : 10;
$page    = $pager ? $pager->getCurrentPage() : 1;
$offset  = ($page - 1) * $perPage;
$total   = max($pager ? (int) $pager->getTotal() : 0, $offset + count($rows));
$colspan = $canWrite ? 5 : 4;
?>
<?= $this->section('content') ?>

<style>
/* Prefiks "sup-" supaya tidak bentrok */
.sup-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.sup-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.sup-head>* {
    position: relative;
    z-index: 1;
}

.sup-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.sup-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.sup-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.sup-head .btn-light {
    color: #4338ca;
    font-weight: 700;
    border: 0;
    box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .5);
}

.sup-head .btn-light:hover {
    background: #fff;
    transform: translateY(-1px);
}

/* ---------- Toolbar ---------- */
.sup-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
}

.sup-search {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    flex: 1 1 320px;
    max-width: 520px;
}

.sup-search-field {
    position: relative;
    flex: 1 1 220px;
}

.sup-search-field i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    pointer-events: none;
}

.sup-search-field input {
    padding-left: 2.3rem;
    border-radius: 99px;
    background: #f8f9fd;
}

.sup-meta {
    color: var(--app-muted);
    font-size: .82rem;
}

.sup-meta b {
    color: var(--app-ink);
}

/* ---------- Tabel ---------- */
.sup-table td {
    vertical-align: middle;
}

.sup-name {
    font-weight: 700;
    overflow-wrap: anywhere;
}

.sup-contact {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    font-size: .85rem;
}

.sup-contact span {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    color: var(--app-muted);
}

.sup-address {
    font-size: .85rem;
    color: var(--app-muted);
    max-width: 250px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Tombol aksi berikon */
.sup-act {
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

.sup-act:hover {
    transform: translateY(-1px);
}

.sup-act.is-edit:hover {
    background: #e7e9fb;
    color: #4338ca;
}

.sup-act.is-del:hover {
    background: #fee2e2;
    color: #b91c1c;
}

/* ---------- Kosong ---------- */
.sup-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.sup-empty-ico {
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

.sup-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.sup-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Modal hapus ---------- */
.sup-del-ico {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 18px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 1.5rem;
}

.sup-del-name {
    display: inline-block;
    max-width: 100%;
    padding: .2rem .65rem;
    border-radius: 8px;
    background: #f1f3f9;
    overflow-wrap: anywhere;
}

/* ---------- Ponsel ---------- */
@media (max-width: 767.98px) {
    .sup-head {
        padding: 1.1rem;
    }

    .sup-table,
    .sup-table tbody,
    .sup-table tr,
    .sup-table td {
        display: block;
        width: 100%;
    }

    .sup-table thead {
        display: none;
    }

    .sup-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .sup-table tbody tr:last-child {
        border-bottom: 0;
    }

    .sup-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .sup-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .sup-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .sup-address {
        max-width: 100%;
        white-space: normal;
    }

    .sup-table td.sup-td-act {
        margin-top: .5rem;
    }
}
</style>

<!-- Header -->
<div class="sup-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="sup-head-ico" aria-hidden="true"><i class="bi bi-truck"></i></span>
        <div>
            <h2>Supplier</h2>
            <p>Kelola data pemasok barang untuk keperluan pembelian.</p>
        </div>
    </div>

    <?php if ($canWrite): ?>
    <a href="<?= site_url('suppliers/new') ?>" class="btn btn-light">
        <i class="bi bi-plus-lg me-1"></i>Tambah Supplier
    </a>
    <?php endif; ?>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar -->
    <div class="sup-bar">
        <form method="get" action="<?= site_url('suppliers') ?>" class="sup-search">
            <label class="sup-search-field mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" maxlength="100" placeholder="Cari nama supplier"
                    value="<?= esc($search, 'attr') ?>" aria-label="Cari nama supplier" autocomplete="off">
            </label>
            <button type="submit" class="btn btn-primary px-3">Cari</button>
            <?php if ($search !== ''): ?>
            <a href="<?= site_url('suppliers') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($rows !== []): ?>
        <div class="sup-meta">
            Menampilkan <b><?= $offset + 1 ?>&ndash;<?= $offset + count($rows) ?></b> dari <b><?= $total ?></b> supplier
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sup-table">
            <thead>
                <tr>
                    <th>Nama Supplier</th>
                    <th>Kontak</th>
                    <th>Alamat</th>
                    <?php if ($canWrite): ?>
                    <th class="text-end" style="width: 110px;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= $colspan ?>" class="p-0">
                        <div class="sup-empty">
                            <div class="sup-empty-ico"><i
                                    class="bi <?= $search !== '' ? 'bi-search' : 'bi-truck' ?>"></i></div>
                            <?php if ($search !== ''): ?>
                            <h3>Supplier tidak ditemukan</h3>
                            <p>Tidak ada supplier yang cocok dengan &ldquo;<?= esc($search) ?>&rdquo;.</p>
                            <a href="<?= site_url('suppliers') ?>" class="btn btn-outline-secondary btn-sm">Reset
                                pencarian</a>
                            <?php else: ?>
                            <h3>Belum ada supplier</h3>
                            <?php if ($canWrite): ?>
                            <p>Tambahkan supplier pertama untuk mulai mencatat data pemasok.</p>
                            <a href="<?= site_url('suppliers/new') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Supplier
                            </a>
                            <?php else: ?>
                            <p class="mb-0">Data supplier akan tampil di sini.</p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <div class="sup-name"><?= esc($row['name']) ?></div>
                    </td>
                    <td data-th="Kontak">
                        <div class="sup-contact">
                            <span><i class="bi bi-telephone"></i>
                                <?= esc($row['phone'] ?? '') !== '' ? esc($row['phone']) : '-' ?></span>
                            <span><i class="bi bi-envelope"></i>
                                <?= esc($row['email'] ?? '') !== '' ? esc($row['email']) : '-' ?></span>
                        </div>
                    </td>
                    <td data-th="Alamat">
                        <div class="sup-address" title="<?= esc($row['address'] ?? '') ?>">
                            <?= $row['address'] !== null && $row['address'] !== '' ? esc(mb_strimwidth($row['address'], 0, 80, '...')) : '-' ?>
                        </div>
                    </td>
                    <?php if ($canWrite): ?>
                    <td class="text-end text-nowrap sup-td-act">
                        <a href="<?= site_url('suppliers/' . $row['id'] . '/edit') ?>" class="sup-act is-edit"
                            title="Ubah supplier" aria-label="Ubah <?= esc($row['name'], 'attr') ?>">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <button type="button" class="sup-act is-del" data-bs-toggle="modal"
                            data-bs-target="#deleteSupplierModal" title="Hapus supplier"
                            aria-label="Hapus <?= esc($row['name'], 'attr') ?>"
                            data-action="<?= site_url('suppliers/' . $row['id'] . '/delete') ?>"
                            data-label="<?= esc($row['name'], 'attr') ?>">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager !== null && $pager->getPageCount() > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-end border-top-0 py-3">
        <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($canWrite): ?>
<!-- Modal Hapus -->
<div class="modal fade" id="deleteSupplierModal" tabindex="-1" aria-labelledby="deleteSupplierTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <form method="post" action="" id="deleteSupplierForm" class="modal-content border-0"
            style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="sup-del-ico"><i class="bi bi-trash3"></i></div>
                <h5 class="fw-bold mb-2" id="deleteSupplierTitle">Hapus supplier?</h5>
                <p class="mb-2 text-muted small">Supplier berikut akan dihapus dan tindakan ini tidak dapat dibatalkan.
                </p>
                <strong class="sup-del-name" id="deleteSupplierLabel"></strong>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill m-0"
                    data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger flex-fill m-0">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('deleteSupplierModal');
    var form = document.getElementById('deleteSupplierForm');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function(event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        form.action = btn.getAttribute('data-action');
        document.getElementById('deleteSupplierLabel').textContent = btn.getAttribute('data-label');
    });
    form.addEventListener('submit', function() {
        var b = form.querySelector('button[type="submit"]');
        if (b) {
            b.disabled = true;
            b.textContent = 'Menghapus...';
        }
    });
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>