<?php
/** UI-06 Customer (daftar). Variabel: $rows, $pager, $canWrite, $search. */
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
/* Prefiks "cus-" */
.cus-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.cus-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.cus-head>* {
    position: relative;
    z-index: 1;
}

.cus-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.cus-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.cus-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.cus-head .btn-light {
    color: #4338ca;
    font-weight: 700;
    border: 0;
    box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .5);
}

.cus-head .btn-light:hover {
    background: #fff;
    transform: translateY(-1px);
}

/* ---------- Toolbar ---------- */
.cus-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
}

.cus-search {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    flex: 1 1 320px;
    max-width: 520px;
}

.cus-search-field {
    position: relative;
    flex: 1 1 220px;
}

.cus-search-field i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    pointer-events: none;
}

.cus-search-field input {
    padding-left: 2.3rem;
    border-radius: 99px;
    background: #f8f9fd;
}

.cus-meta {
    color: var(--app-muted);
    font-size: .82rem;
}

.cus-meta b {
    color: var(--app-ink);
}

.cus-q {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-left: .35rem;
    padding: .15rem .6rem;
    border-radius: 99px;
    background: #eef0ff;
    color: #4338ca;
    font-size: .76rem;
    font-weight: 700;
    overflow-wrap: anywhere;
}

/* ---------- Tabel ---------- */
.cus-table td {
    vertical-align: middle;
}

.cus-name {
    font-weight: 700;
    overflow-wrap: anywhere;
}

.cus-contact {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    font-size: .85rem;
}

.cus-contact span {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    color: var(--app-muted);
}

