<?php
/**
 * Conversation thread with real-time polling and client-side filters
 * (content-type, fuzzy text, time range). Messages render via partials.message.
 *
 * @var int        $me
 * @var array      $other
 * @var array      $messages  oldest-first
 * @var array      $previews  shared-post previews keyed by post id
 * @var int        $lastId    id of the newest message (poll cursor)
 */

use App\Core\Csrf;
use App\Core\View;

$types = ['image' => 'Bilder', 'gif' => 'GIFs', 'video' => 'Videos', 'file' => 'Dateien', 'link' => 'Links', 'text' => 'Text', 'post' => 'Beiträge'];
?>
<div class="mx-auto flex h-[calc(100vh-8rem)] max-w-md flex-col"
     data-dm-thread data-with="<?= (int) $other['id'] ?>" data-me="<?= $me ?>" data-lastid="<?= (int) $lastId ?>">

    <!-- Header -->
    <header class="mb-2 flex items-center gap-3">
        <a href="<?= url('messages') ?>" class="btn-ghost h-8 w-8 !px-0" aria-label="Zurück">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </a>
        <a href="<?= url('profile/visit?id=' . (int) $other['id']) ?>" class="flex items-center gap-2">
            <img src="<?= url('avatar?id=' . (int) $other['id']) ?>" alt="" class="h-9 w-9 rounded-full object-cover">
            <span class="font-semibold"><?= e($other['username']) ?></span>
        </a>
        <button type="button" data-filter-toggle class="btn-ghost ml-auto h-8 w-8 !px-0" aria-label="Filter">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
        </button>
    </header>

    <!-- Filters (collapsed by default) -->
    <div data-filter-panel class="mb-2 hidden space-y-2 rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
        <div class="flex flex-wrap gap-1" data-type-filters>
            <button type="button" data-type="all" class="rounded-full bg-indigo-600 px-2.5 py-1 text-xs font-medium text-white">Alle</button>
            <?php foreach ($types as $key => $label): ?>
                <button type="button" data-type="<?= $key ?>" class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= $label ?></button>
            <?php endforeach; ?>
        </div>

        <input type="search" data-fuzzy class="input !py-1.5 text-sm" placeholder="Fuzzy-Suche (toleriert Tippfehler) …" autocomplete="off">

        <div data-timeline class="hidden">
            <div class="mb-1 flex items-end gap-px" data-histogram style="height:2.5rem"></div>
            <input type="range" data-range-start class="w-full" min="0" max="100" value="0">
            <input type="range" data-range-end class="w-full" min="0" max="100" value="100">
            <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                <span data-range-start-label>–</span> bis <span data-range-end-label>–</span>
            </p>
        </div>
        <p class="hidden text-center text-xs text-gray-400" data-filter-empty>Keine Nachrichten entsprechen den Filtern.</p>
    </div>

    <!-- Messages -->
    <div class="flex-1 space-y-2 overflow-y-auto rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50" data-message-list>
        <?php if (empty($messages)): ?>
            <p class="py-8 text-center text-sm text-gray-400" data-thread-empty>Noch keine Nachrichten. Sag Hallo!</p>
        <?php endif; ?>
        <?php foreach ($messages as $m): ?>
            <?= View::partial('partials.message', ['m' => $m, 'me' => $me, 'preview' => $m['shared_post_id'] ? ($previews[(int) $m['shared_post_id']] ?? null) : null]) ?>
        <?php endforeach; ?>
    </div>

    <!-- Typing indicator -->
    <p class="mt-1 hidden h-4 text-xs text-gray-400" data-typing><?= e($other['username']) ?> tippt …</p>

    <!-- Composer -->
    <form class="mt-2 flex items-center gap-2" data-dm-composer method="POST" action="<?= url('messages/send') ?>" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <input type="hidden" name="recipient" value="<?= (int) $other['id'] ?>">
        <button type="button" data-attach class="btn-ghost h-9 w-9 !px-0" aria-label="Datei anhängen">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
        </button>
        <input type="file" name="media" class="hidden" data-media-input
               accept="image/*,video/*,.pdf,.zip,.txt,.doc,.docx,.xls,.xlsx,.gif">
        <label class="sr-only" for="dm-body">Nachricht</label>
        <input id="dm-body" type="text" name="body" maxlength="2000" autocomplete="off" class="input" placeholder="Nachricht, Bild-URL oder Link …">
        <button type="submit" class="btn-primary shrink-0">Senden</button>
    </form>
    <p class="mt-1 hidden text-xs text-gray-500 dark:text-gray-400" data-attach-name></p>
    <p class="mt-1 hidden text-xs text-rose-600 dark:text-rose-400" data-dm-error></p>
</div>
