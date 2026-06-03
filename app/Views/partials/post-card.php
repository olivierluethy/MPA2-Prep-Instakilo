<?php

use App\Core\Csrf;
use App\Core\View;

/**
 * A single feed post (compact layout).
 *
 * @var array     $post        post row with images[], like_count, liked_by_me,
 *                             comments[], comment_count, is_saved, is_reposted,
 *                             reposted_by, is_public
 * @var bool      $isLoggedIn
 * @var int|null  $viewerId    current user id (for ownership checks)
 */
$viewerId = $viewerId ?? null;
$images   = $post['images'] ?? [];
$liked    = !empty($post['liked_by_me']);
$saved    = !empty($post['is_saved']);
$reposted = !empty($post['is_reposted']);
$multi    = count($images) > 1;
$isOwner  = $isLoggedIn && $viewerId !== null && (int) $post['user_id'] === $viewerId;
$canRepost = $isLoggedIn && !$isOwner && !empty($post['is_public']);
?>
<article class="card flex flex-col overflow-hidden text-sm" data-post-id="<?= (int) $post['id'] ?>">
    <?php if (!empty($post['reposted_by'])): ?>
        <div class="flex items-center gap-1.5 px-3 pt-2 text-xs text-gray-500 dark:text-gray-400">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/></svg>
            Repost von <span class="font-medium"><?= e($post['reposted_by']) ?></span>
        </div>
    <?php endif; ?>

    <!-- Author -->
    <header class="flex items-center gap-2.5 p-2.5">
        <a href="<?= url('profile/visit?id=' . (int) $post['user_id']) ?>" class="shrink-0">
            <img src="<?= url('avatar?id=' . (int) $post['user_id']) ?>" alt=""
                 class="h-8 w-8 rounded-full object-cover ring-1 ring-gray-300 dark:ring-gray-700">
        </a>
        <a href="<?= url('profile/visit?id=' . (int) $post['user_id']) ?>"
           class="font-semibold hover:underline" title="Profil von <?= e($post['username']) ?> ansehen">
            <?= e($post['username']) ?>
        </a>
        <?php if (!empty($post['location'])): ?>
            <span class="ml-auto truncate text-xs text-gray-500 dark:text-gray-400"><?= e($post['location']) ?></span>
        <?php endif; ?>

        <?php if ($isOwner): ?>
            <div class="relative <?= empty($post['location']) ? 'ml-auto' : '' ?>" data-dropdown>
                <button type="button" data-dropdown-toggle class="btn-ghost h-7 w-7 !px-0" aria-label="Beitragsoptionen">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 6.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm0 7a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm0 7a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
                </button>
                <div data-dropdown-menu class="absolute right-0 z-10 mt-1 hidden w-36 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                    <button type="button" data-edit-post data-post-id="<?= (int) $post['id'] ?>"
                            class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-800">Bearbeiten</button>
                    <form action="<?= url('posts/delete?id=' . (int) $post['id']) ?>" method="POST" data-delete-post>
                        <?= Csrf::field() ?>
                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-rose-600 hover:bg-gray-100 dark:hover:bg-gray-800">Löschen</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <!-- Images: sliding track, one visible at a time -->
    <?php if ($images): ?>
        <div class="relative aspect-square w-full overflow-hidden bg-gray-100 dark:bg-gray-800" data-carousel>
            <div class="flex h-full w-full transition-transform duration-300 ease-out" data-carousel-track>
                <?php foreach ($images as $imgId): ?>
                    <img src="<?= url('posts/image?id=' . (int) $imgId) ?>" alt="<?= e($post['title']) ?>" loading="lazy"
                         class="h-full w-full shrink-0 grow-0 basis-full object-cover" data-carousel-slide>
                <?php endforeach; ?>
            </div>
            <?php if ($multi): ?>
                <button type="button" data-carousel-prev aria-label="Vorheriges Bild"
                        class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-1.5 text-white hover:bg-black/60">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <button type="button" data-carousel-next aria-label="Nächstes Bild"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-1.5 text-white hover:bg-black/60">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
                <div class="absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1.5">
                    <?php foreach ($images as $i => $imgId): ?>
                        <span class="h-1.5 w-1.5 rounded-full bg-white <?= $i === 0 ? 'opacity-100' : 'opacity-50' ?>" data-carousel-dot></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Actions + body -->
    <div class="flex flex-col gap-1.5 p-2.5">
        <div class="flex items-center gap-3">
            <!-- Like -->
            <?php if ($isLoggedIn): ?>
                <form action="<?= url('posts/' . ($liked ? 'unlike' : 'like') . '?id=' . (int) $post['id']) ?>" method="POST" data-like-form>
                    <?= Csrf::field() ?>
                    <button type="submit" data-like-toggle data-liked="<?= $liked ? '1' : '0' ?>" data-post-id="<?= (int) $post['id'] ?>"
                            class="flex items-center text-rose-500 transition hover:scale-110" aria-pressed="<?= $liked ? 'true' : 'false' ?>" aria-label="Gefällt mir">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="<?= $liked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" data-like-icon><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </button>
                </form>
            <?php else: ?>
                <a href="<?= url('login') ?>" class="flex items-center text-rose-500 hover:scale-110" aria-label="Zum Liken einloggen">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                </a>
            <?php endif; ?>
            <span class="font-semibold" data-like-count><?= (int) $post['like_count'] ?></span>

            <?php if ($isLoggedIn): ?>
                <!-- Comment (reveals the input, Instagram-style) -->
                <button type="button" data-comment-open class="ml-1 flex items-center text-gray-500 hover:text-indigo-500 dark:text-gray-400" aria-label="Kommentieren">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                </button>

                <!-- Share via DM -->
                <button type="button" data-share-post data-post-id="<?= (int) $post['id'] ?>" data-post-title="<?= e($post['title']) ?>"
                        class="ml-1 flex items-center text-gray-500 hover:text-indigo-500 dark:text-gray-400" aria-label="Per Nachricht teilen">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                </button>

                <?php if ($canRepost): ?>
                    <!-- Repost -->
                    <form action="<?= url('posts/' . ($reposted ? 'unrepost' : 'repost') . '?id=' . (int) $post['id']) ?>" method="POST" data-repost-form>
                        <?= Csrf::field() ?>
                        <button type="submit" data-repost-toggle data-reposted="<?= $reposted ? '1' : '0' ?>"
                                class="flex items-center <?= $reposted ? 'text-emerald-500' : 'text-gray-500 hover:text-emerald-500 dark:text-gray-400' ?>" aria-label="Reposten">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/></svg>
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Save -->
                <form action="<?= url('posts/' . ($saved ? 'unsave' : 'save') . '?id=' . (int) $post['id']) ?>" method="POST" data-save-form class="ml-auto">
                    <?= Csrf::field() ?>
                    <button type="submit" data-save-toggle data-saved="<?= $saved ? '1' : '0' ?>"
                            class="flex items-center text-gray-700 hover:text-indigo-500 dark:text-gray-200" aria-label="Speichern">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="<?= $saved ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" data-save-icon><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/></svg>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <h2 class="font-semibold"><?= e($post['title']) ?></h2>

        <?php if (!empty($post['description'])): ?>
            <div class="prose-content text-xs"><?= $post['description'] ?></div>
        <?php endif; ?>

        <?php if (!empty($post['created_at'])): ?>
            <time class="text-xs text-gray-400" datetime="<?= e(iso_datetime($post['created_at'])) ?>">
                <?= e(format_datetime($post['created_at'])) ?>
                <?php if (!empty($post['taken_on'])): ?> · aufgenommen am <?= e(format_date($post['taken_on'])) ?><?php endif; ?>
            </time>
        <?php endif; ?>

        <?= View::partial('partials.comments', ['post' => $post, 'isLoggedIn' => $isLoggedIn, 'viewerId' => $viewerId]) ?>
    </div>
</article>
