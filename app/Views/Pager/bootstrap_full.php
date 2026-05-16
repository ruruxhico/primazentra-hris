<?php
/**
 * Bootstrap 5 Full Pager Template
 */
?>

<?php if (isset($pager) && ($pager->hasPrevious() || $pager->hasNext())) : ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-end">

        <!-- Previous Page Link -->
        <?php if ($pager->hasPrevious()) : ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="First">
                    &laquo;
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getPrevious() ?>" aria-label="Previous">
                    &lsaquo;
                </a>
            </li>
        <?php endif ?>

        <!-- Page Number Links -->
        <?php foreach ($pager->links() as $link) : ?>
            <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= $link['uri'] ?>">
                    <?= $link['title'] ?>
                </a>
            </li>
        <?php endforeach ?>

        <!-- Next Page Link -->
        <?php if ($pager->hasNext()) : ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getNext() ?>" aria-label="Next">
                    &rsaquo;
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Last">
                    &raquo;
                </a>
            </li>
        <?php endif ?>

    </ul>
</nav>
<?php endif ?>
