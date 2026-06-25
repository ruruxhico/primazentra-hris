<?php
/**
 * Custom Full Pager Template - uses project CSS classes.
 * Preserves all current GET parameters so filters are not lost when changing pages.
 */

// Build a base query string from all current GET params, excluding any page_* params
// (the pager appends the correct page_* param to each link itself).
$currentParams = array_filter($_GET, static fn($k) => !preg_match('/^page(_|$)/i', $k), ARRAY_FILTER_USE_KEY);
$baseQuery = http_build_query($currentParams);

if (!function_exists('pzPagerUrl')) {
    function pzPagerUrl(string $uri, string $baseQuery): string
    {
        if (!$baseQuery) return $uri;
        return $uri . (str_contains($uri, '?') ? '&' : '?') . $baseQuery;
    }
}
?>

<?php $pager->setSurroundCount(2) ?>
<nav aria-label="Page navigation">
    <ul class="pager-list">

        <?php if ($pager->hasPrevious()) : ?>
            <li class="pager-item">
                <a class="pager-link" href="<?= pzPagerUrl($pager->getFirst(), $baseQuery) ?>" aria-label="First">&laquo;</a>
            </li>
            <li class="pager-item">
                <a class="pager-link" href="<?= pzPagerUrl($pager->getPrevious(), $baseQuery) ?>" aria-label="Previous">&lsaquo;</a>
            </li>
        <?php endif ?>

        <?php foreach ($pager->links() as $link) : ?>
            <li class="pager-item">
                <a class="<?= $link['active'] ? 'pager-active' : 'pager-link' ?>" href="<?= pzPagerUrl($link['uri'], $baseQuery) ?>">
                    <?= $link['title'] ?>
                </a>
            </li>
        <?php endforeach ?>

        <?php if ($pager->hasNext()) : ?>
            <li class="pager-item">
                <a class="pager-link" href="<?= pzPagerUrl($pager->getNext(), $baseQuery) ?>" aria-label="Next">&rsaquo;</a>
            </li>
            <li class="pager-item">
                <a class="pager-link" href="<?= pzPagerUrl($pager->getLast(), $baseQuery) ?>" aria-label="Last">&raquo;</a>
            </li>
        <?php endif ?>

    </ul>
</nav>
