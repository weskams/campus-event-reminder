<?php
/**
 * Previous / next links. Expects $pagination from paginate().
 */

defined('APP_ROOT') || exit;

if ($pagination['pages'] <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($pagination['page'] > 1): ?>
        <a class="button button-secondary button-sm" href="<?= e(current_url_with(['page' => $pagination['page'] - 1])) ?>" rel="prev">
            <?= icon('arrow-left') ?> Previous
        </a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>

    <span class="pagination-status">Page <?= $pagination['page'] ?> of <?= $pagination['pages'] ?></span>

    <?php if ($pagination['page'] < $pagination['pages']): ?>
        <a class="button button-secondary button-sm" href="<?= e(current_url_with(['page' => $pagination['page'] + 1])) ?>" rel="next">
            Next <?= icon('arrow-right') ?>
        </a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>
</nav>
