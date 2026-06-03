/**
 * Create-post experience:
 *   - Quill rich-text description.
 *   - Images via file upload, drag & drop, image URL, or clipboard paste —
 *     with preview, drag-to-reorder, removal and an upload progress bar.
 *   - Location autocomplete + "use my location" (nearest city, offline dataset).
 *
 * Submitted via XHR/FormData so order is preserved and progress is reported.
 */
(function () {
    'use strict';

    const form = document.querySelector('[data-upload-form]');
    if (!form || typeof Quill === 'undefined') return;

    const ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const appUrl = (p) => basePath + '/' + String(p).replace(/^\/+/, '');
    const getJson = (u) => fetch(u, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } }).then((r) => r.json().then((b) => ({ ok: r.ok, body: b })));

    const dropzone = form.querySelector('[data-dropzone]');
    const fileInput = form.querySelector('[data-file-input]');
    const urlInput = form.querySelector('[data-image-url-input]');
    const previewList = form.querySelector('[data-preview-list]');
    const errorBox = form.querySelector('[data-upload-error]');
    const progress = form.querySelector('[data-upload-progress]');
    const progressBar = form.querySelector('[data-upload-bar]');
    const submitBtn = form.querySelector('[data-upload-submit]');
    const modal = document.querySelector('[data-upload-modal]');

    const maxFiles = parseInt(dropzone.dataset.maxFiles || '10', 10) || 10;
    const maxSize = parseInt(dropzone.dataset.maxSize || String(5 * 1024 * 1024), 10);

    /** Selected images, in order: {kind:'file', file} | {kind:'url', url}. */
    let items = [];
    const total = () => items.length;

    /* ---------- Quill ---------- */
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
    quill.getModule('toolbar').addHandler('image', function () {
        const url = window.prompt('Bild-URL eingeben (https://…):');
        if (url) {
            const range = quill.getSelection(true);
            quill.insertEmbed(range.index, 'image', url, 'user');
        }
    });

    /* ---------- Helpers ---------- */
    const showError = (m) => {
        errorBox.textContent = m;
        errorBox.classList.remove('hidden');
    };
    const clearError = () => errorBox.classList.add('hidden');

    function addFiles(list) {
        clearError();
        for (const file of list) {
            if (total() >= maxFiles) {
                showError('Maximal ' + maxFiles + ' Bilder.');
                break;
            }
            if (!ALLOWED.includes(file.type)) {
                showError('Nur JPEG, PNG, GIF oder WebP.');
                continue;
            }
            if (file.size > maxSize) {
                showError('„' + file.name + '“ ist zu groß.');
                continue;
            }
            items.push({ kind: 'file', file });
        }
        render();
    }

    function addUrl(url) {
        clearError();
        url = url.trim();
        if (!/^https?:\/\//i.test(url)) {
            showError('Bitte eine gültige http(s)-Bild-URL.');
            return;
        }
        if (total() >= maxFiles) {
            showError('Maximal ' + maxFiles + ' Bilder.');
            return;
        }
        const probe = new Image();
        probe.onload = () => {
            items.push({ kind: 'url', url });
            render();
        };
        probe.onerror = () => showError('Bild-URL konnte nicht geladen werden.');
        probe.src = url;
    }

    function render() {
        previewList.innerHTML = '';
        items.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = 'group relative aspect-square overflow-hidden rounded-md ring-1 ring-gray-300 dark:ring-gray-700 cursor-move';
            li.draggable = true;
            li.dataset.index = String(index);

            const img = document.createElement('img');
            img.className = 'h-full w-full object-cover';
            if (item.kind === 'url') {
                img.src = item.url;
            } else {
                const reader = new FileReader();
                reader.onload = (e) => (img.src = e.target.result);
                reader.readAsDataURL(item.file);
            }

            const badge = document.createElement('span');
            badge.className = 'absolute left-1 top-1 rounded bg-black/60 px-1.5 text-xs text-white';
            badge.textContent = item.kind === 'url' ? '🔗' : String(index + 1);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'absolute right-1 top-1 hidden rounded-full bg-black/60 px-1.5 text-xs text-white group-hover:block';
            remove.textContent = '✕';
            remove.addEventListener('click', () => {
                items.splice(index, 1);
                render();
            });

            li.append(img, badge, remove);
            previewList.appendChild(li);
        });
    }

    /* ---------- Inputs: file / drop / url / paste ---------- */
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

    form.querySelector('[data-add-url]').addEventListener('click', () => {
        if (urlInput.value.trim()) {
            addUrl(urlInput.value);
            urlInput.value = '';
        }
    });
    urlInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            form.querySelector('[data-add-url]').click();
        }
    });

    // Clipboard paste (only while the modal is open).
    document.addEventListener('paste', (e) => {
        if (!modal || modal.classList.contains('hidden')) return;
        const files = [];
        for (const item of e.clipboardData?.items || []) {
            if (item.kind === 'file' && item.type.startsWith('image/')) {
                const f = item.getAsFile();
                if (f) files.push(f);
            }
        }
        if (files.length) {
            e.preventDefault();
            addFiles(files);
        }
    });

    /* ---------- Reorder ---------- */
    let dragIndex = null;
    previewList.addEventListener('dragstart', (e) => {
        const li = e.target.closest('li');
        if (li) dragIndex = parseInt(li.dataset.index, 10);
    });
    previewList.addEventListener('dragover', (e) => e.preventDefault());
    previewList.addEventListener('drop', (e) => {
        e.preventDefault();
        const li = e.target.closest('li');
        if (!li || dragIndex === null) return;
        const dropIndex = parseInt(li.dataset.index, 10);
        if (dragIndex !== dropIndex) {
            const [moved] = items.splice(dragIndex, 1);
            items.splice(dropIndex, 0, moved);
            render();
        }
        dragIndex = null;
    });

    /* ---------- Location autocomplete + geolocation ---------- */
    const locInput = form.querySelector('[data-location-input]');
    const locBox = form.querySelector('[data-location-suggestions]');
    const useLoc = form.querySelector('[data-use-location]');
    let locTimer = null;

    locInput.addEventListener('input', () => {
        const q = locInput.value.trim();
        clearTimeout(locTimer);
        if (!q) {
            locBox.classList.add('hidden');
            return;
        }
        locTimer = setTimeout(() => {
            getJson(appUrl('locations?q=' + encodeURIComponent(q))).then(({ ok, body }) => {
                if (!ok || !body.success) return;
                locBox.innerHTML = '';
                if (!body.data.locations.length) {
                    locBox.classList.add('hidden');
                    return;
                }
                body.data.locations.forEach((loc) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-800';
                    b.textContent = loc;
                    b.addEventListener('click', () => {
                        locInput.value = loc;
                        locBox.classList.add('hidden');
                    });
                    locBox.appendChild(b);
                });
                locBox.classList.remove('hidden');
            });
        }, 200);
    });
    document.addEventListener('click', (e) => {
        if (!locInput.closest('.relative').contains(e.target)) locBox.classList.add('hidden');
    });

    useLoc.addEventListener('click', () => {
        if (!navigator.geolocation) {
            showError('Standort wird vom Browser nicht unterstützt.');
            return;
        }
        useLoc.disabled = true;
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                getJson(appUrl('locations/nearest?lat=' + pos.coords.latitude + '&lng=' + pos.coords.longitude))
                    .then(({ ok, body }) => {
                        useLoc.disabled = false;
                        if (ok && body.success && body.data.location) locInput.value = body.data.location;
                    })
                    .catch(() => (useLoc.disabled = false));
            },
            () => {
                useLoc.disabled = false;
                showError('Standort konnte nicht ermittelt werden.');
            },
            { timeout: 8000 }
        );
    });

    /* ---------- Submit ---------- */
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearError();
        if (total() === 0) {
            showError('Bitte mindestens ein Bild hinzufügen.');
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
        items.forEach((item) => {
            if (item.kind === 'file') data.append('images[]', item.file, item.file.name);
            else data.append('image_urls[]', item.url);
        });

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        submitBtn.disabled = true;
        progress.classList.remove('hidden');

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) progressBar.style.width = Math.round((e.loaded / e.total) * 100) + '%';
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
