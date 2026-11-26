<?php
/**
 * @var array $rows
 * @var \CodeIgniter\Pager\Pager|null $pager
 * @var bool $canWrite
 */
$search  = $search ?? '';
$perPage = $pager ? ((int) $pager->getPerPage() ?: 10) : 10;
$page    = $pager ? $pager->getCurrentPage() : 1;
$offset  = ($page - 1) * $perPage;
$total   = max($pager ? (int) $pager->getTotal() : 0, $offset + count($rows));
$colspan = $canWrite ? 4 : 3;
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* Prefiks "cat-" supaya tidak bentrok dengan Bootstrap / dashboard (dx-) / produk (prd-) */
.cat-head {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.35rem 1.6rem;
    color: #fff;
    background: linear-gradient(125deg, #4338ca 0%, #6d4aed 55%, #0ea5c6 130%);
    box-shadow: 0 18px 40px -24px rgba(67, 56, 202, .7);
}

.cat-head::before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
}

.cat-head>* {
    position: relative;
    z-index: 1;
}

.cat-head-ico {
    flex: 0 0 auto;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255, 255, 255, .2);
    font-size: 1.35rem;
}

.cat-head h2 {
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.01em;
    margin: 0;
}

.cat-head p {
    margin: .1rem 0 0;
    font-size: .86rem;
    opacity: .88;
}

.cat-head .btn-light {
    color: #4338ca;
    font-weight: 700;
    border: 0;
    box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .5);
}

.cat-head .btn-light:hover {
    background: #fff;
    transform: translateY(-1px);
}

/* ---------- Toolbar ---------- */
.cat-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--app-line);
}

.cat-search {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    flex: 1 1 320px;
    max-width: 520px;
}

.cat-search-field {
    position: relative;
    flex: 1 1 220px;
}

.cat-search-field i {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa1b5;
    pointer-events: none;
}

.cat-search-field input {
    padding-left: 2.3rem;
    border-radius: 99px;
    background: #f8f9fd;
}

.cat-meta {
    color: var(--app-muted);
    font-size: .82rem;
}

.cat-meta b {
    color: var(--app-ink);
}

.cat-q {
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
.cat-table td {
    vertical-align: middle;
}

.cat-name {
    font-weight: 700;
    overflow-wrap: anywhere;
}

.cat-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .25rem .75rem;
    border-radius: 99px;
    background: #f1f3f9;
    color: #3b4358;
    font-size: .82rem;
    font-weight: 700;
    border: 1px solid var(--app-line);
}

