<?php
/**
 * The current user's own profile + settings.
 *
 * @var array $user
 * @var int   $followers
 * @var int   $following
 * @var array $posts
 */

use App\Core\Csrf;
use App\Core\View;
?>
<div class="space-y-8">
    <!-- Header -->
    <section class="card flex flex-col items-center gap-4 p-6 sm:flex-row sm:items-start">
        <img src="<?= url('avatar?id=' . (int) $user['id']) ?>" alt=""
             class="h-24 w-24 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-700">
        <div class="flex-1 text-center sm:text-left">
            <h1 class="text-2xl font-bold"><?= e($user['username']) ?></h1>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($user['email']) ?></p>
            <div class="mt-3 flex justify-center gap-6 sm:justify-start">
                <div class="text-center"><span class="block text-lg font-bold"><?= $followers ?></span><span class="text-xs text-gray-500 dark:text-gray-400">Followers</span></div>
                <div class="text-center"><span class="block text-lg font-bold"><?= $following ?></span><span class="text-xs text-gray-500 dark:text-gray-400">Follows</span></div>
                <div class="text-center"><span class="block text-lg font-bold"><?= count($posts) ?></span><span class="text-xs text-gray-500 dark:text-gray-400">Beiträge</span></div>
            </div>
            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                <?= $user['description'] !== null && $user['description'] !== ''
                    ? e($user['description'])
                    : '<span class="italic text-gray-400">Keine Beschreibung vorhanden</span>' ?>
            </p>
        </div>
    </section>

    <!-- Settings -->
    <section class="card p-6">
        <h2 class="mb-4 text-lg font-bold">Profil bearbeiten</h2>
        <div class="grid gap-6 md:grid-cols-2">
            <form action="<?= url('profile/update') ?>" method="POST" class="space-y-2">
                <?= Csrf::field() ?>
                <label for="set-username" class="label">Benutzername</label>
                <input id="set-username" name="username" type="text" class="input" value="<?= e($user['username']) ?>" maxlength="255">
                <button class="btn-secondary mt-1">Speichern</button>
            </form>

            <form action="<?= url('profile/update') ?>" method="POST" enctype="multipart/form-data" class="space-y-2">
                <?= Csrf::field() ?>
                <label for="set-avatar" class="label">Profilbild</label>
                <input id="set-avatar" name="avatar" type="file" accept="image/*"
                       class="input file:mr-3 file:rounded file:border-0 file:bg-gray-200 file:px-3 file:py-1 dark:file:bg-gray-700">
                <button class="btn-secondary mt-1">Hochladen</button>
            </form>

            <form action="<?= url('profile/update') ?>" method="POST" class="space-y-2 md:col-span-2">
                <?= Csrf::field() ?>
                <label for="set-desc" class="label">Beschreibung</label>
                <textarea id="set-desc" name="description" rows="3" class="input" maxlength="500"
                          placeholder="Erzähl etwas über dich"><?= e($user['description'] ?? '') ?></textarea>
                <button class="btn-secondary mt-1">Speichern</button>
            </form>

            <form action="<?= url('profile/update') ?>" method="POST" class="space-y-2 md:col-span-2">
                <?= Csrf::field() ?>
                <h3 class="label">Passwort ändern</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <input name="password" type="password" class="input" placeholder="Neues Passwort" autocomplete="new-password" minlength="6">
                    <input name="verypass" type="password" class="input" placeholder="Passwort bestätigen" autocomplete="new-password" minlength="6">
                </div>
                <button class="btn-secondary mt-1">Passwort aktualisieren</button>
            </form>
        </div>
    </section>

    <!-- Posts -->
    <section>
        <h2 class="mb-4 text-lg font-bold">Deine Beiträge</h2>
        <?php if (empty($posts)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">Du hast noch keine Beiträge. Nutze „Hochladen“ oben rechts.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <?php foreach ($posts as $post): ?>
                    <?= View::partial('partials.post-card', ['post' => $post, 'isLoggedIn' => true]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
