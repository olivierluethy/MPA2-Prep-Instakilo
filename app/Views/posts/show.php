<?php
/**
 * Single post page (DM share / repost / search target).
 *
 * @var array    $post
 * @var bool     $isLoggedIn
 * @var int|null $viewerId
 */

use App\Core\View;
?>
<div class="mx-auto max-w-md">
    <a href="<?= url('home') ?>" class="link mb-3 inline-flex items-center gap-1 text-sm">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        Zurück zum Feed
    </a>
    <?= View::partial('partials.post-card', ['post' => $post, 'isLoggedIn' => $isLoggedIn, 'viewerId' => $viewerId]) ?>
</div>
