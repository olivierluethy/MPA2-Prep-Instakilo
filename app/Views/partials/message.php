<?php
/**
 * A single DM bubble. Used both server-side (thread) and by the poll endpoint.
 * Outer wrapper carries data-* attributes consumed by the client-side filters.
 *
 * @var array      $m        message row (kind, body, media_*, shared_post_id, ...)
 * @var int        $me
 * @var array|null $preview  shared-post preview (for kind = post)
 */
$mine    = (int) $m['sender_id'] === $me;
$kind    = $m['kind'];
$body    = (string) ($m['body'] ?? '');
$mediaId = $m['media_id'] !== null ? (int) $m['media_id'] : null;
$hasLink = $kind === 'link' || ($kind === 'text' && preg_match('#https?://#i', $body));
$searchText = mb_strtolower(trim($body . ' ' . (string) ($m['media_name'] ?? '')));
$bubble = $mine
    ? 'bg-indigo-600 text-white'
    : 'bg-white text-gray-900 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700';
?>
<div class="flex <?= $mine ? 'justify-end' : 'justify-start' ?>"
     data-message data-id="<?= (int) $m['id'] ?>" data-kind="<?= e($kind) ?>"
     data-ts="<?= (int) strtotime((string) $m['created_at']) ?>"
     data-has-link="<?= $hasLink ? '1' : '0' ?>"
     data-text="<?= e($searchText) ?>">
    <div class="max-w-[80%] space-y-1 rounded-2xl px-3 py-2 text-sm <?= $bubble ?>">

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

        <?php elseif ($kind === 'image' || $kind === 'gif'): ?>
            <img src="<?= $mediaId ? url('messages/media?id=' . $mediaId) : e($body) ?>"
                 alt="<?= $kind === 'gif' ? 'GIF' : 'Bild' ?>" loading="lazy"
                 class="max-h-72 w-auto max-w-full rounded-lg">

        <?php elseif ($kind === 'video'): ?>
            <video controls preload="metadata" class="max-h-72 w-full max-w-xs rounded-lg"
                   src="<?= $mediaId ? url('messages/media?id=' . $mediaId) : e($body) ?>"></video>

        <?php elseif ($kind === 'file' && $mediaId): ?>
            <a href="<?= url('messages/media?id=' . $mediaId) ?>"
               class="flex items-center gap-2 rounded-lg bg-black/10 p-2 dark:bg-white/10" download>
                <svg class="h-7 w-7 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                <span class="min-w-0">
                    <span class="block truncate font-medium"><?= e($m['media_name'] ?? 'Datei') ?></span>
                    <span class="block text-xs opacity-80"><?= e(format_bytes((int) ($m['media_size'] ?? 0))) ?> · herunterladen</span>
                </span>
            </a>

        <?php elseif ($kind === 'link'): ?>
            <a href="<?= e($body) ?>" data-external rel="noopener noreferrer nofollow" target="_blank"
               class="flex items-center gap-2 underline break-all">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                <?= e($body) ?>
            </a>
        <?php endif; ?>

        <?php
        // Caption text for media messages (body shown beneath), or plain text.
        if ($body !== '' && !in_array($kind, ['link', 'image', 'gif', 'video'], true)):
            ?>
            <p class="whitespace-pre-wrap break-words"><?= linkify($body) ?></p>
        <?php elseif ($body !== '' && in_array($kind, ['image', 'gif', 'video'], true) && $mediaId): ?>
            <p class="whitespace-pre-wrap break-words"><?= linkify($body) ?></p>
        <?php endif; ?>

        <span class="block text-[10px] opacity-70"><?= e(format_datetime($m['created_at'])) ?></span>
    </div>
</div>
