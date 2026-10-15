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
        <a href="<?= e(current_url_with(['page' => $pagination['page'] - 1])) ?>" rel="prev">Previous</a>
    <?php endif; ?>

    <span>Page <?= $pagination['page'] ?> of <?= $pagination['pages'] ?></span>

    <?php if ($pagination['page'] < $pagination['pages']): ?>
        <a href="<?= e(current_url_with(['page' => $pagination['page'] + 1])) ?>" rel="next">Next</a>
    <?php endif; ?>
</nav>
