<?php

use App\Core\Csrf;

/**
 * Comments block for a post: a preview of the latest comments, a toggle to load
 * the full paginated list (AJAX), and — for logged-in users — an add form.
 *
 * @var array $post        with comments[] (preview) and comment_count
 * @var bool  $isLoggedIn
 */
$comments = $post['comments'] ?? [];
$count    = (int) ($post['comment_count'] ?? 0);
$postId   = (int) $post['id'];
?>
<section class="mt-1 border-t border-gray-100 pt-2 dark:border-gray-800"
         data-comments data-post-id="<?= $postId ?>">

    <?php if ($count > 0): ?>
        <button type="button" data-comments-toggle
                class="text-xs font-medium text-gray-500 hover:underline dark:text-gray-400">
            Alle <span data-comments-count><?= $count ?></span> Kommentare ansehen
        </button>
    <?php else: ?>
        <p class="text-xs text-gray-400" data-comments-empty>Noch keine Kommentare</p>
    <?php endif; ?>

    <ul class="mt-2 space-y-1.5 text-sm" data-comments-list>
        <?php foreach ($comments as $c): ?>
            <li class="flex flex-wrap gap-x-2" data-comment-id="<?= (int) $c['id'] ?>">
                <a href="<?= url('profile/visit?id=' . (int) $c['user_id']) ?>"
                   class="font-semibold hover:underline"><?= e($c['username']) ?></a>
                <span class="text-gray-700 dark:text-gray-300"><?= e($c['body']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($isLoggedIn): ?>
        <form class="mt-2 flex items-center gap-2" method="POST"
              action="<?= url('posts/comment?id=' . $postId) ?>" data-comment-form>
            <?= Csrf::field() ?>
            <label class="sr-only" for="comment-<?= $postId ?>">Kommentar</label>
            <input id="comment-<?= $postId ?>" type="text" name="body" maxlength="1000" required
                   class="input !py-1.5 text-sm" placeholder="Kommentar hinzufügen …" data-comment-input>
            <button type="submit" class="btn-primary shrink-0 !py-1.5">Senden</button>
        </form>
    <?php endif; ?>
</section>
