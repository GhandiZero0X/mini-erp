<?php
/**
 * UI-15 Histori mutasi stok. Variabel: $rows, $pager, $products, $filter, $errors.
 * Filter produk opsional; periode default bulan berjalan; urut dari yang terbaru.
 */
$this->extend('layouts/main');

/** @var array{product_id:int,start:string,end:string} $filter */
$typeBadge = static function (string $t): string {
    return match ($t) {
        'masuk' => 'text-bg-success',
        'keluar' => 'text-bg-danger',
        'penyesuaian' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
};

// Link referensi hanya untuk pengguna yang berhak membuka dokumen tujuan
$refUrl = static function (array $r): ?string {
    $type = (string) $r['reference_type'];
    $id = $r['reference_id'];
    if ($id === null) {
        return null;
    }

    if (str_starts_with($type, 'GOODS_RECEIPT') && auth_can('goods_receipt', 'R')) {
        return site_url('goods-receipts/' . $id);
    }
    if (str_starts_with($type, 'DELIVERY') && auth_can('delivery', 'R')) {
        return site_url('deliveries/' . $id);
    }
    return null;
};
?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3">Histori Mutasi Stok</h1>

<form method="get" action="<?= site_url('stock-movements') ?>" class="row g-2 mb-3">
    <div class="col-12 col-md-4 col-lg-3">
        <label class="form-label small text-muted mb-1">Produk</label>
        <select name="product_id" class="form-select">
            <option value="">Semua produk</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $filter['product_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= esc($p['code'] . ' - ' . $p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small text-muted mb-1">Tanggal mulai</label>
        <input type="date" name="start" class="form-control<?= isset($errors['start']) ? ' is-invalid' : '' ?>"
            value="<?= esc($filter['start'], 'attr') ?>">
        <?php if (isset($errors['start'])): ?>
            <div class="invalid-feedback d-block"><?= esc($errors['start']) ?></div>
        <?php endif; ?>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small text-muted mb-1">Tanggal akhir</label>
        <input type="date" name="end" class="form-control<?= isset($errors['end']) ? ' is-invalid' : '' ?>"
            value="<?= esc($filter['end'], 'attr') ?>">
        <?php if (isset($errors['end'])): ?>
            <div class="invalid-feedback d-block"><?= esc($errors['end']) ?></div>
        <?php endif; ?>
    </div>
    <div class="col-auto align-self-end">
        <button type="submit" class="btn btn-outline-primary">Tampilkan</button>
    </div>
</form>

<?php if (isset($errors['period'])): ?>
    <div class="alert alert-danger py-2"><?= esc($errors['period']) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Jenis</th>
                <th class="text-end">Kuantitas</th>
                <th>Dokumen Referensi</th>
                <th>Dicatat oleh</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <?= isset($errors['period']) ? 'Periode tidak valid.' : 'Tidak ada mutasi pada periode ini.' ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r):
                    $qty = $r['quantity_change'];
                    $sign = $qty > 0 ? '+' : '';
                    $url = $refUrl($r);
                    ?>
                    <tr>
                        <td class="text-nowrap"><?= esc(format_datetime($r['movement_date'])) ?></td>
                        <td>
                            <span class="text-nowrap fw-semibold"><?= esc($r['code']) ?></span>
                            - <?= esc($r['product_name']) ?>
                            <span class="text-muted small">(<?= esc($r['unit']) ?>)</span>
                        </td>
                        <td><span
                                class="badge <?= $typeBadge($r['movement_type']) ?>"><?= esc(ucfirst($r['movement_type'])) ?></span>
                        </td>
                        <td class="text-end fw-semibold <?= $qty > 0 ? 'text-success' : ($qty < 0 ? 'text-danger' : '') ?>">
                            <?= esc($sign . number_format($qty, 0, ',', '.')) ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if ($r['reference_number'] === null): ?>
                                <span class="text-muted">-</span>
                            <?php elseif ($url !== null): ?>
                                <a href="<?= $url ?>"><?= esc($r['reference_number']) ?></a>
                            <?php else: ?>
                                <?= esc($r['reference_number']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($r['created_by_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($pager !== null): ?>
    <?= $pager->links('default', 'bootstrap_full') ?>
<?php endif; ?>

<?= $this->endSection() ?>