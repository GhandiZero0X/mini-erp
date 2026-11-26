<?php
/**
 * UI-13 Form Delivery. Variabel: $title, $so (header + 'items' dengan 'remaining', 'stock_available'),
 * $action, $today, $errors, $old.
 */
$this->extend('layouts/main');

$dateVal = (string) (($old['delivery_date'] ?? '') !== '' ? $old['delivery_date'] : $today);
$oldQty  = (isset($old['quantities']) && is_array($old['quantities'])) ? $old['quantities'] : [];
$qtyErr  = (isset($errors['quantities']) && is_array($errors['quantities'])) ? $errors['quantities'] : [];
?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3"><?= esc($title) ?></h1>

<?php if (session('error')): ?>
<div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small">Nomor SO</div>
                <div class="fw-semibold"><?= esc($so['so_number']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Customer</div>
                <div><?= esc($so['customer_name']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Tanggal SO</div>
                <div><?= esc(format_date($so['so_date'])) ?></div>
            </div>
        </div>
    </div>
</div>

<form method="post" action="<?= esc($action, 'attr') ?>" class="card card-body" novalidate>
    <?= csrf_field() ?>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Nomor Delivery</label>
            <input type="text" class="form-control" value="Otomatis saat disimpan" disabled>
        </div>
        <div class="col-md-4 mb-3">
            <label for="delivery_date" class="form-label">Tanggal Pengiriman <span class="text-danger">*</span></label>
            <input type="date" id="delivery_date" name="delivery_date"
                class="form-control<?= isset($errors['delivery_date']) ? ' is-invalid' : '' ?>"
                value="<?= esc($dateVal, 'attr') ?>">
            <?php if (isset($errors['delivery_date'])): ?>
            <div class="invalid-feedback d-block"><?= esc($errors['delivery_date']) ?></div>
            <?php endif; ?>
            <div id="schedule-note" class="alert alert-info py-2 mt-2 d-none">
                <i class="bi bi-info-circle me-1"></i> Karena tanggal pengiriman di masa depan, status DO akan menjadi
                <strong>Terjadwal</strong>.
            </div>
        </div>
    </div>

    <h2 class="h6 mt-2">Barang Dikirim</h2>
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
                    <th class="text-end">Sudah Dikirim</th>
                    <th class="text-end">Sisa</th>
                    <th class="text-end">Stok Tersedia</th>
                    <th style="width: 170px;" class="text-end">Dikirim Sekarang</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($so['items'] as $it):
                $id       = (int) $it['id'];
                $value    = array_key_exists($id, $oldQty) ? (string) $oldQty[$id] : '0';
                $done     = $it['remaining'] === 0;
                $lowStock = $it['stock_available'] < $it['remaining'];
            ?>
                <tr<?= $done ? ' class="table-light text-muted"' : '' ?>>
                    <td><span class="text-nowrap"><?= esc($it['code']) ?></span> - <?= esc($it['name']) ?></td>
                    <td><?= esc($it['unit']) ?></td>
                    <td class="text-end"><?= number_format($it['quantity'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($it['delivered'], 0, ',', '.') ?></td>
                    <td class="text-end fw-semibold"><?= number_format($it['remaining'], 0, ',', '.') ?></td>
                    <td class="text-end">
                        <span class="<?= $lowStock ? 'text-danger fw-semibold' : '' ?>">
                            <?= number_format($it['stock_available'], 0, ',', '.') ?>
                        </span>
                    </td>
                    <td>
                        <input type="text" inputmode="numeric" autocomplete="off" name="quantities[<?= $id ?>]"
                            value="<?= esc($value, 'attr') ?>"
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
        Isi jumlah yang benar-benar dikirim (maksimal sebesar sisa; isi 0 bila belum dikirim).
        Stok berkurang sesuai jumlah ini. Bila stok tidak mencukupi, sistem akan menolak seluruh pengiriman.
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?= site_url('sales-orders') ?>" class="btn btn-secondary">Batal</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dateInput = document.getElementById('delivery_date');
    var noteDiv = document.getElementById('schedule-note');

    // Dapatkan tanggal hari ini dalam format YYYY-MM-DD
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
    var yyyy = today.getFullYear();
    var todayStr = yyyy + '-' + mm + '-' + dd;

    function checkSchedule() {
        var selectedDate = dateInput.value;
        if (selectedDate > todayStr) {
            noteDiv.classList.remove('d-none');
        } else {
            noteDiv.classList.add('d-none');
        }
    }

    // Panggil saat halaman pertama dimuat (untuk menangani jika ada validasi error)
    checkSchedule();

    // Panggil setiap kali user mengubah tanggal
    dateInput.addEventListener('change', checkSchedule);
});
</script>

<?= $this->endSection() ?>