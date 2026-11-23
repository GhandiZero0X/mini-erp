<?php
/** UI-06 Customer (daftar). Variabel: $rows, $pager, $canWrite. */
$this->extend('layouts/main');
?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Customer</h1>
    <?php if ($canWrite): ?>
    <a href="<?= site_url('customers/new') ?>" class="btn btn-primary">Tambah</a>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Telepon</th>
                <th>Email</th>
                <?php if ($canWrite): ?><th class="text-end">Aksi</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
            <tr>
                <td colspan="<?= $canWrite ? 5 : 4 ?>" class="text-center text-muted py-4">Tidak ada data</td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= esc($row['name']) ?></td>
                <td><?= $row['address'] !== null && $row['address'] !== '' ? esc(mb_strimwidth($row['address'], 0, 80, '...')) : '-' ?>
                </td>
                <td class="text-nowrap"><?= esc($row['phone'] ?? '') !== '' ? esc($row['phone']) : '-' ?></td>
                <td><?= esc($row['email'] ?? '') !== '' ? esc($row['email']) : '-' ?></td>
                <?php if ($canWrite): ?>
                <td class="text-end text-nowrap">
                    <a href="<?= site_url('customers/' . $row['id'] . '/edit') ?>"
                        class="btn btn-sm btn-outline-primary">Ubah</a>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                        data-bs-target="#deleteCustomerModal"
                        data-action="<?= site_url('customers/' . $row['id'] . '/delete') ?>"
                        data-label="<?= esc($row['name'], 'attr') ?>">Hapus</button>
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
<!-- Dialog konfirmasi hapus (UC-06 langkah 9, A5) -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="" id="deleteCustomerForm" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="deleteCustomerTitle">Hapus Customer</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                Hapus customer <strong id="deleteCustomerLabel"></strong>? Tindakan ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('deleteCustomerModal');
    modal.addEventListener('show.bs.modal', function(event) {
        var btn = event.relatedTarget;
        document.getElementById('deleteCustomerForm').action = btn.getAttribute('data-action');
        document.getElementById('deleteCustomerLabel').textContent = btn.getAttribute('data-label');
    });
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>