.cus-address {
    font-size: .85rem;
    color: var(--app-muted);
    max-width: 250px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Tombol aksi berikon */
.cus-act {
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

.cus-act:hover {
    transform: translateY(-1px);
}

.cus-act.is-edit:hover {
    background: #e7e9fb;
    color: #4338ca;
}

.cus-act.is-del:hover {
    background: #fee2e2;
    color: #b91c1c;
}

.cus-act:focus-visible {
    outline: 2px solid #a5b4fc;
    outline-offset: 2px;
}

/* ---------- Kosong ---------- */
.cus-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.cus-empty-ico {
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

.cus-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.cus-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Modal hapus ---------- */
.cus-del-ico {
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

.cus-del-name {
    display: inline-block;
    max-width: 100%;
    padding: .2rem .65rem;
    border-radius: 8px;
    background: #f1f3f9;
    overflow-wrap: anywhere;
}

/* ---------- Ponsel ---------- */
@media (max-width: 767.98px) {
    .cus-head {
        padding: 1.1rem;
    }

    .cus-table,
    .cus-table tbody,
    .cus-table tr,
    .cus-table td {
        display: block;
        width: 100%;
    }

    .cus-table thead {
        display: none;
    }

    .cus-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .cus-table tbody tr:last-child {
        border-bottom: 0;
    }

    .cus-table tbody td {
        padding: .3rem 0;
        border: 0;
        text-align: left !important;
    }

    .cus-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .cus-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .cus-address {
        max-width: 100%;
        white-space: normal;
    }

    .cus-table td.cus-td-act {
        margin-top: .5rem;
    }
}

@media (prefers-reduced-motion: reduce) {

    .cus-act,
    .cus-head .btn-light {
        transition: none;
    }
}
</style>

<!-- Header -->
<div class="cus-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="cus-head-ico" aria-hidden="true"><i class="bi bi-people"></i></span>
        <div>
            <h2>Customer</h2>
            <p>Kelola data pelanggan untuk keperluan penjualan.</p>
        </div>
    </div>

    <?php if ($canWrite): ?>
    <a href="<?= site_url('customers/new') ?>" class="btn btn-light">
        <i class="bi bi-plus-lg me-1"></i>Tambah Customer
    </a>
    <?php endif; ?>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar -->
    <div class="cus-bar">
        <form method="get" action="<?= site_url('customers') ?>" class="cus-search">
            <label class="cus-search-field mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" maxlength="100" placeholder="Cari nama customer"
                    value="<?= esc($search, 'attr') ?>" aria-label="Cari nama customer" autocomplete="off">
            </label>
            <button type="submit" class="btn btn-primary px-3">Cari</button>
            <?php if ($search !== ''): ?>
            <a href="<?= site_url('customers') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($rows !== []): ?>
        <div class="cus-meta">
            Menampilkan <b><?= $offset + 1 ?>&ndash;<?= $offset + count($rows) ?></b> dari <b><?= $total ?></b> customer
            <?php if ($search !== ''): ?>
            <span class="cus-q"><i class="bi bi-funnel"></i><?= esc($search) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 cus-table">
            <thead>
                <tr>
                    <th>Nama Customer</th>
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
                        <div class="cus-empty">
                            <div class="cus-empty-ico"><i
                                    class="bi <?= $search !== '' ? 'bi-search' : 'bi-people' ?>"></i></div>
                            <?php if ($search !== ''): ?>
                            <h3>Customer tidak ditemukan</h3>
                            <p>Tidak ada customer yang cocok dengan &ldquo;<?= esc($search) ?>&rdquo;.</p>
                            <a href="<?= site_url('customers') ?>" class="btn btn-outline-secondary btn-sm">Reset
                                pencarian</a>
                            <?php else: ?>
                            <h3>Belum ada customer</h3>
                            <?php if ($canWrite): ?>
                            <p>Tambahkan customer pertama untuk mulai mencatat data pelanggan.</p>
                            <a href="<?= site_url('customers/new') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Customer
                            </a>
                            <?php else: ?>
                            <p class="mb-0">Data customer akan tampil di sini.</p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <div class="cus-name"><?= esc($row['name']) ?></div>
                    </td>
                    <td data-th="Kontak">
                        <div class="cus-contact">
                            <span><i class="bi bi-telephone"></i>
                                <?= esc($row['phone'] ?? '') !== '' ? esc($row['phone']) : '-' ?></span>
                            <span><i class="bi bi-envelope"></i>
                                <?= esc($row['email'] ?? '') !== '' ? esc($row['email']) : '-' ?></span>
                        </div>
                    </td>
                    <td data-th="Alamat">
                        <div class="cus-address" title="<?= esc($row['address'] ?? '') ?>">
                            <?= $row['address'] !== null && $row['address'] !== '' ? esc(mb_strimwidth($row['address'], 0, 80, '...')) : '-' ?>
                        </div>
                    </td>
                    <?php if ($canWrite): ?>
                    <td class="text-end text-nowrap cus-td-act">
                        <a href="<?= site_url('customers/' . $row['id'] . '/edit') ?>" class="cus-act is-edit"
                            title="Ubah customer" aria-label="Ubah <?= esc($row['name'], 'attr') ?>">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <button type="button" class="cus-act is-del" data-bs-toggle="modal"
                            data-bs-target="#deleteCustomerModal" title="Hapus customer"
                            aria-label="Hapus <?= esc($row['name'], 'attr') ?>"
                            data-action="<?= site_url('customers/' . $row['id'] . '/delete') ?>"
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
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <form method="post" action="" id="deleteCustomerForm" class="modal-content border-0"
            style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="cus-del-ico"><i class="bi bi-trash3"></i></div>
                <h5 class="fw-bold mb-2" id="deleteCustomerTitle">Hapus customer?</h5>
                <p class="mb-2 text-muted small">Customer berikut akan dihapus dan tindakan ini tidak dapat dibatalkan.
                </p>
                <strong class="cus-del-name" id="deleteCustomerLabel"></strong>
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
    var modal = document.getElementById('deleteCustomerModal');
    var form = document.getElementById('deleteCustomerForm');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function(event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        form.action = btn.getAttribute('data-action');
        document.getElementById('deleteCustomerLabel').textContent = btn.getAttribute('data-label');
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