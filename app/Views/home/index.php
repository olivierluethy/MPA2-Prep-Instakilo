<?php
/**
 * Home feed.
 *
 * @var array    $posts
 * @var bool     $isLoggedIn
 * @var int|null $viewerId
 * @var string   $scope  'all' | 'following'
 */

use App\Core\View;
?>
<?php if (!$isLoggedIn): ?>
    <div class="mx-auto mb-6 max-w-md rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white">
        <h1 class="text-2xl font-bold">Willkommen bei Instakilo</h1>
        <p class="mt-1 text-indigo-100">Du siehst öffentliche Beiträge. <a href="<?= url('login') ?>" class="font-semibold underline">Melde dich an</a>, um zu folgen, zu liken und eigene Beiträge zu teilen.</p>
    </div>
<?php else: ?>
    <!-- Feed scope toggle -->
    <div class="mx-auto mb-4 flex max-w-md gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
        <a href="<?= url('home') ?>"
           class="flex-1 rounded-md py-1.5 text-center text-sm font-medium transition <?= $scope === 'all' ? 'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' ?>">
            Alle Beiträge
        </a>
        <a href="<?= url('home?feed=following') ?>"
           class="flex-1 rounded-md py-1.5 text-center text-sm font-medium transition <?= $scope === 'following' ? 'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' ?>">
            Folge ich
        </a>
    </div>
<?php endif; ?>

<?php if (empty($posts)): ?>
    <div class="card mx-auto flex max-w-md flex-col items-center gap-2 p-12 text-center">
        <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 9h.008v.008H18V9Zm-15 4.5V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v9a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 16.5v-3Z"/></svg>
        <h2 class="text-lg font-semibold">Derzeit noch keine Beiträge</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <?= $isLoggedIn && $scope === 'following' ? 'Folge anderen, um ihre Beiträge hier zu sehen.' : 'Sobald es Beiträge gibt, erscheinen sie hier.' ?>
        </p>
    </div>
<?php else: ?>
    <div class="mx-auto grid max-w-md grid-cols-1 gap-5">
        <?php foreach ($posts as $post): ?>
            <?= View::partial('partials.post-card', ['post' => $post, 'isLoggedIn' => $isLoggedIn, 'viewerId' => $viewerId]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
