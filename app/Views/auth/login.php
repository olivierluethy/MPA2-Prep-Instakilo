<?php
/** Login / registration page. */

use App\Core\Csrf;
?>
<div class="mx-auto grid max-w-4xl items-center gap-8 py-8 md:grid-cols-2">
    <div class="hidden md:block">
        <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 p-10 text-white">
            <h1 class="text-3xl font-bold leading-tight">Eine neue Art, verbunden zu sein.</h1>
            <p class="mt-3 text-lg text-indigo-100">Mit Instakilo.</p>
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
                <input id="login" name="login" type="text" class="input" value="<?= old('login') ?>"
                       autocomplete="username" required>
            </div>
            <div>
                <label for="login-password" class="label">Passwort</label>
                <input id="login-password" name="password" type="password" class="input"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn-primary w-full">Login</button>
        </form>

        <!-- Register -->
        <form action="<?= url('register') ?>" method="POST" class="mt-5 hidden space-y-3" data-auth-panel="register" novalidate>
            <?= Csrf::field() ?>
            <div>
                <label for="reg-email" class="label">E-Mail</label>
                <input id="reg-email" name="email" type="email" class="input" value="<?= old('email') ?>"
                       autocomplete="email" required>
            </div>
            <div>
                <label for="reg-username" class="label">Benutzername</label>
                <input id="reg-username" name="username" type="text" class="input" value="<?= old('username') ?>"
                       autocomplete="username" required>
            </div>
            <div>
                <label for="reg-password" class="label">Passwort</label>
                <input id="reg-password" name="password" type="password" class="input"
                       autocomplete="new-password" minlength="6" required>
            </div>
            <div>
                <label for="reg-verypass" class="label">Passwort bestätigen</label>
                <input id="reg-verypass" name="verypass" type="password" class="input"
                       autocomplete="new-password" minlength="6" required>
            </div>
            <button type="submit" class="btn-primary w-full">Konto erstellen</button>
        </form>
    </div>
</div>
