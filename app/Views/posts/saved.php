<?php
/**
 * The current user's saved-post collection.
 *
 * @var array $posts
 * @var int   $viewerId
 */

use App\Core\View;
?>
<div class="mx-auto max-w-md">
    <h1 class="mb-4 flex items-center gap-2 text-xl font-bold">
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z"/></svg>
        Gespeicherte Beiträge
    </h1>

    <?php if (empty($posts)): ?>
        <div class="card flex flex-col items-center gap-2 p-12 text-center">
            <h2 class="text-lg font-semibold">Noch nichts gespeichert</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tippe auf das Lesezeichen-Symbol eines Beitrags, um ihn hier zu sammeln.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-5">
            <?php foreach ($posts as $post): ?>
                <?= View::partial('partials.post-card', ['post' => $post, 'isLoggedIn' => true, 'viewerId' => $viewerId]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
