<?php
/**
 * UI-10 Form Goods Receipt. Variabel: $title, $po (header + 'items' dengan 'received' dan 'remaining'),
 * $action, $today, $errors, $old.
 * Kuantitas diterima diisi manual (default 0): stok hanya bertambah sesuai barang yang benar-benar diterima (BR-03).
 */
$this->extend('layouts/main');

$dateVal = (string) (($old['receipt_date'] ?? '') !== '' ? $old['receipt_date'] : $today);
$oldQty  = (isset($old['quantities']) && is_array($old['quantities'])) ? $old['quantities'] : [];
$qtyErr  = (isset($errors['quantities']) && is_array($errors['quantities'])) ? $errors['quantities'] : [];
?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3"><?= esc($title) ?></h1>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small">Nomor PO</div>
                <div class="fw-semibold"><?= esc($po['po_number']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Supplier</div>
                <div><?= esc($po['supplier_name']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Tanggal PO</div>
                <div><?= esc(format_date($po['po_date'])) ?></div>
            </div>
        </div>
    </div>
</div>

<form method="post" action="<?= esc($action, 'attr') ?>" class="card card-body" novalidate>
    <?= csrf_field() ?>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Nomor GR</label>
            <input type="text" class="form-control" value="Otomatis saat disimpan" disabled>
        </div>
        <div class="col-md-4 mb-3">
            <label for="receipt_date" class="form-label">Tanggal Penerimaan <span class="text-danger">*</span></label>
            <input type="date" id="receipt_date" name="receipt_date"
                   class="form-control<?= isset($errors['receipt_date']) ? ' is-invalid' : '' ?>"
                   value="<?= esc($dateVal, 'attr') ?>">
            <?php if (isset($errors['receipt_date'])): ?>
                <div class="invalid-feedback d-block"><?= esc($errors['receipt_date']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <h2 class="h6 mt-2">Barang Diterima</h2>
    <?php if (isset($errors['items'])): ?>
        <div class="alert alert-danger py-2"><?= esc($errors['items']) ?></div>
    <?php endif; ?>

    <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produk</th>
                    <th>Satuan</th>
                    <th class="text-end">Dipesan</th>
                    <th class="text-end">Sudah Diterima</th>
                    <th class="text-end">Sisa</th>
                    <th style="width: 170px;" class="text-end">Diterima Sekarang</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($po['items'] as $it):
                $id    = (int) $it['id'];
                $value = array_key_exists($id, $oldQty) ? (string) $oldQty[$id] : '0';
                $done  = $it['remaining'] === 0;
            ?>
                <tr<?= $done ? ' class="table-light text-muted"' : '' ?>>
                    <td><span class="text-nowrap"><?= esc($it['code']) ?></span> - <?= esc($it['name']) ?></td>
                    <td><?= esc($it['unit']) ?></td>
                    <td class="text-end"><?= number_format($it['quantity'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($it['received'], 0, ',', '.') ?></td>
                    <td class="text-end fw-semibold"><?= number_format($it['remaining'], 0, ',', '.') ?></td>
                    <td>
                        <input type="text" inputmode="numeric" autocomplete="off"
                               name="quantities[<?= $id ?>]" value="<?= esc($value, 'attr') ?>"
                               class="form-control form-control-sm text-end<?= isset($qtyErr[$id]) ? ' is-invalid' : '' ?>"
                               <?= $done ? 'readonly' : '' ?>>
                        <?php if (isset($qtyErr[$id])): ?>
                            <div class="invalid-feedback d-block"><?= esc($qtyErr[$id]) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="form-text mb-3">
        Isi jumlah yang benar-benar diterima (maksimal sebesar sisa; isi 0 bila belum datang). Stok bertambah sesuai jumlah ini.
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?= site_url('purchase-orders') ?>" class="btn btn-secondary">Batal</a>
    </div>
</form>

<?= $this->endSection() ?>
