<?php

use App\Core\Csrf;

/**
 * "Share post via DM" modal. The user searches for a recipient, optionally adds
 * a note, and sends the post. Driven by app.js.
 */
?>
<div data-share-modal class="fixed inset-0 z-40 hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-8"
     role="dialog" aria-modal="true" aria-labelledby="share-title">
    <div class="card my-auto w-full max-w-sm">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 id="share-title" class="text-lg font-bold">Beitrag teilen</h2>
            <button type="button" data-close-share class="btn-ghost h-8 w-8 !px-0 text-xl" aria-label="Schließen">&times;</button>
        </div>

        <form data-share-form method="POST" class="space-y-3 px-5 py-4">
            <?= Csrf::field() ?>
            <p class="truncate text-sm text-gray-500 dark:text-gray-400">„<span data-share-post-title></span>“ an einen Benutzer senden:</p>

            <div class="relative">
                <label class="sr-only" for="share-search">Empfänger suchen</label>
                <input id="share-search" type="search" autocomplete="off" class="input" placeholder="Benutzer suchen …" data-share-search>
                <div data-share-results class="mt-1 max-h-44 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-800"></div>
            </div>

            <div data-share-selected class="hidden items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-gray-800">
                <span>An: <span class="font-semibold" data-share-selected-name></span></span>
                <button type="button" class="ml-auto text-gray-400 hover:text-rose-500" data-share-clear aria-label="Auswahl entfernen">&times;</button>
            </div>
            <input type="hidden" name="recipient" data-share-recipient>

            <div>
                <label class="sr-only" for="share-note">Nachricht (optional)</label>
                <input id="share-note" type="text" name="body" maxlength="2000" class="input" placeholder="Nachricht hinzufügen (optional)">
            </div>

            <p data-share-error class="hidden text-sm text-rose-600 dark:text-rose-400"></p>

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" data-close-share class="btn-secondary">Abbrechen</button>
                <button type="submit" data-share-submit class="btn-primary" disabled>Senden</button>
            </div>
        </form>
    </div>
</div>
