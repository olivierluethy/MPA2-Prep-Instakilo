<?php
/**
 * Generic error page.
 *
 * @var int    $code
 * @var string $heading
 * @var string $detail
 */
?>
<div class="card mx-auto max-w-md p-10 text-center">
    <p class="text-5xl font-black text-indigo-600 dark:text-indigo-400"><?= (int) $code ?></p>
    <h1 class="mt-2 text-xl font-bold"><?= e($heading) ?></h1>
    <?php if (!empty($detail)): ?>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= e($detail) ?></p>
    <?php endif; ?>
    <a href="<?= url('home') ?>" class="btn-primary mt-6">Zur Startseite</a>
</div>
