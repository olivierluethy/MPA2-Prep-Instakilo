<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Message;

$loggedIn = Auth::check();
$unread = $loggedIn ? (new Message())->unreadCount((int) Auth::id()) : 0;
?>
<nav class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4">
        <a href="<?= url('home') ?>" class="flex items-center gap-2 shrink-0">
            <img src="<?= asset('assets/logo.png') ?>" alt="" class="h-8 w-8">
            <span class="text-lg font-bold tracking-tight">Instakilo</span>
        </a>

        <div class="hidden flex-1 sm:block">
            <label for="nav-search" class="sr-only">Suche nach Personen</label>
            <input id="nav-search" type="search" placeholder="Suche nach Personen"
                   class="input max-w-sm" autocomplete="off">
        </div>

        <div class="flex items-center gap-2">
            <button type="button" data-theme-toggle aria-label="Theme wechseln"
                    class="btn-ghost h-9 w-9 !px-0">
                <svg data-theme-icon="light" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                <svg data-theme-icon="dark" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
            </button>

            <?php if ($loggedIn): ?>
                <a href="<?= url('messages') ?>" class="btn-ghost relative h-9 w-9 !px-0" aria-label="Nachrichten">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                    <?php if ($unread > 0): ?>
                        <span class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white"><?= $unread > 9 ? '9+' : $unread ?></span>
                    <?php endif; ?>
                </a>
                <button type="button" data-open-upload class="btn-primary h-9" aria-haspopup="dialog">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span class="hidden sm:inline">Hochladen</span>
                </button>

                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-toggle
                            class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full ring-1 ring-gray-300 dark:ring-gray-700"
                            aria-label="Menü">
                        <img src="<?= url('avatar?id=' . (int) Auth::id()) ?>" alt="" class="h-full w-full object-cover">
                    </button>
                    <div data-dropdown-menu
                         class="absolute right-0 mt-2 hidden w-44 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-800 dark:bg-gray-900">
                        <a href="<?= url('profile') ?>" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800">Profil</a>
                        <a href="<?= url('saved') ?>" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800">Gespeichert</a>
                        <a href="<?= url('messages') ?>" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800">Nachrichten</a>
                        <hr class="my-1 border-gray-200 dark:border-gray-800">
                        <form action="<?= url('logout') ?>" method="POST">
                            <?= Csrf::field() ?>
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-rose-600 hover:bg-gray-100 dark:hover:bg-gray-800">Logout</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= url('login') ?>" class="btn-primary h-9">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
