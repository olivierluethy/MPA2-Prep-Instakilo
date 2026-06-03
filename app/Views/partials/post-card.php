<?php

use App\Core\Csrf;

/**
 * A single feed post.
 *
 * @var array $post        post row with images[], like_count, liked_by_me
 * @var bool  $isLoggedIn
 */
$images = $post['images'] ?? [];
$liked  = !empty($post['liked_by_me']);
$multi  = count($images) > 1;
?>
<article class="card overflow-hidden flex flex-col">
    <!-- Author -->
    <header class="flex items-center gap-3 p-3">
        <a href="<?= url('profile/visit?id=' . (int) $post['user_id']) ?>" class="shrink-0">
            <img src="<?= url('avatar?id=' . (int) $post['user_id']) ?>" alt=""
                 class="h-9 w-9 rounded-full object-cover ring-1 ring-gray-300 dark:ring-gray-700">
        </a>
        <a href="<?= url('profile/visit?id=' . (int) $post['user_id']) ?>"
           class="font-semibold hover:underline" title="Profil von <?= e($post['username']) ?> ansehen">
            <?= e($post['username']) ?>
        </a>
        <?php if (!empty($post['location'])): ?>
            <span class="ml-auto truncate text-xs text-gray-500 dark:text-gray-400"><?= e($post['location']) ?></span>
        <?php endif; ?>
    </header>

    <!-- Images -->
    <?php if ($images): ?>
        <div class="relative aspect-square w-full bg-gray-100 dark:bg-gray-800" data-carousel>
            <?php foreach ($images as $i => $imgId): ?>
                <img src="<?= url('posts/image?id=' . (int) $imgId) ?>"
                     alt="<?= e($post['title']) ?>"
                     loading="lazy"
                     class="absolute inset-0 h-full w-full object-cover transition-opacity duration-300 <?= $i === 0 ? 'opacity-100' : 'opacity-0' ?>"
                     data-carousel-slide>
            <?php endforeach; ?>

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
    <div class="flex flex-col gap-2 p-3">
        <div class="flex items-center gap-2">
            <?php if ($isLoggedIn): ?>
                <form action="<?= url('posts/' . ($liked ? 'unlike' : 'like') . '?id=' . (int) $post['id']) ?>"
                      method="POST" data-like-form>
                    <?= Csrf::field() ?>
                    <button type="submit" data-like-toggle data-liked="<?= $liked ? '1' : '0' ?>"
                            data-post-id="<?= (int) $post['id'] ?>"
                            class="flex items-center text-rose-500 transition hover:scale-110"
                            aria-pressed="<?= $liked ? 'true' : 'false' ?>" aria-label="Gefällt mir">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="<?= $liked ? 'currentColor' : 'none' ?>"
                             stroke="currentColor" stroke-width="1.8" data-like-icon><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </button>
                </form>
            <?php else: ?>
                <a href="<?= url('login') ?>" class="flex items-center text-rose-500 hover:scale-110" aria-label="Zum Liken einloggen">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                </a>
            <?php endif; ?>
            <span class="text-sm font-semibold" data-like-count><?= (int) $post['like_count'] ?></span>
            <span class="text-sm text-gray-500 dark:text-gray-400">Likes</span>
        </div>

        <h2 class="font-semibold"><?= e($post['title']) ?></h2>

        <?php if (!empty($post['description'])): ?>
            <!-- Description is sanitized server-side (HtmlSanitizer) before storage. -->
            <div class="prose-content"><?= $post['description'] ?></div>
        <?php endif; ?>

        <?php if (!empty($post['taken_on'])): ?>
            <time class="text-xs text-gray-400" datetime="<?= e($post['taken_on']) ?>"><?= e($post['taken_on']) ?></time>
        <?php endif; ?>
    </div>
</article>
