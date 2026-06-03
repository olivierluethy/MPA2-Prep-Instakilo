/**
 * DM thread client: real-time polling, typing indicator, media composer, and
 * client-side filters (content-type, Levenshtein fuzzy search, time-range
 * histogram). Activates only on the conversation page.
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

    const jsonHeaders = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
    const getJson = (u) => fetch(u, { headers: jsonHeaders }).then((r) => r.json().then((b) => ({ ok: r.ok, body: b })));
    const postForm = (u, fd) =>
        fetch(u, { method: 'POST', headers: { ...jsonHeaders, 'X-CSRF-Token': csrfToken }, body: fd }).then((r) =>
            r.json().then((b) => ({ ok: r.ok, body: b }))
        );

    const messageEls = () => Array.from(list.querySelectorAll('[data-message]'));
    const nearBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 80;
    const scrollBottom = () => (list.scrollTop = list.scrollHeight);
    scrollBottom();

    /* ===================== Filters ===================== */
    const activeTypes = new Set(); // empty => all
    let fuzzyQuery = '';
    let rangeStartTs = null;
    let rangeEndTs = null;
    const emptyMsg = root.querySelector('[data-filter-empty]');

    // Levenshtein edit distance.
    function lev(a, b) {
        const m = a.length;
        const n = b.length;
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
    // Approximate match: substring or any token within an edit-distance threshold.
    function fuzzyMatch(text, q) {
        if (!q) return true;
        if (text.includes(q)) return true;
        const threshold = Math.max(1, Math.floor(q.length / 3));
        return text.split(/\s+/).some((tok) => {
            if (!tok) return false;
            if (tok.includes(q)) return true;
            const window = tok.length > q.length ? tok.slice(0, q.length + threshold) : tok;
            return lev(window, q) <= threshold || lev(tok, q) <= threshold;
        });
    }

    function applyFilters() {
        let visible = 0;
        messageEls().forEach((el) => {
            const kind = el.dataset.kind;
            const ts = parseInt(el.dataset.ts, 10);
            const hasLink = el.dataset.hasLink === '1';
            let ok = true;
            if (activeTypes.size) {
                ok = [...activeTypes].some((t) => (t === 'link' ? kind === 'link' || hasLink : kind === t));
            }
            if (ok && fuzzyQuery) ok = fuzzyMatch(el.dataset.text || '', fuzzyQuery);
            if (ok && rangeStartTs !== null) ok = ts >= rangeStartTs;
            if (ok && rangeEndTs !== null) ok = ts <= rangeEndTs;
            el.classList.toggle('hidden', !ok);
            if (ok) visible++;
        });
        if (emptyMsg) emptyMsg.classList.toggle('hidden', visible > 0 || messageEls().length === 0);
    }

    // Content-type buttons (combinable; "Alle" clears).
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
        const t = b.dataset.type;
        if (t === 'all') activeTypes.clear();
        else activeTypes.has(t) ? activeTypes.delete(t) : activeTypes.add(t);
        refreshTypeButtons();
        applyFilters();
    });

    root.querySelector('[data-fuzzy]').addEventListener('input', (e) => {
        fuzzyQuery = e.target.value.trim().toLowerCase();
        applyFilters();
    });

    /* ===================== Timeline / histogram ===================== */
    const filterPanel = root.querySelector('[data-filter-panel]');
    const timeline = root.querySelector('[data-timeline]');
    const histo = root.querySelector('[data-histogram]');
    const startRange = root.querySelector('[data-range-start]');
    const endRange = root.querySelector('[data-range-end]');
    const startLabel = root.querySelector('[data-range-start-label]');
    const endLabel = root.querySelector('[data-range-end-label]');
    let days = [];
    let startIdx = null;
    let endIdx = null;

    function buildTimeline() {
        const map = new Map();
        messageEls().forEach((el) => {
            const d = new Date(parseInt(el.dataset.ts, 10) * 1000);
            const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            if (!map.has(key)) map.set(key, { key, count: 0, date: d });
            map.get(key).count++;
        });
        days = [...map.values()]
            .sort((a, b) => a.key.localeCompare(b.key))
            .map((d) => {
                const s = new Date(d.date); s.setHours(0, 0, 0, 0);
                const e = new Date(d.date); e.setHours(23, 59, 59, 999);
                return { label: d.key, startTs: Math.floor(s / 1000), endTs: Math.floor(e / 1000), count: d.count };
            });

        if (days.length === 0) {
            timeline.classList.add('hidden');
            return;
        }
        timeline.classList.remove('hidden');
        startRange.max = endRange.max = String(days.length - 1);
        if (startIdx === null || startIdx > days.length - 1) startIdx = 0;
        if (endIdx === null || endIdx > days.length - 1) endIdx = days.length - 1;
        startRange.value = String(startIdx);
        endRange.value = String(endIdx);
        rangeStartTs = days[startIdx].startTs;
        rangeEndTs = days[endIdx].endTs;
        startLabel.textContent = days[startIdx].label;
        endLabel.textContent = days[endIdx].label;
        renderHisto();
    }

    function renderHisto() {
        const max = Math.max(...days.map((d) => d.count), 1);
        histo.innerHTML = '';
        days.forEach((d, i) => {
            const bar = document.createElement('div');
            const inRange = i >= startIdx && i <= endIdx;
            bar.className = 'flex-1 rounded-t ' + (inRange ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-700');
            bar.style.height = Math.max(8, Math.round((d.count / max) * 100)) + '%';
            bar.title = d.label + ': ' + d.count + ' Nachricht(en)'; // hover shows date + count
            histo.appendChild(bar);
        });
    }

    function onSlider() {
        let s = parseInt(startRange.value, 10);
        let e = parseInt(endRange.value, 10);
        if (s > e) [s, e] = [e, s];
        startIdx = s;
        endIdx = e;
        rangeStartTs = days[s].startTs;
        rangeEndTs = days[e].endTs;
        startLabel.textContent = days[s].label;
        endLabel.textContent = days[e].label;
        renderHisto();
        applyFilters();
    }
    startRange.addEventListener('input', onSlider);
    endRange.addEventListener('input', onSlider);

    root.querySelector('[data-filter-toggle]').addEventListener('click', () => {
        filterPanel.classList.toggle('hidden');
        if (!filterPanel.classList.contains('hidden')) buildTimeline();
    });

    /* ===================== Real-time polling ===================== */
    const typingEl = root.querySelector('[data-typing]');
    async function poll() {
        try {
            const { ok, body } = await getJson(appUrl('messages/poll?with=' + withId + '&after=' + lastId));
            if (!ok || !body.success) return;
            const data = body.data;
            if (data.messages.length) {
                const stick = nearBottom();
                data.messages.forEach((m) => {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = m.html;
                    if (tmp.firstElementChild) list.appendChild(tmp.firstElementChild);
                });
                lastId = data.lastId;
                root.querySelector('[data-thread-empty]')?.remove();
                applyFilters();
                if (!filterPanel.classList.contains('hidden')) buildTimeline();
                if (stick) scrollBottom();
            }
            typingEl.classList.toggle('hidden', !data.typing);
        } catch (e) {
            /* transient network error — next tick retries */
        }
    }
    setInterval(poll, 3000);

    /* ===================== Composer ===================== */
    const composer = root.querySelector('[data-dm-composer]');
    const bodyInput = composer.querySelector('[name="body"]');
    const mediaInput = composer.querySelector('[data-media-input]');
    const attachName = root.querySelector('[data-attach-name]');
    const errEl = root.querySelector('[data-dm-error]');

    composer.querySelector('[data-attach]').addEventListener('click', () => mediaInput.click());
    mediaInput.addEventListener('change', () => {
        if (mediaInput.files.length) {
            attachName.textContent = '📎 ' + mediaInput.files[0].name;
            attachName.classList.remove('hidden');
        } else {
            attachName.classList.add('hidden');
        }
    });

    composer.addEventListener('submit', async (e) => {
        e.preventDefault();
        errEl.classList.add('hidden');
        if (!bodyInput.value.trim() && !mediaInput.files.length) return;
        const { ok, body } = await postForm(appUrl('messages/send'), new FormData(composer));
        if (ok && body.success) {
            bodyInput.value = '';
            mediaInput.value = '';
            attachName.classList.add('hidden');
            poll();
        } else {
            errEl.textContent = (body && body.message) || 'Senden fehlgeschlagen.';
            errEl.classList.remove('hidden');
        }
    });

    // Typing signal (throttled to once per 2s).
    let lastTyping = 0;
    bodyInput.addEventListener('input', () => {
        const now = Date.now();
        if (now - lastTyping < 2000) return;
        lastTyping = now;
        fetch(appUrl('messages/typing?with=' + withId), {
            method: 'POST',
            headers: { ...jsonHeaders, 'X-CSRF-Token': csrfToken },
        }).catch(() => {});
    });
})();
