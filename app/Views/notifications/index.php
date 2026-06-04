<?php
/**
 * Notification center page. The list is server-rendered for first paint and
 * no-JS use; notifications.js then keeps it live (poll), handles mark-read,
 * follow-back / unfollow, lazy "load more" and relative-time refresh.
 *
 * @var array<int, array> $items
 * @var int               $unread
 * @var bool              $hasMore
 * @var string            $sig
 */

use App\Core\View;
?>
<div class="mx-auto max-w-md" data-notifications data-sig="<?= e($sig) ?>">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-xl font-bold">Benachrichtigungen</h1>
        <button type="button" data-notif-read-all
                class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400 <?= $unread > 0 ? '' : 'hidden' ?>">
            Alle als gelesen markieren
        </button>
    </div>

    <!-- Empty state -->
    <div class="card flex-col items-center gap-2 p-12 text-center <?= $items === [] ? 'flex' : 'hidden' ?>" data-notif-empty>
        <svg class="h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
        <h2 class="text-lg font-semibold">Noch keine Benachrichtigungen</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Likes, Kommentare und neue Follower erscheinen hier.</p>
    </div>

    <!-- List -->
    <ul class="card divide-y divide-gray-100 overflow-hidden dark:divide-gray-800 <?= $items === [] ? 'hidden' : '' ?>" data-notif-list>
        <?= View::partial('partials.notification-list', ['items' => $items]) ?>
    </ul>

    <div class="mt-3 text-center">
        <button type="button" data-notif-more data-page="1"
                class="btn-secondary text-sm <?= $hasMore ? '' : 'hidden' ?>">Mehr laden</button>
    </div>
</div>
