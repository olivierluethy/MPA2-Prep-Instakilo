/**
 * Global UI behaviour: nav dropdown, modals, auth tabs, flash, image slider,
 * AJAX like/follow/save/repost, comments (add/edit/delete), post delete,
 * share-via-DM, and user/post search.
 *
 * Forms degrade gracefully — they still POST normally without JavaScript.
 */
(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
    const currentUserId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '', 10) || null;

    const appUrl = (path) => basePath + '/' + String(path).replace(/^\/+/, '');

    /* ---------- Fetch helpers ---------- */
    const jsonHeaders = { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken, Accept: 'application/json' };
    function postJson(url) {
        return fetch(url, { method: 'POST', headers: jsonHeaders }).then((r) => r.json().then((body) => ({ ok: r.ok, body })));
    }
    function postForm(url, formData) {
        return fetch(url, { method: 'POST', headers: jsonHeaders, body: formData }).then((r) => r.json().then((body) => ({ ok: r.ok, body })));
    }
    function getJson(url) {
        return fetch(url, { headers: jsonHeaders }).then((r) => r.json().then((body) => ({ ok: r.ok, body })));
    }

    /* ---------- Generic dropdowns (nav + post owner menu) ---------- */
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

    /* ---------- Modals (upload / edit / share) ---------- */
    function openModal(sel) {
        const m = document.querySelector(sel);
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(m) {
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = '';
    }
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-open-upload]')) openModal('[data-upload-modal]');
        if (event.target.closest('[data-close-upload]')) closeModal(document.querySelector('[data-upload-modal]'));
        if (event.target.closest('[data-close-share]')) closeModal(document.querySelector('[data-share-modal]'));
        const overlay = event.target.closest('[data-upload-modal],[data-share-modal]');
        if (overlay && event.target === overlay) closeModal(overlay);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-upload-modal],[data-edit-modal],[data-share-modal]').forEach(closeModal);
        }
    });
    window.InstakiloModal = { open: openModal, close: closeModal };

    /* ---------- Auth tabs ---------- */
    const tabs = document.querySelector('[data-auth-tabs]');
    if (tabs) {
        const activeCls = ['bg-white', 'dark:bg-gray-700', 'shadow'];
        const selectTab = (name) => {
            tabs.querySelectorAll('[data-auth-tab]').forEach((btn) => {
                btn.classList.toggle('text-gray-900', btn.dataset.authTab === name);
                activeCls.forEach((c) => btn.classList.toggle(c, btn.dataset.authTab === name));
            });
            document.querySelectorAll('[data-auth-panel]').forEach((p) => p.classList.toggle('hidden', p.dataset.authPanel !== name));
        };
        tabs.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-auth-tab]');
            if (btn) selectTab(btn.dataset.authTab);
        });
        selectTab('login');
    }

    /* ---------- Flash dismissal ---------- */
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-flash-dismiss]')) event.target.closest('[data-flash]').remove();
    });

    /* ---------- Image slider ---------- */
    document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
        const track = carousel.querySelector('[data-carousel-track]');
        const slides = carousel.querySelectorAll('[data-carousel-slide]');
        const dots = carousel.querySelectorAll('[data-carousel-dot]');
        if (!track || slides.length < 2) return;
        let index = 0;
        const show = (i) => {
            index = (i + slides.length) % slides.length;
            track.style.transform = 'translateX(-' + index * 100 + '%)';
            dots.forEach((d, n) => d.classList.toggle('opacity-50', n !== index));
        };
        carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => show(index - 1));
        carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => show(index + 1));
    });

    /* ---------- Like ---------- */
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-like-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[data-like-toggle]');
        const action = button.dataset.liked === '1' ? 'unlike' : 'like';
        postJson(form.action.replace(/posts\/(un)?like/, 'posts/' + action))
            .then(({ ok, body }) => {
                if (!ok || !body.success) return;
                const liked = body.data.liked;
                button.dataset.liked = liked ? '1' : '0';
                button.setAttribute('aria-pressed', liked ? 'true' : 'false');
                button.querySelector('[data-like-icon]').setAttribute('fill', liked ? 'currentColor' : 'none');
                form.action = form.action.replace(/posts\/(un)?like/, 'posts/' + (liked ? 'unlike' : 'like'));
                const counter = form.closest('article').querySelector('[data-like-count]');
                if (counter) counter.textContent = body.data.likeCount;
            })
            .catch(() => form.submit());
    });

    /* ---------- Follow ---------- */
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-follow-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[data-follow-toggle]');
        const action = button.dataset.following === '1' ? 'unfollow' : 'follow';
        postJson(form.action.replace(/(un)?follow/, action))
            .then(({ ok, body }) => {
                if (!ok || !body.success) return;
                const following = body.data.following;
                button.dataset.following = following ? '1' : '0';
                button.querySelector('[data-follow-label]').textContent = following ? 'Unfollow' : 'Follow';
                button.classList.toggle('btn-primary', !following);
                button.classList.toggle('btn-secondary', following);
                form.action = form.action.replace(/(un)?follow/, following ? 'unfollow' : 'follow');
                const counter = document.querySelector('[data-follower-count]');
                if (counter) counter.textContent = body.data.followerCount;
            })
            .catch(() => form.submit());
    });

    /* ---------- Save (bookmark) ---------- */
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-save-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[data-save-toggle]');
        const action = button.dataset.saved === '1' ? 'unsave' : 'save';
        postJson(form.action.replace(/posts\/(un)?save/, 'posts/' + action))
            .then(({ ok, body }) => {
                if (!ok || !body.success) return;
                const saved = body.data.saved;
                button.dataset.saved = saved ? '1' : '0';
                button.querySelector('[data-save-icon]').setAttribute('fill', saved ? 'currentColor' : 'none');
                form.action = form.action.replace(/posts\/(un)?save/, 'posts/' + (saved ? 'unsave' : 'save'));
            })
            .catch(() => form.submit());
    });

    /* ---------- Repost ---------- */
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-repost-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('[data-repost-toggle]');
        const action = button.dataset.reposted === '1' ? 'unrepost' : 'repost';
        postJson(form.action.replace(/posts\/(un)?repost/, 'posts/' + action))
            .then(({ ok, body }) => {
                if (!ok || !body.success) {
                    if (body && body.message) alert(body.message);
                    return;
                }
                const reposted = body.data.reposted;
                button.dataset.reposted = reposted ? '1' : '0';
                button.classList.toggle('text-emerald-500', reposted);
                button.classList.toggle('text-gray-500', !reposted);
                form.action = form.action.replace(/posts\/(un)?repost/, 'posts/' + (reposted ? 'unrepost' : 'repost'));
            })
            .catch(() => form.submit());
    });

    /* ---------- Delete post (confirm) ---------- */
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-delete-post]');
        if (!form) return;
        if (!window.confirm('Diesen Beitrag wirklich löschen? Das kann nicht rückgängig gemacht werden.')) {
            event.preventDefault();
        }
    });

    /* ---------- Comments ---------- */
    function renderComment(c) {
        const li = document.createElement('li');
        li.className = 'group flex flex-wrap items-baseline gap-x-2';
        li.dataset.commentId = c.id;
        li.dataset.commentUser = c.user_id;

        const a = document.createElement('a');
        a.className = 'font-semibold hover:underline';
        a.href = appUrl('profile/visit?id=' + c.user_id);
        a.textContent = c.username;

        const span = document.createElement('span');
        span.className = 'text-gray-700 dark:text-gray-300';
        span.setAttribute('data-comment-body', '');
        span.textContent = c.body;

        li.append(a, span);

        if (c.created_at) {
            const t = document.createElement('span');
            t.className = 'text-xs text-gray-400';
            t.textContent = c.created_at;
            li.append(t);
        }
        if (currentUserId && Number(c.user_id) === currentUserId) {
            const actions = document.createElement('span');
            actions.className = 'ml-1 hidden gap-2 group-hover:inline-flex';
            actions.innerHTML =
                '<button type="button" class="text-xs text-gray-400 hover:text-indigo-500" data-comment-edit>Bearbeiten</button>' +
                '<button type="button" class="text-xs text-gray-400 hover:text-rose-500" data-comment-delete>Löschen</button>';
            li.append(actions);
        }
        return li;
    }

    // Add comment.
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

    // Expand / paginate comments.
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
                if (nextPage === 1) list.innerHTML = '';
                body.data.comments.forEach((c) => list.appendChild(renderComment(c)));
                section.dataset.page = String(nextPage);
                if (body.data.hasMore) toggle.textContent = 'Weitere Kommentare laden';
                else toggle.remove();
            })
            .catch(() => (toggle.disabled = false));
    });

    // Edit comment (inline).
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-comment-edit]');
        if (!btn) return;
        const li = btn.closest('[data-comment-id]');
        if (li.dataset.editing) return;
        li.dataset.editing = '1';
        const bodyEl = li.querySelector('[data-comment-body]');
        const original = bodyEl.textContent;
        bodyEl.style.display = 'none';

        const wrap = document.createElement('span');
        wrap.className = 'inline-flex items-center gap-1';
        const input = document.createElement('input');
        input.type = 'text';
        input.value = original;
        input.maxLength = 1000;
        input.className = 'input !py-1 !px-2 text-sm';
        const save = document.createElement('button');
        save.type = 'button';
        save.className = 'text-xs font-medium text-indigo-500';
        save.textContent = 'OK';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'text-xs text-gray-400';
        cancel.textContent = 'Abbrechen';
        wrap.append(input, save, cancel);
        bodyEl.after(wrap);
        input.focus();

        const cleanup = () => {
            wrap.remove();
            bodyEl.style.display = '';
            delete li.dataset.editing;
        };
        cancel.addEventListener('click', cleanup);
        save.addEventListener('click', () => {
            const val = input.value.trim();
            if (!val) return;
            const fd = new FormData();
            fd.append('_csrf_token', csrfToken);
            fd.append('body', val);
            postForm(appUrl('posts/comment/update?id=' + li.dataset.commentId), fd)
                .then(({ ok, body }) => {
                    if (ok && body.success) bodyEl.textContent = body.data.body;
                    cleanup();
                })
                .catch(cleanup);
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                save.click();
            }
            if (e.key === 'Escape') cleanup();
        });
    });

    // Delete comment.
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-comment-delete]');
        if (!btn) return;
        if (!window.confirm('Kommentar löschen?')) return;
        const li = btn.closest('[data-comment-id]');
        const section = li.closest('[data-comments]');
        postJson(appUrl('posts/comment/delete?id=' + li.dataset.commentId)).then(({ ok, body }) => {
            if (!ok || !body.success) return;
            li.remove();
            const countEl = section.querySelector('[data-comments-count]');
            if (countEl) countEl.textContent = body.data.commentCount;
        });
    });

    /* ---------- Share post via DM ---------- */
    const shareModal = document.querySelector('[data-share-modal]');
    if (shareModal) {
        const titleEl = shareModal.querySelector('[data-share-post-title]');
        const searchEl = shareModal.querySelector('[data-share-search]');
        const resultsEl = shareModal.querySelector('[data-share-results]');
        const recipientEl = shareModal.querySelector('[data-share-recipient]');
        const selectedEl = shareModal.querySelector('[data-share-selected]');
        const selectedName = shareModal.querySelector('[data-share-selected-name]');
        const submitBtn = shareModal.querySelector('[data-share-submit]');
        const errorEl = shareModal.querySelector('[data-share-error]');
        const form = shareModal.querySelector('[data-share-form]');
        let postId = null;
        let timer = null;

        const reset = () => {
            searchEl.value = '';
            resultsEl.innerHTML = '';
            recipientEl.value = '';
            selectedEl.classList.add('hidden');
            selectedEl.classList.remove('flex');
            submitBtn.disabled = true;
            errorEl.classList.add('hidden');
            shareModal.querySelector('[name="body"]').value = '';
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-share-post]');
            if (!trigger) return;
            postId = trigger.dataset.postId;
            titleEl.textContent = trigger.dataset.postTitle || '';
            reset();
            openModal('[data-share-modal]');
            searchEl.focus();
        });

        searchEl.addEventListener('input', () => {
            const q = searchEl.value.trim();
            clearTimeout(timer);
            if (!q) {
                resultsEl.innerHTML = '';
                return;
            }
            timer = setTimeout(() => {
                getJson(appUrl('search?q=' + encodeURIComponent(q))).then(({ ok, body }) => {
                    if (!ok || !body.success) return;
                    resultsEl.innerHTML = '';
                    body.data.users.forEach((u) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-800';
                        const img = document.createElement('img');
                        img.src = appUrl('avatar?id=' + u.id);
                        img.className = 'h-6 w-6 rounded-full object-cover';
                        const span = document.createElement('span');
                        span.textContent = u.username;
                        btn.append(img, span);
                        btn.addEventListener('click', () => {
                            recipientEl.value = u.id;
                            selectedName.textContent = u.username;
                            selectedEl.classList.remove('hidden');
                            selectedEl.classList.add('flex');
                            resultsEl.innerHTML = '';
                            searchEl.value = '';
                            submitBtn.disabled = false;
                        });
                        resultsEl.appendChild(btn);
                    });
                });
            }, 200);
        });

        shareModal.querySelector('[data-share-clear]')?.addEventListener('click', () => {
            recipientEl.value = '';
            selectedEl.classList.add('hidden');
            submitBtn.disabled = true;
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!recipientEl.value || !postId) return;
            submitBtn.disabled = true;
            postForm(appUrl('messages/share?id=' + postId), new FormData(form))
                .then(({ ok, body }) => {
                    if (ok && body.success) {
                        window.location.href = body.data.redirect;
                    } else {
                        errorEl.textContent = (body && body.message) || 'Senden fehlgeschlagen.';
                        errorEl.classList.remove('hidden');
                        submitBtn.disabled = false;
                    }
                })
                .catch(() => {
                    submitBtn.disabled = false;
                });
        });
    }

    /* ---------- Nav search (users + posts) ---------- */
    const searchInput = document.getElementById('nav-search');
    if (searchInput) {
        const wrap = searchInput.parentElement;
        wrap.classList.add('relative');
        const dropdown = document.createElement('div');
        dropdown.className =
            'absolute z-40 mt-1 hidden w-full max-w-sm overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900';
        wrap.appendChild(dropdown);
        let timer = null;

        function groupHeading(text) {
            const h = document.createElement('p');
            h.className = 'bg-gray-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:bg-gray-800/60';
            h.textContent = text;
            return h;
        }

        function render(users, posts) {
            dropdown.textContent = '';
            if (!users.length && !posts.length) {
                const empty = document.createElement('p');
                empty.className = 'px-3 py-2 text-sm text-gray-500 dark:text-gray-400';
                empty.textContent = 'Keine Ergebnisse';
                dropdown.appendChild(empty);
                dropdown.classList.remove('hidden');
                return;
            }
            if (users.length) {
                dropdown.appendChild(groupHeading('Benutzer'));
                users.forEach((u) => {
                    const a = document.createElement('a');
                    a.href = appUrl('profile/visit?id=' + u.id);
                    a.className = 'flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800';
                    a.innerHTML = '<img src="' + appUrl('avatar?id=' + u.id) + '" class="h-6 w-6 rounded-full object-cover" alt="">';
                    const span = document.createElement('span');
                    span.className = 'font-medium';
                    span.textContent = u.username;
                    a.appendChild(span);
                    dropdown.appendChild(a);
                });
            }
            if (posts.length) {
                dropdown.appendChild(groupHeading('Beiträge'));
                posts.forEach((p) => {
                    const a = document.createElement('a');
                    a.href = appUrl('post?id=' + p.id);
                    a.className = 'flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800';
                    if (p.imageId) {
                        const img = document.createElement('img');
                        img.src = appUrl('posts/image?id=' + p.imageId);
                        img.className = 'h-8 w-8 rounded object-cover';
                        a.appendChild(img);
                    }
                    const span = document.createElement('span');
                    span.className = 'min-w-0';
                    const title = document.createElement('span');
                    title.className = 'block truncate font-medium';
                    title.textContent = p.title;
                    const by = document.createElement('span');
                    by.className = 'block truncate text-xs text-gray-400';
                    by.textContent = 'von ' + p.username;
                    span.append(title, by);
                    a.appendChild(span);
                    dropdown.appendChild(a);
                });
            }
            dropdown.classList.remove('hidden');
        }

        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim();
            clearTimeout(timer);
            if (!q) {
                dropdown.classList.add('hidden');
                return;
            }
            timer = setTimeout(() => {
                getJson(appUrl('search?q=' + encodeURIComponent(q)))
                    .then(({ ok, body }) => {
                        if (ok && body.success) render(body.data.users, body.data.posts);
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

    /* ---------- Comment input reveal (Instagram-style) ---------- */
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-comment-open]');
        if (!btn) return;
        const box = btn.closest('article')?.querySelector('[data-comment-box]');
        if (!box) return;
        const hidden = box.classList.toggle('hidden');
        box.classList.toggle('flex', !hidden);
        if (!hidden) box.querySelector('[data-comment-input]')?.focus();
    });

    /* ---------- External-link confirmation ("leave Instakilo?") ---------- */
    const linkModal = document.querySelector('[data-link-modal]');
    if (linkModal) {
        const urlEl = linkModal.querySelector('[data-link-url]');
        const goEl = linkModal.querySelector('[data-link-go]');
        const closeLink = () => {
            linkModal.classList.add('hidden');
            linkModal.classList.remove('flex');
        };
        document.addEventListener('click', function (event) {
            const a = event.target.closest('a[href]');
            if (!a || linkModal.contains(a)) return;
            const href = a.getAttribute('href') || '';
            if (!/^https?:\/\//i.test(href)) return; // internal/relative → allow
            try {
                if (new URL(href, location.href).origin === location.origin) return;
            } catch (e) {
                return;
            }
            event.preventDefault();
            urlEl.textContent = href;
            goEl.href = href;
            linkModal.classList.remove('hidden');
            linkModal.classList.add('flex');
        });
        linkModal.querySelector('[data-link-cancel]').addEventListener('click', closeLink);
        goEl.addEventListener('click', closeLink);
        linkModal.addEventListener('click', (e) => {
            if (e.target === linkModal) closeLink();
        });
    }

    /* ---------- Live counters: reconcile feed cards with backend truth ---------- */
    function feedPosts() {
        return Array.from(document.querySelectorAll('article[data-post-id]'));
    }
    if (feedPosts().length) {
        const pollStats = () => {
            const ids = [...new Set(feedPosts().map((a) => a.dataset.postId))];
            if (!ids.length) return;
            getJson(appUrl('posts/stats?ids=' + ids.join(',')))
                .then(({ ok, body }) => {
                    if (!ok || !body.success) return;
                    const map = {};
                    body.data.stats.forEach((s) => (map[s.id] = s));
                    feedPosts().forEach((a) => {
                        const s = map[a.dataset.postId];
                        if (!s) return;
                        const lc = a.querySelector('[data-like-count]');
                        if (lc) lc.textContent = s.likes;
                        const cc = a.querySelector('[data-comments-count]');
                        if (cc) cc.textContent = s.comments;
                    });
                })
                .catch(() => {});
        };
        setInterval(pollStats, 15000);
    }

    /* ---------- Live unread badges (DMs + notifications) ---------- */
    function badgePoller(badge, endpoint, interval) {
        if (!badge || !currentUserId) return;
        const poll = () => {
            getJson(appUrl(endpoint))
                .then(({ ok, body }) => {
                    if (!ok || !body.success) return;
                    const n = body.data.count;
                    if (n > 0) {
                        badge.textContent = n > 9 ? '9+' : n;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                })
                .catch(() => {});
        };
        poll(); // refresh immediately on load
        setInterval(poll, interval);
    }
    badgePoller(document.querySelector('[data-dm-badge]'), 'messages/unread', 20000);
    // Skip the global notification poll on the notifications page itself — its
    // own poller (notifications.js) owns the badge there to avoid double-fetching.
    if (!document.querySelector('[data-notifications]')) {
        badgePoller(document.querySelector('[data-notif-badge]'), 'notifications/unread', 15000);
    }

    // Let notifications.js push live counts into the nav badge.
    window.InstakiloBadge = function (selector, n) {
        const badge = document.querySelector(selector);
        if (!badge) return;
        if (n > 0) {
            badge.textContent = n > 9 ? '9+' : n;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    };
})();
