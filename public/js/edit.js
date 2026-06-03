/**
 * Edit-post modal: load a post's data, manage its images (remove existing,
 * reorder, add new) and submit changes via XHR with progress.
 */
(function () {
    'use strict';

    const modal = document.querySelector('[data-edit-modal]');
    const form = modal?.querySelector('[data-edit-form]');
    if (!modal || !form || typeof Quill === 'undefined') return;

    const ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const appUrl = (p) => basePath + '/' + String(p).replace(/^\/+/, '');

    const existingList = modal.querySelector('[data-existing-images]');
    const dropzone = modal.querySelector('[data-edit-dropzone]');
    const fileInput = modal.querySelector('[data-edit-file-input]');
    const newPreview = modal.querySelector('[data-edit-preview-list]');
    const errorBox = modal.querySelector('[data-edit-error]');
    const progress = modal.querySelector('[data-edit-progress]');
    const progressBar = modal.querySelector('[data-edit-bar]');
    const submitBtn = modal.querySelector('[data-edit-submit]');
    const maxFiles = 10;

    let postId = null;
    let existing = []; // [{id, url}] kept, ordered
    let removed = []; // ids removed
    let newFiles = []; // File[]
    let quill = null;

    function ensureQuill() {
        if (quill) return quill;
        quill = new Quill(modal.querySelector('[data-edit-quill]'), {
            theme: 'snow',
            placeholder: 'Beschreibung …',
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
        return quill;
    }

    function close() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
    function open() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.classList.remove('hidden');
    }
    function clearError() {
        errorBox.classList.add('hidden');
    }

    function totalImages() {
        return existing.length + newFiles.length;
    }

    function renderExisting() {
        existingList.innerHTML = '';
        existing.forEach((img, index) => {
            const li = document.createElement('li');
            li.className = 'group relative aspect-square overflow-hidden rounded-md ring-1 ring-gray-300 dark:ring-gray-700 cursor-move';
            li.draggable = true;
            li.dataset.index = String(index);
            li.innerHTML =
                '<img src="' + img.url + '" class="h-full w-full object-cover" alt="">' +
                '<span class="absolute left-1 top-1 rounded bg-black/60 px-1.5 text-xs text-white">' + (index + 1) + '</span>';
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'absolute right-1 top-1 hidden rounded-full bg-black/60 px-1.5 text-xs text-white group-hover:block';
            remove.textContent = '✕';
            remove.addEventListener('click', () => {
                removed.push(img.id);
                existing.splice(index, 1);
                renderExisting();
            });
            li.appendChild(remove);
            existingList.appendChild(li);
        });
    }

    function renderNew() {
        newPreview.innerHTML = '';
        newFiles.forEach((file, index) => {
            const li = document.createElement('li');
            li.className = 'group relative aspect-square overflow-hidden rounded-md ring-1 ring-gray-300 dark:ring-gray-700';
            const img = document.createElement('img');
            img.className = 'h-full w-full object-cover';
            const reader = new FileReader();
            reader.onload = (e) => (img.src = e.target.result);
            reader.readAsDataURL(file);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'absolute right-1 top-1 hidden rounded-full bg-black/60 px-1.5 text-xs text-white group-hover:block';
            remove.textContent = '✕';
            remove.addEventListener('click', () => {
                newFiles.splice(index, 1);
                renderNew();
            });
            li.append(img, remove);
            newPreview.appendChild(li);
        });
    }

    function addFiles(list) {
        clearError();
        for (const file of list) {
            if (totalImages() >= maxFiles) {
                showError('Maximal ' + maxFiles + ' Bilder.');
                break;
            }
            if (!ALLOWED.includes(file.type)) {
                showError('Nur JPEG, PNG, GIF oder WebP.');
                continue;
            }
            newFiles.push(file);
        }
        renderNew();
    }

    /* ---------- Open from a post's "Bearbeiten" button ---------- */
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-edit-post]');
        if (!trigger) return;
        postId = trigger.dataset.postId;
        clearError();
        newFiles = [];
        removed = [];
        renderNew();

        fetch(appUrl('posts/edit?id=' + postId), { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
            .then((r) => r.json())
            .then((res) => {
                if (!res.success) {
                    alert(res.message || 'Konnte Beitrag nicht laden.');
                    return;
                }
                const p = res.data.post;
                form.title.value = p.title;
                form.location.value = p.location || '';
                form.taken_on.value = p.taken_on || '';
                modal.querySelector('[data-edit-public]').checked = !!p.is_public;
                existing = p.images.map((i) => ({ id: i.id, url: i.url }));
                renderExisting();
                ensureQuill().root.innerHTML = p.description || '';
                open();
            })
            .catch(() => alert('Netzwerkfehler.'));
    });

    modal.querySelectorAll('[data-close-edit]').forEach((b) => b.addEventListener('click', close));
    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });

    /* ---------- New-image dropzone ---------- */
    dropzone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        addFiles(fileInput.files);
        fileInput.value = '';
    });
    ['dragenter', 'dragover'].forEach((evt) =>
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.add('border-indigo-500');
        })
    );
    ['dragleave', 'drop'].forEach((evt) =>
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-indigo-500');
        })
    );
    dropzone.addEventListener('drop', (e) => {
        if (e.dataTransfer?.files?.length) addFiles(e.dataTransfer.files);
    });

    /* ---------- Reorder existing images ---------- */
    let dragIndex = null;
    existingList.addEventListener('dragstart', (e) => {
        const li = e.target.closest('li');
        if (li) dragIndex = parseInt(li.dataset.index, 10);
    });
    existingList.addEventListener('dragover', (e) => e.preventDefault());
    existingList.addEventListener('drop', (e) => {
        e.preventDefault();
        const li = e.target.closest('li');
        if (!li || dragIndex === null) return;
        const dropIndex = parseInt(li.dataset.index, 10);
        if (dragIndex !== dropIndex) {
            const [moved] = existing.splice(dragIndex, 1);
            existing.splice(dropIndex, 0, moved);
            renderExisting();
        }
        dragIndex = null;
    });

    /* ---------- Submit ---------- */
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearError();
        if (!form.title.value.trim()) {
            showError('Bitte einen Titel eingeben.');
            return;
        }
        if (totalImages() < 1) {
            showError('Ein Beitrag braucht mindestens ein Bild.');
            return;
        }

        const html = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
        modal.querySelector('[data-edit-quill-input]').value = html;

        const data = new FormData();
        data.append('_csrf_token', csrfToken);
        data.append('title', form.title.value);
        data.append('description', html);
        data.append('location', form.location.value);
        data.append('taken_on', form.taken_on.value);
        data.append('is_public', modal.querySelector('[data-edit-public]').checked ? '1' : '0');
        existing.forEach((img) => data.append('order[]', img.id));
        removed.forEach((id) => data.append('remove_images[]', id));
        newFiles.forEach((file) => data.append('images[]', file, file.name));

        const xhr = new XMLHttpRequest();
        xhr.open('POST', appUrl('posts/update?id=' + postId));
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
                window.location.reload();
            } else {
                submitBtn.disabled = false;
                progress.classList.add('hidden');
                progressBar.style.width = '0%';
                showError(body.message || 'Speichern fehlgeschlagen.');
            }
        });
        xhr.addEventListener('error', function () {
            submitBtn.disabled = false;
            progress.classList.add('hidden');
            showError('Netzwerkfehler.');
        });
        xhr.send(data);
    });
})();
