<?php
/**
 * @var array $rows
 * @var \CodeIgniter\Pager\Pager|null $pager
 * @var bool $canWrite
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Kategori Produk</h1>
    <?php if ($canWrite): ?>
        <a href="<?= site_url('categories/new') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Tambah
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">No</th>
                    <th>Nama Kategori</th>
                    <th class="text-center" style="width: 160px;">Jumlah Produk</th>
                    <?php if ($canWrite): ?>
                        <th class="text-end" style="width: 180px;">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="<?= $canWrite ? 4 : 3 ?>" class="text-center text-muted py-4">Tidak ada data.</td>
                    </tr>
                <?php endif; ?>

                <?php
                // Nomor urut berlanjut antar halaman
                $no = (($pager ? $pager->getCurrentPage() : 1) - 1) * 10;
                foreach ($rows as $row):
                    $no++; ?>
                    <tr>
                        <td><?= $no ?></td>
                        <td><?= esc($row['name']) ?></td>
                        <td class="text-center"><span
                                class="badge text-bg-light border"><?= (int) $row['product_count'] ?></span></td>
                        <?php if ($canWrite): ?>
                            <td class="text-end text-nowrap">
                                <a href="<?= site_url('categories/' . $row['id'] . '/edit') ?>"
                                    class="btn btn-sm btn-outline-primary">Ubah</a>
                                <form method="post" action="<?= site_url('categories/' . $row['id'] . '/delete') ?>"
                                    class="d-inline"
                                    onsubmit="return confirm('<?= esc('Hapus kategori ' . $row['name'] . '?', 'js') ?>')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager !== null && $pager->getPageCount() > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-end">
            <?= $pager->links('default', 'bootstrap_full') ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>