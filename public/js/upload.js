/**
 * Create-post experience:
 *   - Quill rich-text editor for the description.
 *   - Multi-image upload with drag & drop, preview, drag-to-reorder, removal,
 *     client-side validation and an upload progress bar.
 *
 * Files are submitted via XMLHttpRequest/FormData (not the native input) so the
 * user-chosen order is preserved and progress can be reported.
 */
(function () {
    'use strict';

    const form = document.querySelector('[data-upload-form]');
    if (!form || typeof Quill === 'undefined') return;

    const ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const dropzone = form.querySelector('[data-dropzone]');
    const fileInput = form.querySelector('[data-file-input]');
    const previewList = form.querySelector('[data-preview-list]');
    const errorBox = form.querySelector('[data-upload-error]');
    const progress = form.querySelector('[data-upload-progress]');
    const progressBar = form.querySelector('[data-upload-bar]');
    const submitBtn = form.querySelector('[data-upload-submit]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const maxFiles = parseInt(dropzone.dataset.maxFiles || '10', 10) || 10;
    const maxSize = parseInt(dropzone.dataset.maxSize || String(5 * 1024 * 1024), 10);

    /** Selected files, in display/upload order. */
    let files = [];

    /* ---------- Quill editor ---------- */
    const quill = new Quill(form.querySelector('[data-quill]'), {
        theme: 'snow',
        placeholder: 'Schreibe eine Beschreibung …',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['clean'],
            ],
        },
    });

    // Insert images by URL (keeps content lean; base64 is stripped server-side).
    quill.getModule('toolbar').addHandler('image', function () {
        const url = window.prompt('Bild-URL eingeben (https://…):');
        if (url) {
            const range = quill.getSelection(true);
            quill.insertEmbed(range.index, 'image', url, 'user');
        }
    });

    /* ---------- Validation + helpers ---------- */
    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }
    function clearError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function addFiles(fileList) {
        clearError();
        for (const file of fileList) {
            if (files.length >= maxFiles) {
                showError('Maximal ' + maxFiles + ' Bilder erlaubt.');
                break;
            }
            if (!ALLOWED.includes(file.type)) {
                showError('Nur JPEG, PNG, GIF oder WebP erlaubt.');
                continue;
            }
            if (file.size > maxSize) {
                showError('„' + file.name + '“ ist zu groß.');
                continue;
            }
            files.push(file);
        }
        renderPreviews();
    }

    function removeFile(index) {
        files.splice(index, 1);
        renderPreviews();
    }

    function renderPreviews() {
        previewList.innerHTML = '';
        files.forEach((file, index) => {
            const li = document.createElement('li');
            li.className =
                'group relative aspect-square overflow-hidden rounded-md ring-1 ring-gray-300 dark:ring-gray-700 cursor-move';
            li.draggable = true;
            li.dataset.index = String(index);

            const img = document.createElement('img');
            img.className = 'h-full w-full object-cover';
            img.alt = file.name;
            const reader = new FileReader();
            reader.onload = (e) => (img.src = e.target.result);
            reader.readAsDataURL(file);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className =
                'absolute right-1 top-1 hidden rounded-full bg-black/60 px-1.5 text-xs text-white group-hover:block';
            remove.textContent = '✕';
            remove.setAttribute('aria-label', 'Bild entfernen');
            remove.addEventListener('click', () => removeFile(index));

            const order = document.createElement('span');
            order.className = 'absolute left-1 top-1 rounded bg-black/60 px-1.5 text-xs text-white';
            order.textContent = String(index + 1);

            li.append(img, remove, order);
            previewList.appendChild(li);
        });
    }

    /* ---------- Drag & drop (from desktop) ---------- */
    dropzone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        addFiles(fileInput.files);
        fileInput.value = '';
    });

    ['dragenter', 'dragover'].forEach((evt) =>
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.add('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/40');
        })
    );
    ['dragleave', 'drop'].forEach((evt) =>
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-950/40');
        })
    );
    dropzone.addEventListener('drop', (e) => {
        if (e.dataTransfer?.files?.length) addFiles(e.dataTransfer.files);
    });

    /* ---------- Drag-to-reorder previews ---------- */
    let dragIndex = null;
    previewList.addEventListener('dragstart', (e) => {
        const li = e.target.closest('li');
        if (li) dragIndex = parseInt(li.dataset.index, 10);
    });
    previewList.addEventListener('dragover', (e) => e.preventDefault());
    previewList.addEventListener('drop', (e) => {
        e.preventDefault();
        const li = e.target.closest('li');
        if (li === null || dragIndex === null) return;
        const dropIndex = parseInt(li.dataset.index, 10);
        if (dragIndex !== dropIndex) {
            const [moved] = files.splice(dragIndex, 1);
            files.splice(dropIndex, 0, moved);
            renderPreviews();
        }
        dragIndex = null;
    });

    /* ---------- Submit via XHR (progress + ordered files) ---------- */
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearError();

        if (files.length === 0) {
            showError('Bitte mindestens ein Bild auswählen.');
            return;
        }
        if (!form.title.value.trim()) {
            showError('Bitte einen Titel eingeben.');
            return;
        }

        const html = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
        form.querySelector('[data-quill-input]').value = html;

        const data = new FormData();
        data.append('_csrf_token', csrfToken);
        data.append('title', form.title.value);
        data.append('description', html);
        data.append('location', form.location.value);
        data.append('taken_on', form.taken_on.value);
        data.append('is_public', form.querySelector('[name="is_public"]').checked ? '1' : '0');
        files.forEach((file) => data.append('images[]', file, file.name));

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        submitBtn.disabled = true;
        progress.classList.remove('hidden');

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                progressBar.style.width = Math.round((e.loaded / e.total) * 100) + '%';
            }
        });

        xhr.addEventListener('load', function () {
            let body = {};
            try {
                body = JSON.parse(xhr.responseText);
            } catch (e) {
                /* ignore */
            }
            if (xhr.status >= 200 && xhr.status < 300 && body.success) {
                window.location.href = body.data.redirect || '/home';
            } else {
                submitBtn.disabled = false;
                progress.classList.add('hidden');
                progressBar.style.width = '0%';
                showError(body.message || 'Upload fehlgeschlagen.');
            }
        });

        xhr.addEventListener('error', function () {
            submitBtn.disabled = false;
            progress.classList.add('hidden');
            showError('Netzwerkfehler. Bitte erneut versuchen.');
        });

        xhr.send(data);
    });
})();
