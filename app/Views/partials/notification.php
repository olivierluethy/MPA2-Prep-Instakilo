<?php

use App\Core\NotificationType;

/**
 * One notification row — grouped ("A, B and N others …") or single. Server-
 * rendered and reused verbatim by the real-time poll endpoint, so the markup is
 * identical whether it arrives on first paint or live.
 *
 * @var array{
 *   id:int, type:string, group:bool, key:int, last_at:string, unread:bool,
 *   total:int, actors:array<int, array{id:int, username:string}>,
 *   actor_id:int, secondary:?int, i_follow:?bool
 * } $n
 */
$type    = $n['type'];
$href    = NotificationType::url($n);
$unread  = !empty($n['unread']);
$ts      = strtotime((string) $n['last_at']) ?: time();
$isFollow = NotificationType::action($type) === 'follow';
?>
<li data-notif data-id="<?= (int) $n['id'] ?>" data-type="<?= e($type) ?>" data-unread="<?= $unread ? '1' : '0' ?>"
    class="flex items-start gap-3 px-3 py-3 transition-colors <?= $unread
        ? 'bg-indigo-50 dark:bg-indigo-950/40'
        : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' ?>">

    <!-- Avatar + type badge -->
    <a href="<?= e($href) ?>" data-notif-link class="relative shrink-0">
        <img src="<?= url('avatar?id=' . (int) $n['actor_id']) ?>" alt=""
             class="h-11 w-11 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700">
        <span class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white dark:ring-gray-900 <?= NotificationType::accent($type) ?>">
            <span class="h-3 w-3"><?= NotificationType::icon($type) ?></span>
        </span>
    </a>

    <!-- Message + timestamp -->
    <a href="<?= e($href) ?>" data-notif-link class="min-w-0 flex-1 <?= $unread ? '' : 'text-gray-600 dark:text-gray-400' ?>">
        <p class="text-sm leading-snug"><?= NotificationType::message($n) /* names pre-escaped */ ?></p>
        <p class="mt-0.5 text-xs text-gray-400" data-ts="<?= $ts ?>" title="<?= e(format_datetime($n['last_at'])) ?>"><?= e(time_ago($n['last_at'])) ?></p>
    </a>

    <!-- Right-side control: follow-back action, or unread dot -->
    <div class="flex shrink-0 items-center self-center">
        <?php if ($isFollow): ?>
            <?php $following = !empty($n['i_follow']); ?>
            <button type="button" data-follow-action
                    data-user-id="<?= (int) $n['actor_id'] ?>"
                    data-username="<?= e($n['actors'][0]['username'] ?? '') ?>"
                    data-following="<?= $following ? '1' : '0' ?>"
                    class="<?= $following ? 'btn-secondary' : 'btn-primary' ?> h-8 !px-3 text-xs">
                <span data-follow-label><?= $following ? 'Folge ich' : 'Zurückfolgen' ?></span>
            </button>
        <?php elseif ($unread): ?>
            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500" aria-label="ungelesen"></span>
        <?php endif; ?>
    </div>
</li>
