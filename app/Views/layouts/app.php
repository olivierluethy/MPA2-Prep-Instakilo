<?php
/**
 * Main layout shell. Wraps every page, owns the dark-mode bootstrap, the global
 * nav/footer, flash messages and (for authenticated users) the upload modal.
 *
 * @var string $title
 * @var string $content   pre-rendered inner view
 */

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;

$needsEditor = Auth::check();
?>
<!DOCTYPE html>
<html lang="de" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <meta name="base-path" content="<?= e((string) config('app.base_path', '')) ?>">
    <meta name="user-id" content="<?= Auth::check() ? (int) Auth::id() : '' ?>">
    <title><?= e($title) ?></title>
    <link rel="icon" href="<?= asset('assets/favicon.ico') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?php if ($needsEditor): ?>
        <link rel="stylesheet" href="<?= asset('vendor/quill/quill.snow.css') ?>">
    <?php endif; ?>
    <script>
        // Apply the saved/system theme before first paint to avoid a flash.
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
</head>

<body class="min-h-full bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <?= View::partial('partials.nav') ?>

    <?= View::partial('partials.flash', ['messages' => Flash::pull()]) ?>

    <main class="mx-auto w-full max-w-5xl px-4 py-6">
        <?= $content ?>
    </main>

    <?= View::partial('partials.footer') ?>

    <?php if ($needsEditor): ?>
        <?= View::partial('partials.upload-modal') ?>
        <?= View::partial('partials.edit-modal') ?>
        <?= View::partial('partials.share-modal') ?>
    <?php endif; ?>

    <?= View::partial('partials.link-confirm-modal') ?>

    <script src="<?= asset('js/theme.js') ?>" defer></script>
    <script src="<?= asset('js/app.js') ?>" defer></script>
    <?php if ($needsEditor): ?>
        <script src="<?= asset('vendor/quill/quill.js') ?>" defer></script>
        <script src="<?= asset('js/upload.js') ?>" defer></script>
        <script src="<?= asset('js/edit.js') ?>" defer></script>
        <script src="<?= asset('js/messages.js') ?>" defer></script>
    <?php endif; ?>
</body>

</html>
