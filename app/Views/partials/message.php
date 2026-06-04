<?php
/**
 * A single DM bubble (server-rendered and reused by the poll endpoint).
 * Supports: reply preview, one or more attachments, link/media/text/shared-post
 * content, edited indicator, soft-deleted placeholder. Reaction bars are filled
 * client-side (data-reactions container). data-* drive the filters.
 *
 * @var array      $m        message row (media[], reply, deleted, edited, …)
 * @var int        $me
 * @var array|null $preview  shared-post preview (kind = post)
 */
$mine    = (int) $m['sender_id'] === $me;
$kind    = $m['kind'];
$body    = (string) ($m['body'] ?? '');
$deleted = !empty($m['deleted']);
$media   = $m['media'] ?? [];
$reply   = $m['reply'] ?? null;

$mediaKinds = array_values(array_unique(array_map(static fn ($a) => $a['kind'], $media)));
if ($deleted) {
    $kinds = ['deleted'];
} elseif ($mediaKinds !== []) {
    $kinds = $mediaKinds;
} elseif (in_array($kind, ['image', 'gif', 'video', 'link', 'post'], true)) {
    $kinds = [$kind];
} else {
    $kinds = ['text'];
}
$hasLink = $kind === 'link' || (in_array('text', $kinds, true) && preg_match('#https?://#i', $body));
$searchText = mb_strtolower(trim($body . ' ' . implode(' ', array_map(static fn ($a) => $a['name'], $media))));
$bubble = $mine
    ? 'bg-indigo-600 text-white'
    : 'bg-white text-gray-900 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700';
?>
<div class="group flex flex-col <?= $mine ? 'items-end' : 'items-start' ?>"
     data-message data-id="<?= (int) $m['id'] ?>" data-mine="<?= $mine ? '1' : '0' ?>"
     data-kinds="<?= e(implode(',', $kinds)) ?>" data-ts="<?= (int) strtotime((string) $m['created_at']) ?>"
     data-has-link="<?= $hasLink ? '1' : '0' ?>" data-text="<?= e($searchText) ?>">

    <div class="msg-row <?= $mine ? 'is-mine' : '' ?>">
        <div class="msg-bubble space-y-1 rounded-2xl px-3 py-2 text-sm <?= $bubble ?>">
        <?php if ($deleted): ?>
            <p class="italic opacity-70">Nachricht gelöscht</p>
        <?php else: ?>
            <?php if ($reply): ?>
                <button type="button" data-jump="<?= (int) $reply['id'] ?>"
                        class="block w-full rounded-md border-l-2 border-current/50 bg-black/10 px-2 py-1 text-left text-xs opacity-80 dark:bg-white/10">
                    <span class="font-semibold"><?= e($reply['username']) ?></span><br><?= e($reply['snippet']) ?>
                </button>
            <?php endif; ?>

            <?php if ($kind === 'post' && $preview): ?>
                <a href="<?= url('post?id=' . (int) $m['shared_post_id']) ?>" class="flex items-center gap-2 rounded-lg bg-black/10 p-2 dark:bg-white/10">
                    <?php if (!empty($preview['cover_image_id'])): ?>
                        <img src="<?= url('posts/image?id=' . (int) $preview['cover_image_id']) ?>" alt="" class="h-12 w-12 rounded object-cover">
                    <?php endif; ?>
                    <span class="min-w-0">
                        <span class="block truncate font-semibold"><?= e($preview['title']) ?></span>
                        <span class="block text-xs opacity-80">Beitrag von <?= e($preview['username']) ?></span>
                    </span>
                </a>
            <?php elseif ($kind === 'post'): ?>
                <p class="italic opacity-80">Beitrag nicht mehr verfügbar</p>

            <?php elseif ($media !== []): ?>
                <!-- Multi-attachment grid -->
                <div class="grid gap-1 <?= count($media) > 1 ? 'grid-cols-2' : 'grid-cols-1' ?>">
                    <?php foreach ($media as $att):
                        $src = url('messages/media?id=' . (int) $att['id']); ?>
                        <?php if ($att['kind'] === 'image' || $att['kind'] === 'gif'): ?>
                            <a href="<?= $src ?>" target="_blank" rel="noopener" class="block">
                                <img src="<?= $src ?>" alt="<?= e($att['name']) ?>" loading="lazy" class="max-h-60 w-full rounded-lg object-cover">
                            </a>
                        <?php elseif ($att['kind'] === 'video'): ?>
                            <video controls preload="metadata" class="max-h-60 w-full rounded-lg" src="<?= $src ?>"></video>
                        <?php else: ?>
                            <a href="<?= $src ?>" download class="flex items-center gap-2 rounded-lg bg-black/10 p-2 dark:bg-white/10">
                                <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                <span class="min-w-0">
                                    <span class="block truncate text-xs font-medium"><?= e($att['name']) ?></span>
                                    <span class="block text-[10px] opacity-80"><?= e(format_bytes((int) $att['size'])) ?></span>
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($kind === 'image' || $kind === 'gif'): ?>
                <img src="<?= e($body) ?>" alt="<?= $kind === 'gif' ? 'GIF' : 'Bild' ?>" loading="lazy" class="max-h-72 w-auto max-w-full rounded-lg">
            <?php elseif ($kind === 'video'): ?>
                <video controls preload="metadata" class="max-h-72 w-full max-w-xs rounded-lg" src="<?= e($body) ?>"></video>
            <?php elseif ($kind === 'link'): ?>
                <a href="<?= e($body) ?>" data-external rel="noopener noreferrer nofollow" target="_blank" class="flex items-center gap-2 underline break-all">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                    <?= e($body) ?>
                </a>
            <?php endif; ?>

            <?php if ($body !== '' && $kind !== 'link' && !in_array($kind, ['image', 'gif', 'video'], true) || ($body !== '' && $media !== [])): ?>
                <p class="whitespace-pre-wrap break-words" data-body><?= linkify($body) ?></p>
            <?php endif; ?>

            <span class="block text-[10px] opacity-70">
                <?= e(format_datetime($m['created_at'])) ?><?php if (!empty($m['edited'])): ?> · bearbeitet<?php endif; ?>
            </span>
        <?php endif; ?>
        </div><!-- /.msg-bubble -->

        <?php if (!$deleted): ?>
            <!-- Stable three-dots trigger; the menu itself is built by messages.js -->
            <div class="msg-actions" data-msg-menu>
                <button type="button" class="msg-actions-btn" data-msg-menu-toggle
                        aria-haspopup="true" aria-expanded="false" aria-label="Nachrichtenoptionen">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/></svg>
                </button>
            </div>
        <?php endif; ?>
    </div><!-- /.msg-row -->

    <!-- Reaction badges (filled by messages.js) -->
    <div class="mt-0.5 flex flex-wrap gap-1" data-reactions></div>
</div>
