<?php
/** @var array<int, array{type:string, message:string}> $messages */
if (empty($messages)) {
    return;
}

$styles = [
    'success' => 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    'error'   => 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-200',
    'info'    => 'border-sky-300 bg-sky-50 text-sky-800 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-200',
];
?>
<div class="mx-auto mt-4 w-full max-w-5xl space-y-2 px-4" role="status" aria-live="polite">
    <?php foreach ($messages as $m): ?>
        <div class="flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm <?= $styles[$m['type']] ?? $styles['info'] ?>"
             data-flash>
            <span><?= e($m['message']) ?></span>
            <button type="button" class="opacity-60 hover:opacity-100" data-flash-dismiss aria-label="Schließen">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
