<?php
/** Login / registration page. */

use App\Core\Csrf;

// Reusable leading field icon (kept inline so the form stays self-contained).
$fieldIcon = static function (string $d): string {
    return '<svg class="field-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
};
$icons = [
    'user'   => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
    'mail'   => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
    'at'     => 'M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 1 0-2.636 6.364M16.5 12V8.25',
    'lock'   => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
    'shield' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.285Z',
];
?>
<div class="mx-auto grid max-w-4xl items-center gap-8 py-8 md:grid-cols-2">
    <!-- Community / value proposition -->
    <div class="hidden md:block">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 p-8 text-white shadow-xl">
            <h1 class="text-3xl font-bold leading-tight">Eine neue Art, verbunden zu sein.</h1>
            <p class="mt-2 text-lg text-indigo-100">Teile Momente, entdecke Menschen, bleib in Kontakt.</p>

            <img src="<?= asset('assets/imageForLoginPage.png') ?>"
                 alt="Glückliche Menschen in der Instakilo-Community"
                 class="mt-6 h-56 w-full rounded-xl object-cover shadow-lg ring-1 ring-white/20">

            <ul class="mt-6 space-y-3 text-sm text-indigo-50">
                <li class="flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z"/></svg>
                    </span>
                    Teile deine schönsten Fotos und Beiträge
                </li>
                <li class="flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                    </span>
                    Schreib in Echtzeit mit Freunden
                </li>
                <li class="flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </span>
                    Werde Teil einer wachsenden Community
                </li>
            </ul>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="text-center text-xl font-bold">Willkommen bei Instakilo</h2>

        <div class="mt-4 grid grid-cols-2 rounded-lg bg-gray-100 p-1 dark:bg-gray-800" data-auth-tabs>
            <button type="button" class="rounded-md py-1.5 text-sm font-medium" data-auth-tab="login">Login</button>
            <button type="button" class="rounded-md py-1.5 text-sm font-medium" data-auth-tab="register">Registrieren</button>
        </div>

        <!-- Login -->
        <form action="<?= url('login') ?>" method="POST" class="mt-5 space-y-3" data-auth-panel="login" novalidate>
            <?= Csrf::field() ?>
            <div>
                <label for="login" class="label">E-Mail oder Benutzername</label>
                <div class="field">
                    <?= $fieldIcon($icons['user']) ?>
                    <input id="login" name="login" type="text" class="input" value="<?= old('login') ?>"
                           autocomplete="username" required>
                </div>
            </div>
            <div>
                <label for="login-password" class="label">Passwort</label>
                <div class="field">
                    <?= $fieldIcon($icons['lock']) ?>
                    <input id="login-password" name="password" type="password" class="input"
                           autocomplete="current-password" required>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full">Login</button>
        </form>

        <!-- Register -->
        <form action="<?= url('register') ?>" method="POST" class="mt-5 hidden space-y-3" data-auth-panel="register" novalidate>
            <?= Csrf::field() ?>
            <div>
                <label for="reg-email" class="label">E-Mail</label>
                <div class="field">
                    <?= $fieldIcon($icons['mail']) ?>
                    <input id="reg-email" name="email" type="email" class="input" value="<?= old('email') ?>"
                           autocomplete="email" required>
                </div>
            </div>
            <div>
                <label for="reg-username" class="label">Benutzername</label>
                <div class="field">
                    <?= $fieldIcon($icons['at']) ?>
                    <input id="reg-username" name="username" type="text" class="input" value="<?= old('username') ?>"
                           autocomplete="username" required>
                </div>
            </div>
            <div>
                <label for="reg-password" class="label">Passwort</label>
                <div class="field">
                    <?= $fieldIcon($icons['lock']) ?>
                    <input id="reg-password" name="password" type="password" class="input"
                           autocomplete="new-password" minlength="6" required>
                </div>
            </div>
            <div>
                <label for="reg-verypass" class="label">Passwort bestätigen</label>
                <div class="field">
                    <?= $fieldIcon($icons['shield']) ?>
                    <input id="reg-verypass" name="verypass" type="password" class="input"
                           autocomplete="new-password" minlength="6" required>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full">Konto erstellen</button>
            <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                Nach der Registrierung bist du sofort angemeldet.
            </p>
        </form>
    </div>
</div>
