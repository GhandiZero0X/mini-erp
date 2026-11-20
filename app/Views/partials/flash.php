<?php
// Flash message terstandar (NFR-06): hijau = berhasil, merah = gagal, kuning = peringatan.
$types = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning'];
foreach ($types as $key => $class):
    $message = session()->getFlashdata($key);
    if ($message): ?>
        <div class="alert alert-<?= $class ?> alert-dismissible fade show" role="alert">
            <?= esc($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif;
endforeach; ?>
