/**
 * Notification center client: real-time polling (same architecture as DMs),
 * mark single / all as read, follow-back & unfollow (with confirm) updating in
 * place, lazy "load more", and live relative-time refresh.
 */
(function () {
    'use strict';

    const root = document.querySelector('[data-notifications]');
    if (!root) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
    const appUrl = (p) => basePath + '/' + String(p).replace(/^\/+/, '');

    const jsonHeaders = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
    const getJson = (u) => fetch(u, { headers: jsonHeaders }).then((r) => r.json().then((b) => ({ ok: r.ok, body: b })));
    function post(u, fields, keepalive) {
        const fd = new FormData();
        fd.append('_csrf_token', csrfToken);
        Object.entries(fields || {}).forEach(([k, v]) => fd.append(k, v));
        return fetch(u, {
            method: 'POST',
            headers: { ...jsonHeaders, 'X-CSRF-Token': csrfToken },
            body: fd,
            keepalive: !!keepalive,
        }).then((r) => r.json().then((b) => ({ ok: r.ok, body: b })).catch(() => ({ ok: r.ok, body: {} })));
    }

    const list = root.querySelector('[data-notif-list]');
    const emptyEl = root.querySelector('[data-notif-empty]');
    const readAllBtn = root.querySelector('[data-notif-read-all]');
    const moreBtn = root.querySelector('[data-notif-more]');
    let sig = root.dataset.sig || '';
    let expanded = false; // user loaded older pages → pause live list replacement

    const setBadge = (n) => window.InstakiloBadge && window.InstakiloBadge('[data-notif-badge]', n);

    /* ---------------- relative time ---------------- */
    function timeAgo(sec) {
        const d = Math.max(0, Math.floor(Date.now() / 1000) - sec);
        if (d < 60) return 'vor ' + d + (d === 1 ? ' Sekunde' : ' Sekunden');
        if (d < 3600) {
            const m = Math.floor(d / 60);
            return 'vor ' + m + (m === 1 ? ' Minute' : ' Minuten');
        }
        if (d < 86400) {
            const h = Math.floor(d / 3600);
            return 'vor ' + h + (h === 1 ? ' Stunde' : ' Stunden');
        }
        const days = Math.floor(d / 86400);
        if (days === 1) return 'Gestern';
        if (days < 7) return 'vor ' + days + (days === 1 ? ' Tag' : ' Tagen');
        return null; // older → keep the server-rendered absolute date
    }
    function refreshTimes() {
        root.querySelectorAll('[data-ts]').forEach((el) => {
            const label = timeAgo(parseInt(el.dataset.ts, 10) || 0);
            if (label) el.textContent = label;
        });
    }

    /* ---------------- empty / list toggling ---------------- */
    function syncEmpty() {
        const has = list.children.length > 0;
        list.classList.toggle('hidden', !has);
        emptyEl?.classList.toggle('hidden', has);
        emptyEl?.classList.toggle('flex', !has);
    }

    /* ---------------- real-time poll ---------------- */
    function applyList(html) {
        list.innerHTML = html;
        refreshTimes();
        syncEmpty();
    }
    function poll() {
        getJson(appUrl('notifications/poll?sig=' + encodeURIComponent(sig)))
            .then(({ ok, body }) => {
                if (!ok || !body.success) return;
                const d = body.data;
                setBadge(d.count);
                if (!d.changed) return;
                sig = d.sig;
                readAllBtn?.classList.toggle('hidden', d.count === 0);
                if (!expanded && typeof d.html === 'string') {
                    applyList(d.html);
                    moreBtn?.classList.toggle('hidden', !d.hasMore);
                    moreBtn && (moreBtn.dataset.page = '1');
                }
            })
            .catch(() => {});
    }
    setInterval(poll, 5000);
    refreshTimes();
    setInterval(refreshTimes, 30000);

    /* ---------------- mark single read (on navigate) ---------------- */
    list.addEventListener('click', (e) => {
        const link = e.target.closest('[data-notif-link]');
        if (!link) return;
        const li = link.closest('[data-notif]');
        if (li && li.dataset.unread === '1') {
            // keepalive so the request survives the navigation that follows.
            post(appUrl('notifications/read?id=' + li.dataset.id), {}, true);
            li.dataset.unread = '0';
        }
        // default navigation proceeds
    });

    /* ---------------- mark all read ---------------- */
    readAllBtn?.addEventListener('click', () => {
        post(appUrl('notifications/read-all'), {}).then(({ ok, body }) => {
            if (!ok || !body.success) return;
            setBadge(0);
            readAllBtn.classList.add('hidden');
            sig = ''; // force the next poll to re-render authoritative (read) state
            list.querySelectorAll('[data-notif]').forEach((li) => {
                li.dataset.unread = '0';
                li.classList.remove('bg-indigo-50', 'dark:bg-indigo-950/40');
                li.querySelector('[aria-label="ungelesen"]')?.remove();
            });
        });
    });

    /* ---------------- load more (older) ---------------- */
    moreBtn?.addEventListener('click', () => {
        const next = parseInt(moreBtn.dataset.page || '1', 10) + 1;
        moreBtn.disabled = true;
        getJson(appUrl('notifications/more?page=' + next))
            .then(({ ok, body }) => {
                moreBtn.disabled = false;
                if (!ok || !body.success) return;
                if (body.data.html) {
                    list.insertAdjacentHTML('beforeend', body.data.html);
                    refreshTimes();
                    syncEmpty();
                    expanded = true; // keep appended history; pause live list swaps
                }
                moreBtn.dataset.page = String(next);
                moreBtn.classList.toggle('hidden', !body.data.hasMore);
            })
            .catch(() => (moreBtn.disabled = false));
    });

    /* ---------------- follow-back / unfollow ---------------- */
    let confirmPop = null;
    const closeConfirm = () => {
        confirmPop?.remove();
        confirmPop = null;
    };
    function setFollowState(btn, following) {
        btn.dataset.following = following ? '1' : '0';
        btn.querySelector('[data-follow-label]').textContent = following ? 'Folge ich' : 'Zurückfolgen';
        btn.classList.toggle('btn-primary', !following);
        btn.classList.toggle('btn-secondary', following);
    }
    function doFollow(btn, follow) {
        const id = btn.dataset.userId;
        post(appUrl((follow ? 'follow' : 'unfollow') + '?id=' + id), {}).then(({ ok, body }) => {
            if (!ok || !body.success) return;
            setFollowState(btn, !!body.data.following);
            // Reflect live counts anywhere they happen to be visible, and broadcast.
            document.querySelectorAll('[data-follower-count][data-user-id="' + id + '"]').forEach((el) => {
                el.textContent = body.data.followerCount;
            });
            window.dispatchEvent(new CustomEvent('instakilo:follow-changed', {
                detail: { userId: parseInt(id, 10), following: !!body.data.following, followerCount: body.data.followerCount },
            }));
        });
    }
    list.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-follow-action]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        closeConfirm();

        if (btn.dataset.following === '0') {
            doFollow(btn, true); // Follow Back → immediate
            return;
        }
        // Already following → confirm before unfollowing.
        confirmPop = document.createElement('div');
        confirmPop.className =
            'absolute z-50 w-56 rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-xl dark:border-gray-700 dark:bg-gray-900';
        const name = btn.dataset.username || 'diesem Nutzer';
        confirmPop.innerHTML =
            '<p class="mb-2"><span class="font-semibold">' + escapeHtml(name) + '</span> entfolgen?</p>' +
            '<div class="flex justify-end gap-2">' +
            '<button type="button" data-cancel class="btn-secondary !py-1 !px-2 text-xs">Abbrechen</button>' +
            '<button type="button" data-confirm class="btn-danger !py-1 !px-2 text-xs">Entfolgen</button>' +
            '</div>';
        document.body.appendChild(confirmPop);
        const r = btn.getBoundingClientRect();
        confirmPop.style.top = window.scrollY + r.bottom + 6 + 'px';
        confirmPop.style.left = window.scrollX + Math.max(8, r.right - confirmPop.offsetWidth) + 'px';

        confirmPop.querySelector('[data-cancel]').addEventListener('click', closeConfirm);
        confirmPop.querySelector('[data-confirm]').addEventListener('click', () => {
            doFollow(btn, false);
            closeConfirm();
        });
    });
    document.addEventListener('click', (e) => {
        if (confirmPop && !confirmPop.contains(e.target) && !e.target.closest('[data-follow-action]')) closeConfirm();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeConfirm();
    });

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
})();
