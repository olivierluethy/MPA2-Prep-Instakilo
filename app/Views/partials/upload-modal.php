<?php

use App\Core\Csrf;

/**
 * Create-post modal: rich-text description (Quill) + multi-image drag & drop
 * upload with preview, reorder and removal. Submitted via fetch (see upload.js).
 */
?>
<div data-upload-modal class="fixed inset-0 z-40 hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-8"
     role="dialog" aria-modal="true" aria-labelledby="upload-title">
    <div class="card my-auto w-full max-w-lg">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h2 id="upload-title" class="text-lg font-bold">Beitrag erstellen</h2>
            <button type="button" data-close-upload class="btn-ghost h-8 w-8 !px-0 text-xl" aria-label="Schließen">&times;</button>
        </div>

        <form data-upload-form action="<?= url('posts/store') ?>" method="POST" enctype="multipart/form-data"
              class="space-y-4 px-5 py-4">
            <?= Csrf::field() ?>

            <!-- Drag & drop zone -->
            <div>
                <span class="label">Bilder</span>
                <div data-dropzone
                     data-max-files="<?= (int) config('uploads.max_files') ?>"
                     data-max-size="<?= (int) config('uploads.max_file_size') ?>"
                     class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 transition hover:border-indigo-400 dark:border-gray-700 dark:text-gray-400">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                    <p><span class="font-medium text-indigo-600 dark:text-indigo-400">Dateien wählen</span> oder hierher ziehen</p>
                    <p class="text-xs">JPEG, PNG, GIF, WebP · max. <?= (int) config('uploads.max_files') ?> Bilder · je max. <?= round(((int) config('uploads.max_file_size')) / 1024 / 1024, 1) ?> MB</p>
                    <input data-file-input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="hidden">
                </div>
                <!-- Thumbnails (drag to reorder, click ✕ to remove) -->
                <ul data-preview-list class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4"></ul>
                <p data-upload-error class="mt-1 hidden text-sm text-rose-600 dark:text-rose-400"></p>
            </div>

            <div>
                <label for="post-title" class="label">Titel</label>
                <input id="post-title" type="text" name="title" class="input" maxlength="255"
                       placeholder="Gib deinem Beitrag einen Titel" required>
            </div>

            <div>
                <span class="label">Beschreibung</span>
                <div data-quill class="rounded-lg border border-gray-300 dark:border-gray-700"></div>
                <input type="hidden" name="description" data-quill-input>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="post-location" class="label">Ort</label>
                    <input id="post-location" type="text" name="location" class="input" maxlength="255" placeholder="z.&nbsp;B. Zürich">
                </div>
                <div>
                    <label for="post-date" class="label">Datum</label>
                    <input id="post-date" type="date" name="taken_on" class="input">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_public" value="1"
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800">
                Öffentlich sichtbar
            </label>

            <!-- Progress -->
            <div data-upload-progress class="hidden">
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                    <div data-upload-bar class="h-full w-0 bg-indigo-600 transition-all"></div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" data-close-upload class="btn-secondary">Abbrechen</button>
                <button type="submit" data-upload-submit class="btn-primary">Hochladen</button>
            </div>
        </form>
    </div>
</div>