/* Tombol aksi berikon */
.cat-act {
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

.cat-act:hover {
    transform: translateY(-1px);
}

.cat-act.is-edit:hover {
    background: #e7e9fb;
    color: #4338ca;
}

.cat-act.is-del:hover {
    background: #fee2e2;
    color: #b91c1c;
}

.cat-act:focus-visible {
    outline: 2px solid #a5b4fc;
    outline-offset: 2px;
}

/* ---------- Kosong ---------- */
.cat-empty {
    padding: 3.2rem 1rem;
    text-align: center;
}

.cat-empty-ico {
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

.cat-empty h3 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: .25rem;
}

.cat-empty p {
    color: var(--app-muted);
    font-size: .88rem;
    margin-bottom: 1rem;
}

/* ---------- Modal hapus ---------- */
.cat-del-ico {
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

.cat-del-name {
    display: inline-block;
    max-width: 100%;
    padding: .2rem .65rem;
    border-radius: 8px;
    background: #f1f3f9;
    overflow-wrap: anywhere;
}

/* ---------- Ponsel: baris tabel menjadi kartu ---------- */
@media (max-width: 767.98px) {
    .cat-head {
        padding: 1.1rem;
    }

    .cat-table,
    .cat-table tbody,
    .cat-table tr,
    .cat-table td {
        display: block;
        width: 100%;
    }

    .cat-table thead {
        display: none;
    }

    .cat-table tbody tr {
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--app-line);
    }

    .cat-table tbody tr:last-child {
        border-bottom: 0;
    }

    .cat-table tbody td {
        padding: .3rem 0;
        border: 0;
        background: transparent;
        box-shadow: none;
        text-align: left !important;
    }

    .cat-table td[data-th] {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .cat-table td[data-th]::before {
        content: attr(data-th);
        color: var(--app-muted);
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .cat-table td.cat-td-act {
        margin-top: .4rem;
    }
}

@media (prefers-reduced-motion: reduce) {

    .cat-act,
    .cat-head .btn-light {
        transition: none;
    }
}
</style>

<!-- Header halaman -->
<div class="cat-head d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="cat-head-ico" aria-hidden="true"><i class="bi bi-tags"></i></span>
        <div>
            <h2>Kategori Produk</h2>
            <p>Kelola kategori untuk mengelompokkan produk.</p>
        </div>
    </div>

    <?php if ($canWrite): ?>
    <a href="<?= site_url('categories/new') ?>" class="btn btn-light">
        <i class="bi bi-plus-lg me-1"></i>Tambah Kategori
    </a>
    <?php endif; ?>
</div>

<div class="mb-3"></div>

<div class="card overflow-hidden">
    <!-- Toolbar & Pencarian -->
    <div class="cat-bar">
        <form method="get" action="<?= site_url('categories') ?>" class="cat-search">
            <label class="cat-search-field mb-0">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" maxlength="100" placeholder="Cari nama kategori"
                    value="<?= esc($search, 'attr') ?>" aria-label="Cari nama kategori" autocomplete="off">
            </label>
            <button type="submit" class="btn btn-primary px-3">Cari</button>
            <?php if ($search !== ''): ?>
            <a href="<?= site_url('categories') ?>" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($rows !== []): ?>
        <div class="cat-meta">
            Menampilkan <b><?= $offset + 1 ?>&ndash;<?= $offset + count($rows) ?></b> dari <b><?= $total ?></b> kategori
            <?php if ($search !== ''): ?>
            <span class="cat-q"><i class="bi bi-funnel"></i><?= esc($search) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabel Data -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 cat-table">
            <thead>
                <tr>
                    <th style="width: 70px;" class="text-center">No</th>
                    <th>Nama Kategori</th>
                    <th class="text-center" style="width: 160px;">Jumlah Produk</th>
                    <?php if ($canWrite): ?>
                    <th class="text-end" style="width: 110px;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= $colspan ?>" class="p-0">
                        <div class="cat-empty">
                            <div class="cat-empty-ico"><i
                                    class="bi <?= $search !== '' ? 'bi-search' : 'bi-folder-x' ?>"></i></div>
                            <?php if ($search !== ''): ?>
                            <h3>Kategori tidak ditemukan</h3>
                            <p>Tidak ada kategori yang cocok dengan &ldquo;<?= esc($search) ?>&rdquo;. Coba kata kunci
                                lain.</p>
                            <a href="<?= site_url('categories') ?>" class="btn btn-outline-secondary btn-sm">Reset
                                pencarian</a>
                            <?php else: ?>
                            <h3>Belum ada kategori</h3>
                            <?php if ($canWrite): ?>
                            <p>Tambahkan kategori pertama untuk mulai mengelompokkan produk.</p>
                            <a href="<?= site_url('categories/new') ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Kategori
                            </a>
                            <?php else: ?>
                            <p class="mb-0">Kategori akan tampil di sini setelah ditambahkan.</p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php
                    // Nomor urut berlanjut antar halaman
                    $no = $offset;
                    foreach ($rows as $row):
                        $no++; ?>
                <tr>
                    <td class="text-center text-muted fw-medium"><?= $no ?></td>
                    <td>
                        <div class="cat-name"><?= esc($row['name']) ?></div>
                    </td>
                    <td class="text-center" data-th="Jumlah Produk">
                        <span class="cat-badge">
                            <i class="bi bi-box-seam"></i> <?= (int) $row['product_count'] ?> Item
                        </span>
                    </td>
                    <?php if ($canWrite): ?>
                    <td class="text-end text-nowrap cat-td-act">
                        <a href="<?= site_url('categories/' . $row['id'] . '/edit') ?>" class="cat-act is-edit"
                            title="Ubah kategori" aria-label="Ubah <?= esc($row['name'], 'attr') ?>">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <button type="button" class="cat-act is-del" data-bs-toggle="modal"
                            data-bs-target="#deleteCategoryModal" title="Hapus kategori"
                            aria-label="Hapus <?= esc($row['name'], 'attr') ?>"
                            data-action="<?= esc(site_url('categories/' . $row['id'] . '/delete'), 'attr') ?>"
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
<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <form method="post" action="" id="deleteCategoryForm" class="modal-content border-0"
            style="border-radius: 20px;">
            <?= csrf_field() ?>
            <div class="modal-body text-center p-4 pb-3">
                <div class="cat-del-ico"><i class="bi bi-trash3"></i></div>
                <h5 class="fw-bold mb-2" id="deleteCategoryTitle">Hapus kategori?</h5>
                <p class="mb-2 text-muted small">Kategori berikut akan dihapus dan tindakan ini tidak dapat dibatalkan.
                </p>
                <strong class="cat-del-name" id="deleteCategoryLabel"></strong>
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
    var modal = document.getElementById('deleteCategoryModal');
    var form = document.getElementById('deleteCategoryForm');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function(event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        form.action = btn.getAttribute('data-action');
        document.getElementById('deleteCategoryLabel').textContent = btn.getAttribute('data-label');
    });
    // Cegah klik ganda saat form dikirim
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