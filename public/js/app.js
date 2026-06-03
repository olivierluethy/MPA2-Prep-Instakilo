/**
 * Global UI behaviour: nav dropdown, upload modal, auth tabs, flash dismissal,
 * image carousels and AJAX-enhanced like/follow actions.
 *
 * Everything degrades gracefully — the like/follow forms still POST normally if
 * JavaScript is unavailable.
 */
(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    /* ---------- Helpers ---------- */
    function postJson(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
                Accept: 'application/json',
            },
        }).then((r) => r.json().then((body) => ({ ok: r.ok, body })));
    }

    /* ---------- Nav dropdown ---------- */
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-dropdown-toggle]');
        const openMenus = document.querySelectorAll('[data-dropdown-menu]:not(.hidden)');

        openMenus.forEach((menu) => {
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
    document.addEventListener('keydown', function (event) {
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

    /* ---------- Image carousels ---------- */
    document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
        const slides = carousel.querySelectorAll('[data-carousel-slide]');
        const dots = carousel.querySelectorAll('[data-carousel-dot]');
        if (slides.length < 2) return;
        let index = 0;

        function show(i) {
            index = (i + slides.length) % slides.length;
            slides.forEach((s, n) => s.classList.toggle('opacity-0', n !== index));
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
        const liked = button.dataset.liked === '1';
        const postId = button.dataset.postId;
        const action = liked ? 'unlike' : 'like';

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
        const following = button.dataset.following === '1';
        const action = following ? 'unfollow' : 'follow';

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
})();
