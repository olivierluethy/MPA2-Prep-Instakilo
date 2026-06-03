/**
 * Global UI behaviour: nav dropdown, upload modal, auth tabs, flash dismissal,
 * image slider, AJAX like/follow, comments and user search.
 *
 * Like/follow/comment forms degrade gracefully — they still POST normally if
 * JavaScript is unavailable.
 */
(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const basePath = document.querySelector('meta[name="base-path"]')?.content || '';

    /** Build a root-relative URL honouring APP_BASE_PATH (mirrors PHP url()). */
    function appUrl(path) {
        return basePath + '/' + String(path).replace(/^\/+/, '');
    }

    /* ---------- Fetch helpers ---------- */
    function postJson(url) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken, Accept: 'application/json' },
        }).then((r) => r.json().then((body) => ({ ok: r.ok, body })));
    }
    function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken, Accept: 'application/json' },
            body: formData,
        }).then((r) => r.json().then((body) => ({ ok: r.ok, body })));
    }
    function getJson(url) {
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
            .then((r) => r.json().then((body) => ({ ok: r.ok, body })));
    }

    /* ---------- Nav dropdown ---------- */
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-dropdown-toggle]');
        document.querySelectorAll('[data-dropdown-menu]:not(.hidden)').forEach((menu) => {
            if (!toggle || menu.closest('[data-dropdown]') !== toggle.closest('[data-dropdown]')) {
                menu.classList.add('hidden');
            }
        });
        if (toggle) {
            toggle.closest('[data-dropdown]').querySelector('[data-dropdown-menu]').classList.toggle('hidden');
        }
    });

    /* ---------- Upload modal ---------- */
    const modal = document.querySelector('[data-upload-modal]');
    function openModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-open-upload]')) openModal();
        if (event.target.closest('[data-close-upload]')) closeModal();
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeModal();
    });
    window.InstakiloModal = { close: closeModal };

    /* ---------- Auth tabs (login / register) ---------- */
    const tabs = document.querySelector('[data-auth-tabs]');
    if (tabs) {
        const activeCls = ['bg-white', 'dark:bg-gray-700', 'shadow'];
        function selectTab(name) {
            tabs.querySelectorAll('[data-auth-tab]').forEach((btn) => {
                btn.classList.toggle('text-gray-900', btn.dataset.authTab === name);
                activeCls.forEach((c) => btn.classList.toggle(c, btn.dataset.authTab === name));
            });
            document.querySelectorAll('[data-auth-panel]').forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.authPanel !== name);
            });
        }
        tabs.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-auth-tab]');
            if (btn) selectTab(btn.dataset.authTab);
        });
        selectTab('login');
    }

    /* ---------- Flash dismissal ---------- */
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-flash-dismiss]')) {
            event.target.closest('[data-flash]').remove();
        }
    });

    /* ---------- Image slider (one slide visible, smooth translate) ---------- */
    document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
        const track = carousel.querySelector('[data-carousel-track]');
        const slides = carousel.querySelectorAll('[data-carousel-slide]');
        const dots = carousel.querySelectorAll('[data-carousel-dot]');
        if (!track || slides.length < 2) return;
        let index = 0;

        function show(i) {
            index = (i + slides.length) % slides.length;
            track.style.transform = 'translateX(-' + index * 100 + '%)';
            dots.forEach((d, n) => d.classList.toggle('opacity-50', n !== index));
        }
        carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => show(index - 1));
        carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => show(index + 1));
    });

    /* ---------- Like toggle (AJAX) ---------- */
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-like-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[data-like-toggle]');
        const action = button.dataset.liked === '1' ? 'unlike' : 'like';

        postJson(form.action.replace(/posts\/(un)?like/, 'posts/' + action))
            .then(({ ok, body }) => {
                if (!ok || !body.success) return;
                const nowLiked = body.data.liked;
                button.dataset.liked = nowLiked ? '1' : '0';
                button.setAttribute('aria-pressed', nowLiked ? 'true' : 'false');
                button.querySelector('[data-like-icon]').setAttribute('fill', nowLiked ? 'currentColor' : 'none');
                form.action = form.action.replace(/posts\/(un)?like/, 'posts/' + (nowLiked ? 'unlike' : 'like'));
                const counter = form.closest('article').querySelector('[data-like-count]');
                if (counter) counter.textContent = body.data.likeCount;
            })
            .catch(() => form.submit());
    });

    /* ---------- Follow toggle (AJAX) ---------- */
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-follow-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[data-follow-toggle]');
        const action = button.dataset.following === '1' ? 'unfollow' : 'follow';

        postJson(form.action.replace(/(un)?follow/, action))
            .then(({ ok, body }) => {
                if (!ok || !body.success) return;
                const nowFollowing = body.data.following;
                button.dataset.following = nowFollowing ? '1' : '0';
                button.querySelector('[data-follow-label]').textContent = nowFollowing ? 'Unfollow' : 'Follow';
                button.classList.toggle('btn-primary', !nowFollowing);
                button.classList.toggle('btn-secondary', nowFollowing);
                form.action = form.action.replace(/(un)?follow/, nowFollowing ? 'unfollow' : 'follow');
                const counter = document.querySelector('[data-follower-count]');
                if (counter) counter.textContent = body.data.followerCount;
            })
            .catch(() => form.submit());
    });

    /* ---------- Comments ---------- */
    // Build one <li> for a comment. Body/username use textContent → XSS-safe.
    function renderComment(c) {
        const li = document.createElement('li');
        li.className = 'flex flex-wrap items-baseline gap-x-2';
        li.dataset.commentId = c.id;

        const a = document.createElement('a');
        a.className = 'font-semibold hover:underline';
        a.href = appUrl('profile/visit?id=' + c.user_id);
        a.textContent = c.username;

        const span = document.createElement('span');
        span.className = 'text-gray-700 dark:text-gray-300';
        span.textContent = c.body;

        li.append(a, span);
        if (c.created_at) {
            const t = document.createElement('span');
            t.className = 'text-xs text-gray-400';
            t.textContent = c.created_at;
            li.append(t);
        }
        return li;
    }

    // Add a comment (AJAX).
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-comment-form]');
        if (!form) return;
        event.preventDefault();
        const input = form.querySelector('[data-comment-input]');
        if (!input.value.trim()) return;

        postForm(form.action, new FormData(form))
            .then(({ ok, body }) => {
                if (!ok || !body.success) return;
                const section = form.closest('[data-comments]');
                section.querySelector('[data-comments-list]').appendChild(renderComment(body.data.comment));
                section.querySelector('[data-comments-empty]')?.remove();
                const countEl = section.querySelector('[data-comments-count]');
                if (countEl) countEl.textContent = body.data.commentCount;
                input.value = '';
            })
            .catch(() => form.submit());
    });

    // Expand / paginate the full comment list (AJAX).
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-comments-toggle]');
        if (!toggle) return;
        const section = toggle.closest('[data-comments]');
        const list = section.querySelector('[data-comments-list]');
        const nextPage = parseInt(section.dataset.page || '0', 10) + 1;

        toggle.disabled = true;
        getJson(appUrl('posts/comments?id=' + section.dataset.postId + '&page=' + nextPage))
            .then(({ ok, body }) => {
                toggle.disabled = false;
                if (!ok || !body.success) return;
                if (nextPage === 1) list.innerHTML = ''; // replace preview with full list
                body.data.comments.forEach((c) => list.appendChild(renderComment(c)));
                section.dataset.page = String(nextPage);
                if (body.data.hasMore) {
                    toggle.textContent = 'Weitere Kommentare laden';
                } else {
                    toggle.remove();
                }
            })
            .catch(() => {
                toggle.disabled = false;
            });
    });

    /* ---------- User search ---------- */
    const searchInput = document.getElementById('nav-search');
    if (searchInput) {
        const wrap = searchInput.parentElement;
        wrap.classList.add('relative');
        const dropdown = document.createElement('div');
        dropdown.className =
            'absolute z-40 mt-1 hidden w-full max-w-sm overflow-hidden rounded-lg border border-gray-200 ' +
            'bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900';
        wrap.appendChild(dropdown);
        let timer = null;

        function renderResults(users) {
            dropdown.textContent = '';
            if (!users.length) {
                const empty = document.createElement('p');
                empty.className = 'px-3 py-2 text-sm text-gray-500 dark:text-gray-400';
                empty.textContent = 'Keine Benutzer gefunden';
                dropdown.appendChild(empty);
            } else {
                users.forEach((u) => {
                    const a = document.createElement('a');
                    a.href = appUrl('profile/visit?id=' + u.id);
                    a.className = 'flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800';
                    const img = document.createElement('img');
                    img.src = appUrl('avatar?id=' + u.id);
                    img.className = 'h-6 w-6 rounded-full object-cover';
                    img.alt = '';
                    const span = document.createElement('span');
                    span.className = 'font-medium';
                    span.textContent = u.username;
                    a.append(img, span);
                    dropdown.appendChild(a);
                });
            }
            dropdown.classList.remove('hidden');
        }

        searchInput.addEventListener('input', function () {
            const q = searchInput.value.trim();
            clearTimeout(timer);
            if (q === '') {
                dropdown.classList.add('hidden');
                return;
            }
            timer = setTimeout(() => {
                getJson(appUrl('search?q=' + encodeURIComponent(q)))
                    .then(({ ok, body }) => {
                        if (ok && body.success) renderResults(body.data.users);
                    })
                    .catch(() => {});
            }, 200);
        });

        searchInput.addEventListener('focus', () => {
            if (dropdown.children.length) dropdown.classList.remove('hidden');
        });
        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) dropdown.classList.add('hidden');
        });
    }
})();
