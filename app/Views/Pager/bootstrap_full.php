<?php
/**
 * @var \CodeIgniter\Pager\PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>
<nav aria-label="Navigasi Halaman" class="mt-3">
    <ul class="pagination">

        <!-- Tombol First (Awal) -->
        <li class="page-item <?= $pager->hasPrevious() ? '' : 'disabled' ?>">
            <a class="page-link" href="<?= $pager->hasPrevious() ? $pager->getFirst() : '#' ?>" aria-label="Awal">
                <span aria-hidden="true">&laquo;&laquo;</span>
            </a>
        </li>

        <!-- Tombol Previous (Sebelumnya) -->
        <li class="page-item <?= $pager->hasPrevious() ? '' : 'disabled' ?>">
            <a class="page-link" href="<?= $pager->hasPrevious() ? $pager->getPrevious() : '#' ?>"
                aria-label="Sebelumnya">
                <span aria-hidden="true">&laquo;</span> Prev
            </a>
        </li>

        <!-- Angka Halaman -->
        <?php foreach ($pager->links() as $link) : ?>
        <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= $link['uri'] ?>">
                <?= $link['title'] ?>
            </a>
        </li>
        <?php endforeach ?>

        <!-- Tombol Next (Selanjutnya) -->
        <li class="page-item <?= $pager->hasNext() ? '' : 'disabled' ?>">
            <a class="page-link" href="<?= $pager->hasNext() ? $pager->getNext() : '#' ?>" aria-label="Selanjutnya">
                Next <span aria-hidden="true">&raquo;</span>
            </a>
        </li>

        <!-- Tombol Last (Akhir) -->
        <li class="page-item <?= $pager->hasNext() ? '' : 'disabled' ?>">
            <a class="page-link" href="<?= $pager->hasNext() ? $pager->getLast() : '#' ?>" aria-label="Akhir">
                <span aria-hidden="true">&raquo;&raquo;</span>
            </a>
        </li>

    </ul>
</nav>