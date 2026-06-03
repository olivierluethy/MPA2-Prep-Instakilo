<?php
/**
 * Conversation list.
 *
 * @var array $conversations  [{user_id, username, last_body, last_at, unread}]
 */
?>
<div class="mx-auto max-w-md">
    <h1 class="mb-4 text-xl font-bold">Nachrichten</h1>

    <?php if (empty($conversations)): ?>
        <div class="card flex flex-col items-center gap-2 p-12 text-center">
            <h2 class="text-lg font-semibold">Keine Unterhaltungen</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Besuche ein Profil und schreibe eine Nachricht, oder teile einen Beitrag per DM.</p>
        </div>
    <?php else: ?>
        <ul class="card divide-y divide-gray-100 dark:divide-gray-800">
            <?php foreach ($conversations as $c): ?>
                <li>
                    <a href="<?= url('messages/thread?with=' . (int) $c['user_id']) ?>"
                       class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <img src="<?= url('avatar?id=' . (int) $c['user_id']) ?>" alt="" class="h-10 w-10 rounded-full object-cover">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold"><?= e($c['username']) ?></span>
                                <span class="shrink-0 text-xs text-gray-400"><?= e(format_datetime($c['last_at'])) ?></span>
                            </div>
                            <p class="truncate text-sm text-gray-500 dark:text-gray-400"><?= e($c['last_body']) ?></p>
                        </div>
                        <?php if ((int) $c['unread'] > 0): ?>
                            <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-xs font-semibold text-white"><?= (int) $c['unread'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
