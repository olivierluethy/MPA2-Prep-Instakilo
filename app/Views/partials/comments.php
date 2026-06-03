<?php

use App\Core\Csrf;

/**
 * Comments block: preview of the latest comments, toggle to load the full
 * paginated list, an add form, and author-only edit/delete controls.
 *
 * @var array    $post        with comments[] (preview) and comment_count
 * @var bool     $isLoggedIn
 * @var int|null $viewerId
 */
$viewerId = $viewerId ?? null;
$comments = $post['comments'] ?? [];
$count    = (int) ($post['comment_count'] ?? 0);
$postId   = (int) $post['id'];

/** Render one comment <li> (also mirrored client-side in app.js). */
$renderComment = static function (array $c) use ($viewerId): void {
    $own = $viewerId !== null && (int) $c['user_id'] === $viewerId;
    ?>
    <li class="group flex flex-wrap items-baseline gap-x-2" data-comment-id="<?= (int) $c['id'] ?>" data-comment-user="<?= (int) $c['user_id'] ?>">
        <a href="<?= url('profile/visit?id=' . (int) $c['user_id']) ?>" class="font-semibold hover:underline"><?= e($c['username']) ?></a>
        <span class="text-gray-700 dark:text-gray-300" data-comment-body><?= e($c['body']) ?></span>
        <?php if ($own): ?>
            <span class="ml-1 hidden gap-2 group-hover:inline-flex" data-comment-actions>
                <button type="button" class="text-xs text-gray-400 hover:text-indigo-500" data-comment-edit>Bearbeiten</button>
                <button type="button" class="text-xs text-gray-400 hover:text-rose-500" data-comment-delete>Löschen</button>
            </span>
        <?php endif; ?>
    </li>
    <?php
};
?>
<section class="mt-1 border-t border-gray-100 pt-2 dark:border-gray-800" data-comments data-post-id="<?= $postId ?>">
    <?php if ($count > 0): ?>
        <button type="button" data-comments-toggle class="text-xs font-medium text-gray-500 hover:underline dark:text-gray-400">
            Alle <span data-comments-count><?= $count ?></span> Kommentare ansehen
        </button>
    <?php else: ?>
        <p class="text-xs text-gray-400" data-comments-empty>Noch keine Kommentare</p>
    <?php endif; ?>

    <ul class="mt-2 space-y-1.5 text-sm" data-comments-list>
        <?php foreach ($comments as $c) {
            $renderComment($c);
        } ?>
    </ul>

    <?php if ($isLoggedIn): ?>
        <!-- Hidden until the comment icon is clicked (Instagram-style). -->
        <form class="mt-2 hidden items-center gap-2" method="POST" action="<?= url('posts/comment?id=' . $postId) ?>"
              data-comment-form data-comment-box>
            <?= Csrf::field() ?>
            <label class="sr-only" for="comment-<?= $postId ?>">Kommentar</label>
            <input id="comment-<?= $postId ?>" type="text" name="body" maxlength="1000" required
                   class="input !py-1.5 text-sm" placeholder="Kommentar hinzufügen …" data-comment-input>
            <button type="submit" class="btn-primary shrink-0 !py-1.5">Senden</button>
        </form>
    <?php endif; ?>
</section>
