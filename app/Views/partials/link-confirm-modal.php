<?php
/**
 * Global "leave Instakilo?" confirmation shown before any external link opens.
 * Driven by app.js (intercepts cross-origin <a> clicks).
 */
?>
<div data-link-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="link-modal-title">
    <div class="card w-full max-w-sm p-5">
        <h2 id="link-modal-title" class="text-lg font-bold">Instakilo verlassen?</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Möchtest du Instakilo verlassen und diesen Link öffnen?</p>
        <p class="mt-3 break-all rounded bg-gray-100 px-2 py-1 text-xs text-gray-500 dark:bg-gray-800" data-link-url></p>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-link-cancel>Abbrechen</button>
            <a class="btn-primary" data-link-go target="_blank" rel="noopener noreferrer nofollow">Öffnen</a>
        </div>
    </div>
</div>
