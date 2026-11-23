<?php
/**
 * UI-04 Produk (daftar). Variabel: $rows, $pager, $search, $canWrite.
 * CATATAN: nama layout & section di bawah mengikuti dugaan 'layouts/main' / 'content'.
 * Samakan dengan baris extend/section di Views/categories/index.php milik Anda.
 */
$this->extend('layouts/main');

// Rupiah dengan pemisah ribuan titik; desimal hanya tampil bila bukan .00
$rupiah = static function ($v): string {
    $n = (float) $v;

    return 'Rp ' . number_format($n, fmod($n, 1) === 0.0 ? 0 : 2, ',', '.');
};
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Produk</h1>
    <?php if ($canWrite): ?>
    <a href="<?= site_url('products/new') ?>" class="btn btn-primary">Tambah</a>
    <?php endif; ?>
</div>

<form method="get" action="<?= site_url('products') ?>" class="row g-2 mb-3">
    <div class="col-12 col-md-6 col-lg-4">
        <input type="search" name="q" class="form-control" maxlength="100" placeholder="Cari kode atau nama produk"
            value="<?= esc($search) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary">Cari</button>
        <?php if ($search !== ''): ?>
        <a href="<?= site_url('products') ?>" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Satuan</th>
                <th class="text-end">Harga Beli</th>
                <th class="text-end">Harga Jual</th>
                <?php if ($canWrite): ?><th class="text-end">Aksi</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
            <tr>
                <td colspan="<?= $canWrite ? 7 : 6 ?>" class="text-center text-muted py-4">Tidak ada data</td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="text-nowrap"><?= esc($row['code']) ?></td>
                <td><?= esc($row['name']) ?></td>
                <td><?= esc($row['category_name']) ?></td>
                <td><?= esc($row['unit']) ?></td>
                <td class="text-end text-nowrap"><?= esc($rupiah($row['purchase_price'])) ?></td>
                <td class="text-end text-nowrap"><?= esc($rupiah($row['selling_price'])) ?></td>
                <?php if ($canWrite): ?>
                <td class="text-end text-nowrap">
                    <a href="<?= site_url('products/' . $row['id'] . '/edit') ?>"
                        class="btn btn-sm btn-outline-primary">Ubah</a>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                        data-bs-target="#deleteProductModal"
                        data-action="<?= site_url('products/' . $row['id'] . '/delete') ?>"
                        data-label="<?= esc($row['code'] . ' - ' . $row['name'], 'attr') ?>">Hapus</button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $pager->links('default', 'bootstrap_full') ?>

<?php if ($canWrite): ?>
<!-- Dialog konfirmasi hapus (UC-04 langkah 9, A7) -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="" id="deleteProductForm" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="deleteProductTitle">Hapus Produk</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                Hapus produk <strong id="deleteProductLabel"></strong>? Tindakan ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
// Dijalankan setelah DOM siap agar aman bila Bootstrap JS dimuat di akhir halaman.
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('deleteProductModal');
    modal.addEventListener('show.bs.modal', function(event) {
        var btn = event.relatedTarget;
        document.getElementById('deleteProductForm').action = btn.getAttribute('data-action');
        document.getElementById('deleteProductLabel').textContent = btn.getAttribute('data-label');
    });
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>