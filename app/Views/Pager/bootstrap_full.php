<?php
/**
 * Custom Full Pager Template - uses project CSS classes
 */
?>

<?php $pager->setSurroundCount(2) ?>
<nav aria-label="Page navigation">
    <ul class="pager-list">

        <?php if ($pager->hasPrevious()) : ?>
            <li class="pager-item">
                <a class="pager-link" href="<?= $pager->getFirst() ?>" aria-label="First">&laquo;</a>
            </li>
            <li class="pager-item">
                <a class="pager-link" href="<?= $pager->getPrevious() ?>" aria-label="Previous">&lsaquo;</a>
            </li>
        <?php endif ?>

        <?php foreach ($pager->links() as $link) : ?>
            <li class="pager-item">
                <a class="<?= $link['active'] ? 'pager-active' : 'pager-link' ?>" href="<?= $link['uri'] ?>">
                    <?= $link['title'] ?>
                </a>
            </li>
        <?php endforeach ?>

        <?php if ($pager->hasNext()) : ?>
            <li class="pager-item">
                <a class="pager-link" href="<?= $pager->getNext() ?>" aria-label="Next">&rsaquo;</a>
            </li>
            <li class="pager-item">
                <a class="pager-link" href="<?= $pager->getLast() ?>" aria-label="Last">&raquo;</a>
            </li>
        <?php endif ?>

    </ul>
</nav>
