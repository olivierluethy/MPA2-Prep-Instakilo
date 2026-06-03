<?php
/**
 * Another user's profile.
 *
 * @var array $user
 * @var int   $followers
 * @var int   $following
 * @var bool  $isFollowing
 * @var bool  $isLoggedIn
 * @var array $posts
 */

use App\Core\Csrf;
use App\Core\View;
?>
<div class="space-y-8">
    <section class="card flex flex-col items-center gap-4 p-6 sm:flex-row sm:items-start">
        <img src="<?= url('avatar?id=' . (int) $user['id']) ?>" alt=""
             class="h-24 w-24 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-700">
        <div class="flex-1 text-center sm:text-left">
            <h1 class="text-2xl font-bold"><?= e($user['username']) ?></h1>
            <div class="mt-3 flex justify-center gap-6 sm:justify-start">
                <div class="text-center"><span class="block text-lg font-bold" data-follower-count><?= $followers ?></span><span class="text-xs text-gray-500 dark:text-gray-400">Followers</span></div>
                <div class="text-center"><span class="block text-lg font-bold"><?= $following ?></span><span class="text-xs text-gray-500 dark:text-gray-400">Follows</span></div>
                <div class="text-center"><span class="block text-lg font-bold"><?= count($posts) ?></span><span class="text-xs text-gray-500 dark:text-gray-400">Beiträge</span></div>
            </div>
            <?php if ($user['description'] !== null && $user['description'] !== ''): ?>
                <p class="mt-3 text-sm text-gray-700 dark:text-gray-300"><?= e($user['description']) ?></p>
            <?php endif; ?>

            <div class="mt-4 flex justify-center gap-2 sm:justify-start">
                <?php if (!$isLoggedIn): ?>
                    <a href="<?= url('login') ?>" class="btn-primary">Follow</a>
                <?php else: ?>
                    <form action="<?= url(($isFollowing ? 'unfollow' : 'follow') . '?id=' . (int) $user['id']) ?>"
                          method="POST" data-follow-form>
                        <?= Csrf::field() ?>
                        <button type="submit" data-follow-toggle data-following="<?= $isFollowing ? '1' : '0' ?>"
                                data-user-id="<?= (int) $user['id'] ?>"
                                class="<?= $isFollowing ? 'btn-secondary' : 'btn-primary' ?>">
                            <span data-follow-label><?= $isFollowing ? 'Unfollow' : 'Follow' ?></span>
                        </button>
                    </form>
                    <a href="<?= url('messages/thread?with=' . (int) $user['id']) ?>" class="btn-secondary">Nachricht</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section>
        <h2 class="mb-4 text-lg font-bold">Beiträge</h2>
        <?php if (empty($posts)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= $isFollowing || $isLoggedIn
                    ? 'Keine sichtbaren Beiträge.'
                    : 'Folge dieser Person, um private Beiträge zu sehen.' ?>
            </p>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <?php foreach ($posts as $post): ?>
                    <?= View::partial('partials.post-card', ['post' => $post, 'isLoggedIn' => $isLoggedIn, 'viewerId' => \App\Core\Auth::id()]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
