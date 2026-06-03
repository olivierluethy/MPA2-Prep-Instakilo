/**
 * DM thread client: real-time polling (new messages, edits/deletes, reactions),
 * typing, multi-file composer with preview, replies, inline edit, delete,
 * reactions, and filters (content-type, Levenshtein fuzzy, epoch time-range).
 */
(function () {
    'use strict';

    const root = document.querySelector('[data-dm-thread]');
    if (!root) return;

    const withId = root.dataset.with;
    const list = root.querySelector('[data-message-list]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
    const appUrl = (p) => basePath + '/' + String(p).replace(/^\/+/, '');
    let lastId = parseInt(root.dataset.lastid || '0', 10);
    let rev = parseInt(root.dataset.rev || '0', 10);

    const jsonHeaders = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
    const getJson = (u) => fetch(u, { headers: jsonHeaders }).then((r) => r.json().then((b) => ({ ok: r.ok, body: b })));
    const postForm = (u, fd) =>
        fetch(u, { method: 'POST', headers: { ...jsonHeaders, 'X-CSRF-Token': csrfToken }, body: fd }).then((r) => r.json().then((b) => ({ ok: r.ok, body: b })));
    function post(u, fields) {
        const fd = new FormData();
        fd.append('_csrf_token', csrfToken);
        Object.entries(fields || {}).forEach(([k, v]) => fd.append(k, v));
        return postForm(u, fd);
    }

    const messageEls = () => Array.from(list.querySelectorAll('[data-message]'));
    const byId = (id) => list.querySelector('[data-message][data-id="' + id + '"]');
    const nearBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 80;
    const scrollBottom = () => (list.scrollTop = list.scrollHeight);
    scrollBottom();

    /* ===================== Reactions ===================== */
    function renderReactions(map) {
        messageEls().forEach((el) => {
            const bar = el.querySelector('[data-reactions]');
            if (!bar) return;
            const list2 = map[el.dataset.id] || [];
            bar.innerHTML = '';
            list2.forEach((r) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.dataset.reactEmoji = r.emoji;
                b.dataset.reactMsg = el.dataset.id;
                b.className =
                    'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs ' +
                    (r.mine
                        ? 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-400 dark:bg-indigo-900/50 dark:text-indigo-200'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300');
                b.textContent = r.emoji + ' ' + r.count;
                bar.appendChild(b);
            });
        });
    }
    let reactionsMap = {};
    try {
        reactionsMap = JSON.parse(root.querySelector('[data-initial-reactions]')?.textContent || '{}');
    } catch (e) {
        reactionsMap = {};
    }
    renderReactions(reactionsMap);

    // Toggle an existing reaction badge.
    list.addEventListener('click', (e) => {
        const b = e.target.closest('[data-react-emoji]');
        if (!b) return;
        post(appUrl('messages/react?id=' + b.dataset.reactMsg), { emoji: b.dataset.reactEmoji }).then(() => poll());
    });

    // Quick-react popover.
    const QUICK = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
    let quickPop = null;
    function closeQuick() {
        quickPop?.remove();
        quickPop = null;
    }
    list.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-msg-react]');
        if (!trigger) return;
        closeQuick();
        const msgId = trigger.closest('[data-message]').dataset.id;
        quickPop = document.createElement('div');
        quickPop.className = 'absolute z-50 flex gap-1 rounded-full border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900';
        QUICK.forEach((emoji) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'rounded-full px-1 text-lg hover:bg-gray-100 dark:hover:bg-gray-800';
            b.textContent = emoji;
            b.addEventListener('click', () => {
                post(appUrl('messages/react?id=' + msgId), { emoji }).then(() => poll());
                closeQuick();
            });
            quickPop.appendChild(b);
        });
        document.body.appendChild(quickPop);
        const r = trigger.getBoundingClientRect();
        quickPop.style.top = window.scrollY + r.top - quickPop.offsetHeight - 4 + 'px';
        quickPop.style.left = window.scrollX + r.left + 'px';
    });
    document.addEventListener('click', (e) => {
        if (quickPop && !quickPop.contains(e.target) && !e.target.closest('[data-msg-react]')) closeQuick();
    });

    /* ===================== Reply ===================== */
    const replyBar = root.querySelector('[data-reply-bar]');
    const replyInput = root.querySelector('[data-reply-input]');
    function setReply(id, name, snippet) {
        replyInput.value = id;
        replyBar.querySelector('[data-reply-name]').textContent = name;
        replyBar.querySelector('[data-reply-snippet]').textContent = snippet;
        replyBar.classList.remove('hidden');
        replyBar.classList.add('flex');
        root.querySelector('[name="body"]').focus();
    }
    function clearReply() {
        replyInput.value = '';
        replyBar.classList.add('hidden');
        replyBar.classList.remove('flex');
    }
    root.querySelector('[data-reply-cancel]').addEventListener('click', clearReply);
    list.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-msg-reply]');
        if (!trigger) return;
        const el = trigger.closest('[data-message]');
        const name = el.dataset.mine === '1' ? 'dir' : (root.querySelector('header .font-semibold')?.textContent || '');
        const snippet = (el.dataset.text || '').slice(0, 80) || '[Anhang]';
        setReply(el.dataset.id, name, snippet);
    });
    // Jump to a referenced message.
    list.addEventListener('click', (e) => {
        const jump = e.target.closest('[data-jump]');
        if (!jump) return;
        const target = byId(jump.dataset.jump);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('ring-2', 'ring-indigo-400', 'rounded-2xl');
            setTimeout(() => target.classList.remove('ring-2', 'ring-indigo-400', 'rounded-2xl'), 1200);
        }
    });

    /* ===================== Edit / Delete ===================== */
    list.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-msg-edit]');
        if (!trigger) return;
        const el = trigger.closest('[data-message]');
        const bodyEl = el.querySelector('[data-body]');
        if (!bodyEl || el.dataset.editing) return;
        el.dataset.editing = '1';
        const original = bodyEl.textContent;
        bodyEl.style.display = 'none';
        const wrap = document.createElement('div');
        wrap.className = 'mt-1 flex items-center gap-1';
        const input = document.createElement('input');
        input.type = 'text';
        input.value = original;
        input.maxLength = 2000;
        input.className = 'input !py-1 !px-2 text-sm text-gray-900';
        const save = document.createElement('button');
        save.type = 'button';
        save.className = 'text-xs font-medium';
        save.textContent = 'OK';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'text-xs opacity-70';
        cancel.textContent = 'Abbr.';
        wrap.append(input, save, cancel);
        bodyEl.after(wrap);
        input.focus();
        const cleanup = () => {
            wrap.remove();
            bodyEl.style.display = '';
            delete el.dataset.editing;
        };
        cancel.addEventListener('click', cleanup);
        save.addEventListener('click', () => {
            const val = input.value.trim();
            if (!val) return;
            post(appUrl('messages/edit?id=' + el.dataset.id), { body: val }).then(({ ok, body }) => {
                if (ok && body.success) bodyEl.textContent = body.data.body;
                cleanup();
            });
        });
        input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                save.click();
            }
            if (ev.key === 'Escape') cleanup();
        });
    });
    list.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-msg-delete]');
        if (!trigger) return;
        if (!window.confirm('Nachricht löschen?')) return;
        const el = trigger.closest('[data-message]');
        post(appUrl('messages/delete?id=' + el.dataset.id), {}).then(({ ok, body }) => {
            if (ok && body.success) poll();
        });
    });

    /* ===================== Filters ===================== */
    const activeTypes = new Set();
    let fuzzyQuery = '';
    let rangeStartTs = null;
    let rangeEndTs = null;
    const emptyMsg = root.querySelector('[data-filter-empty]');

    function lev(a, b) {
        const m = a.length, n = b.length;
        if (!m) return n;
        if (!n) return m;
        const dp = Array.from({ length: n + 1 }, (_, i) => i);
        for (let i = 1; i <= m; i++) {
            let prev = dp[0];
            dp[0] = i;
            for (let j = 1; j <= n; j++) {
                const tmp = dp[j];
                dp[j] = Math.min(dp[j] + 1, dp[j - 1] + 1, prev + (a[i - 1] === b[j - 1] ? 0 : 1));
                prev = tmp;
            }
        }
        return dp[n];
    }
    function fuzzyMatch(text, q) {
        if (!q) return true;
        if (text.includes(q)) return true;
        const threshold = Math.max(1, Math.floor(q.length / 3));
        return text.split(/\s+/).some((tok) => {
            if (!tok) return false;
            if (tok.includes(q)) return true;
            const w = tok.length > q.length ? tok.slice(0, q.length + threshold) : tok;
            return lev(w, q) <= threshold || lev(tok, q) <= threshold;
        });
    }
    function applyFilters() {
        let visible = 0;
        messageEls().forEach((el) => {
            const kinds = (el.dataset.kinds || '').split(',');
            const ts = parseInt(el.dataset.ts, 10);
            const hasLink = el.dataset.hasLink === '1';
            let ok = true;
            if (activeTypes.size) {
                ok = [...activeTypes].some((t) => (t === 'link' ? kinds.includes('link') || hasLink : kinds.includes(t)));
            }
            if (ok && fuzzyQuery) ok = fuzzyMatch(el.dataset.text || '', fuzzyQuery);
            if (ok && rangeStartTs !== null) ok = ts >= rangeStartTs && ts <= rangeEndTs;
            el.classList.toggle('hidden', !ok);
            if (ok) visible++;
        });
        if (emptyMsg) emptyMsg.classList.toggle('hidden', visible > 0 || messageEls().length === 0);
    }
    const typeWrap = root.querySelector('[data-type-filters]');
    function refreshTypeButtons() {
        typeWrap.querySelectorAll('[data-type]').forEach((b) => {
            const t = b.dataset.type;
            const on = t === 'all' ? activeTypes.size === 0 : activeTypes.has(t);
            b.classList.toggle('bg-indigo-600', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('bg-gray-100', !on);
            b.classList.toggle('text-gray-600', !on);
            b.classList.toggle('dark:bg-gray-800', !on);
            b.classList.toggle('dark:text-gray-300', !on);
        });
    }
    typeWrap.addEventListener('click', (e) => {
        const b = e.target.closest('[data-type]');
        if (!b) return;
        if (b.dataset.type === 'all') activeTypes.clear();
        else activeTypes.has(b.dataset.type) ? activeTypes.delete(b.dataset.type) : activeTypes.add(b.dataset.type);
        refreshTypeButtons();
        applyFilters();
    });
    root.querySelector('[data-fuzzy]').addEventListener('input', (e) => {
        fuzzyQuery = e.target.value.trim().toLowerCase();
        applyFilters();
    });

    /* ---------- Timeline: epoch buckets (works at any granularity) ---------- */
    const BUCKETS = 40;
    const filterPanel = root.querySelector('[data-filter-panel]');
    const timeline = root.querySelector('[data-timeline]');
    const histo = root.querySelector('[data-histogram]');
    const startRange = root.querySelector('[data-range-start]');
    const endRange = root.querySelector('[data-range-end]');
    const startLabel = root.querySelector('[data-range-start-label]');
    const endLabel = root.querySelector('[data-range-end-label]');
    let minTs = 0, maxTs = 0, bucketSize = 1, startIdx = 0, endIdx = BUCKETS;

    function fmt(ts) {
        const d = new Date(ts * 1000);
        const p = (n) => String(n).padStart(2, '0');
        return p(d.getDate()) + '.' + p(d.getMonth() + 1) + '. ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
    }
    function buildTimeline() {
        const ts = messageEls().map((el) => parseInt(el.dataset.ts, 10)).filter((n) => n > 0);
        if (!ts.length) {
            timeline.classList.add('hidden');
            return;
        }
        timeline.classList.remove('hidden');
        minTs = Math.min(...ts);
        maxTs = Math.max(...ts);
        if (maxTs <= minTs) maxTs = minTs + 1;
        bucketSize = (maxTs - minTs) / BUCKETS;
        startRange.min = endRange.min = '0';
        startRange.max = endRange.max = String(BUCKETS);
        startRange.value = String(startIdx);
        endRange.value = String(endIdx);
        updateRange();
    }
    function bucketAt(i) {
        return minTs + i * bucketSize;
    }
    function updateRange() {
        rangeStartTs = Math.floor(bucketAt(startIdx));
        rangeEndTs = Math.ceil(bucketAt(endIdx));
        startLabel.textContent = fmt(rangeStartTs);
        endLabel.textContent = fmt(rangeEndTs);
        // histogram
        const counts = new Array(BUCKETS).fill(0);
        messageEls().forEach((el) => {
            const t = parseInt(el.dataset.ts, 10);
            let idx = Math.floor((t - minTs) / bucketSize);
            if (idx < 0) idx = 0;
            if (idx >= BUCKETS) idx = BUCKETS - 1;
            counts[idx]++;
        });
        const max = Math.max(...counts, 1);
        histo.innerHTML = '';
        counts.forEach((c, i) => {
            const bar = document.createElement('div');
            const inRange = i >= startIdx && i < endIdx;
            bar.className = 'flex-1 rounded-t ' + (inRange ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-700');
            bar.style.height = Math.max(6, Math.round((c / max) * 100)) + '%';
            bar.title = fmt(Math.floor(bucketAt(i))) + ' – ' + fmt(Math.floor(bucketAt(i + 1))) + ': ' + c + ' Nachricht(en)';
            histo.appendChild(bar);
        });
        applyFilters();
    }
    function onSlider() {
        let s = parseInt(startRange.value, 10);
        let e = parseInt(endRange.value, 10);
        if (s > e) [s, e] = [e, s];
        startIdx = s;
        endIdx = Math.max(e, s + 1);
        updateRange();
    }
    startRange.addEventListener('input', onSlider);
    endRange.addEventListener('input', onSlider);
    root.querySelector('[data-filter-toggle]').addEventListener('click', () => {
        filterPanel.classList.toggle('hidden');
        if (!filterPanel.classList.contains('hidden')) buildTimeline();
    });

    /* ===================== Polling ===================== */
    const typingEl = root.querySelector('[data-typing]');
    async function poll() {
        try {
            const { ok, body } = await getJson(appUrl('messages/poll?with=' + withId + '&after=' + lastId + '&rev=' + rev));
            if (!ok || !body.success) return;
            const data = body.data;
            const stick = nearBottom();
            // New messages
            data.messages.forEach((m) => {
                const tmp = document.createElement('div');
                tmp.innerHTML = m.html;
                if (tmp.firstElementChild) list.appendChild(tmp.firstElementChild);
            });
            // Revisions (edits/deletes) → replace existing bubbles
            (data.revisions || []).forEach((r) => {
                const existing = byId(r.id);
                if (existing) {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = r.html;
                    if (tmp.firstElementChild) existing.replaceWith(tmp.firstElementChild);
                }
            });
            lastId = data.lastId;
            rev = data.rev;
            reactionsMap = data.reactions || {};
            renderReactions(reactionsMap);
            if (data.messages.length || (data.revisions || []).length) {
                root.querySelector('[data-thread-empty]')?.remove();
                applyFilters();
                if (!filterPanel.classList.contains('hidden')) buildTimeline();
                if (stick && data.messages.length) scrollBottom();
            }
            typingEl.classList.toggle('hidden', !data.typing);
        } catch (e) {
            /* retry next tick */
        }
    }
    setInterval(poll, 3000);

    /* ===================== Composer (multi-file) ===================== */
    const composer = root.querySelector('[data-dm-composer]');
    const bodyInput = composer.querySelector('[name="body"]');
    const mediaInput = composer.querySelector('[data-media-input]');
    const previews = root.querySelector('[data-attach-previews]');
    const errEl = root.querySelector('[data-dm-error]');
    let attachments = [];

    composer.querySelector('[data-attach]').addEventListener('click', () => mediaInput.click());
    mediaInput.addEventListener('change', () => {
        for (const f of mediaInput.files) attachments.push(f);
        mediaInput.value = '';
        renderAttachments();
    });
    function renderAttachments() {
        previews.innerHTML = '';
        previews.classList.toggle('hidden', attachments.length === 0);
        previews.classList.toggle('flex', attachments.length > 0);
        attachments.forEach((file, i) => {
            const li = document.createElement('li');
            li.className = 'relative h-14 w-14 overflow-hidden rounded-md ring-1 ring-gray-300 dark:ring-gray-700';
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'h-full w-full object-cover';
                const rd = new FileReader();
                rd.onload = (e) => (img.src = e.target.result);
                rd.readAsDataURL(file);
                li.appendChild(img);
            } else {
                const s = document.createElement('span');
                s.className = 'flex h-full w-full items-center justify-center p-1 text-center text-[9px] text-gray-500';
                s.textContent = file.name;
                li.appendChild(s);
            }
            const x = document.createElement('button');
            x.type = 'button';
            x.className = 'absolute right-0 top-0 bg-black/60 px-1 text-xs text-white';
            x.textContent = '✕';
            x.addEventListener('click', () => {
                attachments.splice(i, 1);
                renderAttachments();
            });
            li.appendChild(x);
            previews.appendChild(li);
        });
    }

    composer.addEventListener('submit', async (e) => {
        e.preventDefault();
        errEl.classList.add('hidden');
        if (!bodyInput.value.trim() && attachments.length === 0) return;
        const fd = new FormData();
        fd.append('_csrf_token', csrfToken);
        fd.append('recipient', composer.querySelector('[name="recipient"]').value);
        fd.append('reply_to', replyInput.value);
        fd.append('body', bodyInput.value);
        attachments.forEach((f) => fd.append('media[]', f, f.name));
        const { ok, body } = await postForm(appUrl('messages/send'), fd);
        if (ok && body.success) {
            bodyInput.value = '';
            attachments = [];
            renderAttachments();
            clearReply();
            poll();
        } else {
            errEl.textContent = (body && body.message) || 'Senden fehlgeschlagen.';
            errEl.classList.remove('hidden');
        }
    });

    // Typing signal.
    let lastTyping = 0;
    bodyInput.addEventListener('input', () => {
        const now = Date.now();
        if (now - lastTyping < 2000) return;
        lastTyping = now;
        fetch(appUrl('messages/typing?with=' + withId), { method: 'POST', headers: { ...jsonHeaders, 'X-CSRF-Token': csrfToken } }).catch(() => {});
    });
})();
