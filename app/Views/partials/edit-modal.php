<?php

use App\Core\Csrf;

/**
 * Edit-post modal: change caption/details and manage images (remove existing,
 * reorder, add new). Populated and submitted by edit.js.
 */
?>
<div data-edit-modal class="fixed inset-0 z-40 hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-8"
     role="dialog" aria-modal="true" aria-labelledby="edit-title">
    <div class="card my-auto w-full max-w-lg">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 id="edit-title" class="text-lg font-bold">Beitrag bearbeiten</h2>
            <button type="button" data-close-edit class="btn-ghost h-8 w-8 !px-0 text-xl" aria-label="Schließen">&times;</button>
        </div>

        <form data-edit-form method="POST" enctype="multipart/form-data" class="space-y-4 px-5 py-4">
            <?= Csrf::field() ?>

            <div>
                <span class="label">Aktuelle Bilder <span class="font-normal text-gray-400">(ziehen zum Sortieren)</span></span>
                <ul data-existing-images class="grid grid-cols-3 gap-2 sm:grid-cols-4"></ul>
            </div>

            <div>
                <span class="label">Neue Bilder hinzufügen</span>
                <div data-edit-dropzone
                     class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-gray-300 px-4 py-5 text-center text-sm text-gray-500 transition hover:border-indigo-400 dark:border-gray-700 dark:text-gray-400">
                    <span><span class="font-medium text-indigo-600 dark:text-indigo-400">Dateien wählen</span> oder hierher ziehen</span>
                    <input data-edit-file-input type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="hidden">
                </div>
                <ul data-edit-preview-list class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4"></ul>
                <p data-edit-error class="mt-1 hidden text-sm text-rose-600 dark:text-rose-400"></p>
            </div>

            <div>
                <label for="edit-post-title" class="label">Titel</label>
                <input id="edit-post-title" type="text" name="title" class="input" maxlength="255" required>
            </div>

            <div>
                <span class="label">Beschreibung</span>
                <div data-edit-quill class="rounded-lg border border-gray-300 dark:border-gray-700"></div>
                <input type="hidden" name="description" data-edit-quill-input>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit-post-location" class="label">Ort</label>
                    <input id="edit-post-location" type="text" name="location" class="input" maxlength="255">
                </div>
                <div>
                    <label for="edit-post-date" class="label">Datum</label>
                    <input id="edit-post-date" type="date" name="taken_on" class="input">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_public" value="1" data-edit-public
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800">
                Öffentlich sichtbar
            </label>

            <div data-edit-progress class="hidden">
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                    <div data-edit-bar class="h-full w-0 bg-indigo-600 transition-all"></div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" data-close-edit class="btn-secondary">Abbrechen</button>
                <button type="submit" data-edit-submit class="btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>
