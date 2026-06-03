<?php
/**
 * A single conversation thread.
 *
 * @var int   $me
 * @var array $other     the other user
 * @var array $messages  ordered oldest-first
 * @var array $previews  shared-post previews keyed by post id
 */

use App\Core\Csrf;
?>
<div class="mx-auto flex h-[calc(100vh-9rem)] max-w-md flex-col">
    <!-- Header -->
    <header class="mb-3 flex items-center gap-3">
        <a href="<?= url('messages') ?>" class="btn-ghost h-8 w-8 !px-0" aria-label="Zurück">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </a>
        <a href="<?= url('profile/visit?id=' . (int) $other['id']) ?>" class="flex items-center gap-2">
            <img src="<?= url('avatar?id=' . (int) $other['id']) ?>" alt="" class="h-9 w-9 rounded-full object-cover">
            <span class="font-semibold"><?= e($other['username']) ?></span>
        </a>
    </header>

    <!-- Messages -->
    <div class="flex-1 space-y-2 overflow-y-auto rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
        <?php if (empty($messages)): ?>
            <p class="py-8 text-center text-sm text-gray-400">Noch keine Nachrichten. Sag Hallo!</p>
        <?php endif; ?>
        <?php foreach ($messages as $m): ?>
            <?php $mine = (int) $m['sender_id'] === $me; ?>
            <div class="flex <?= $mine ? 'justify-end' : 'justify-start' ?>">
                <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm <?= $mine ? 'bg-indigo-600 text-white' : 'bg-white text-gray-900 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700' ?>">
                    <?php
                    $pid = $m['shared_post_id'] !== null ? (int) $m['shared_post_id'] : null;
                    if ($pid !== null):
                        $preview = $previews[$pid] ?? null; ?>
                        <?php if ($preview): ?>
                            <a href="<?= url('post?id=' . $pid) ?>"
                               class="mb-1 flex items-center gap-2 rounded-lg bg-black/10 p-2 dark:bg-white/10">
                                <?php if (!empty($preview['cover_image_id'])): ?>
                                    <img src="<?= url('posts/image?id=' . (int) $preview['cover_image_id']) ?>" alt="" class="h-12 w-12 rounded object-cover">
                                <?php endif; ?>
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold"><?= e($preview['title']) ?></span>
                                    <span class="block text-xs opacity-80">Beitrag von <?= e($preview['username']) ?></span>
                                </span>
                            </a>
                        <?php else: ?>
                            <p class="mb-1 italic opacity-80">Beitrag nicht mehr verfügbar</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($m['body'])): ?>
                        <p class="whitespace-pre-wrap break-words"><?= e($m['body']) ?></p>
                    <?php endif; ?>
                    <span class="mt-0.5 block text-[10px] opacity-70"><?= e(format_datetime($m['created_at'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Composer -->
    <form action="<?= url('messages/send') ?>" method="POST" class="mt-3 flex items-center gap-2">
        <?= Csrf::field() ?>
        <input type="hidden" name="recipient" value="<?= (int) $other['id'] ?>">
        <label class="sr-only" for="dm-body">Nachricht</label>
        <input id="dm-body" type="text" name="body" maxlength="2000" required autocomplete="off"
               class="input" placeholder="Nachricht schreiben …">
        <button type="submit" class="btn-primary shrink-0">Senden</button>
    </form>
</div>
