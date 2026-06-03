/**
 * Dependency-free emoji picker: categories, search, recently-used (localStorage),
 * click-to-insert at the caret of a target input.
 *
 * Trigger:  <button data-emoji-trigger data-emoji-target="#dm-body">
 */
(function () {
    'use strict';

    const CATS = [
        ['Smileys', '😀', [
            ['😀', 'grinning'], ['😃', 'smile'], ['😄', 'happy'], ['😁', 'grin'], ['😆', 'laugh'],
            ['😅', 'sweat smile'], ['🤣', 'rofl'], ['😂', 'joy tears'], ['🙂', 'slight smile'], ['🙃', 'upside down'],
            ['😉', 'wink'], ['😊', 'blush'], ['😇', 'angel'], ['😍', 'heart eyes'], ['🥰', 'love'],
            ['😘', 'kiss'], ['😎', 'cool sunglasses'], ['🤩', 'star struck'], ['🤔', 'thinking'], ['🤨', 'raised brow'],
            ['😐', 'neutral'], ['😴', 'sleep'], ['😢', 'cry sad'], ['😭', 'sob'], ['😤', 'triumph'],
            ['😠', 'angry'], ['😡', 'rage'], ['🤯', 'mind blown'], ['😳', 'flushed'], ['🥳', 'party'],
            ['😬', 'grimace'], ['🙄', 'eye roll'], ['😏', 'smirk'], ['😶', 'no mouth'], ['🤐', 'zipper'],
        ]],
        ['Gesten', '👍', [
            ['👍', 'thumbs up like'], ['👎', 'thumbs down dislike'], ['👏', 'clap'], ['🙌', 'raised hands'], ['👋', 'wave hi'],
            ['🤝', 'handshake'], ['🙏', 'pray thanks'], ['💪', 'muscle strong'], ['✌️', 'peace victory'], ['🤞', 'fingers crossed'],
            ['👌', 'ok'], ['🤙', 'call me'], ['👈', 'left'], ['👉', 'right'], ['👆', 'up'],
            ['👇', 'down'], ['✊', 'fist'], ['👊', 'punch'], ['🫶', 'heart hands'], ['🤲', 'open hands'],
        ]],
        ['Tiere', '🐶', [
            ['🐶', 'dog'], ['🐱', 'cat'], ['🐭', 'mouse'], ['🐹', 'hamster'], ['🐰', 'rabbit'],
            ['🦊', 'fox'], ['🐻', 'bear'], ['🐼', 'panda'], ['🐨', 'koala'], ['🐯', 'tiger'],
            ['🦁', 'lion'], ['🐮', 'cow'], ['🐷', 'pig'], ['🐸', 'frog'], ['🐵', 'monkey'],
            ['🐔', 'chicken'], ['🐧', 'penguin'], ['🐦', 'bird'], ['🦄', 'unicorn'], ['🐝', 'bee'],
            ['🦋', 'butterfly'], ['🐢', 'turtle'], ['🐙', 'octopus'], ['🐠', 'fish'], ['🐬', 'dolphin'],
        ]],
        ['Essen', '🍎', [
            ['🍎', 'apple'], ['🍌', 'banana'], ['🍇', 'grapes'], ['🍓', 'strawberry'], ['🍑', 'peach'],
            ['🍉', 'watermelon'], ['🍕', 'pizza'], ['🍔', 'burger'], ['🍟', 'fries'], ['🌭', 'hotdog'],
            ['🍿', 'popcorn'], ['🍩', 'donut'], ['🍪', 'cookie'], ['🎂', 'cake'], ['🍫', 'chocolate'],
            ['☕', 'coffee'], ['🍵', 'tea'], ['🍺', 'beer'], ['🍷', 'wine'], ['🥂', 'cheers'],
        ]],
        ['Aktivität', '⚽', [
            ['⚽', 'soccer football'], ['🏀', 'basketball'], ['🏈', 'football'], ['⚾', 'baseball'], ['🎾', 'tennis'],
            ['🏐', 'volleyball'], ['🎱', 'pool'], ['🏓', 'ping pong'], ['🥅', 'goal'], ['⛳', 'golf'],
            ['🏆', 'trophy'], ['🥇', 'gold medal'], ['🎮', 'game'], ['🎲', 'dice'], ['🎯', 'target'],
            ['🎸', 'guitar'], ['🎵', 'music'], ['🎉', 'party tada'], ['🎊', 'confetti'], ['🎁', 'gift'],
        ]],
        ['Reisen', '✈️', [
            ['✈️', 'plane'], ['🚗', 'car'], ['🚕', 'taxi'], ['🚌', 'bus'], ['🚲', 'bike'],
            ['🏍️', 'motorcycle'], ['🚄', 'train'], ['🚢', 'ship'], ['🚀', 'rocket'], ['🗺️', 'map'],
            ['🏔️', 'mountain'], ['🏖️', 'beach'], ['🏝️', 'island'], ['🌋', 'volcano'], ['🗽', 'statue'],
            ['🌆', 'city'], ['🌃', 'night'], ['🌅', 'sunrise'], ['⛺', 'camp'], ['🧳', 'luggage'],
        ]],
        ['Objekte', '💡', [
            ['💡', 'idea bulb'], ['📱', 'phone'], ['💻', 'laptop'], ['⌚', 'watch'], ['📷', 'camera'],
            ['🎥', 'video'], ['🔋', 'battery'], ['💰', 'money'], ['💳', 'card'], ['✏️', 'pencil'],
            ['📌', 'pin'], ['📎', 'clip'], ['🔑', 'key'], ['🔒', 'lock'], ['🔔', 'bell'],
            ['📚', 'books'], ['✅', 'check done'], ['❌', 'cross no'], ['⚠️', 'warning'], ['💯', 'hundred'],
        ]],
        ['Symbole', '❤️', [
            ['❤️', 'red heart love'], ['🧡', 'orange heart'], ['💛', 'yellow heart'], ['💚', 'green heart'], ['💙', 'blue heart'],
            ['💜', 'purple heart'], ['🖤', 'black heart'], ['🤍', 'white heart'], ['💔', 'broken heart'], ['💕', 'two hearts'],
            ['💖', 'sparkle heart'], ['⭐', 'star'], ['🌟', 'glow star'], ['✨', 'sparkles'], ['🔥', 'fire lit'],
            ['💥', 'boom'], ['💫', 'dizzy'], ['☀️', 'sun'], ['🌈', 'rainbow'], ['❄️', 'snow'],
        ]],
    ];
    const RECENT_KEY = 'emoji-recent';

    function getRecent() {
        try {
            return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }
    function pushRecent(emoji) {
        let r = getRecent().filter((e) => e !== emoji);
        r.unshift(emoji);
        r = r.slice(0, 24);
        try {
            localStorage.setItem(RECENT_KEY, JSON.stringify(r));
        } catch (e) {
            /* ignore */
        }
    }

    // Build the popup once.
    const pop = document.createElement('div');
    pop.className =
        'fixed z-[60] hidden w-72 rounded-xl border border-gray-200 bg-white p-2 shadow-2xl dark:border-gray-700 dark:bg-gray-900';
    pop.innerHTML =
        '<input type="search" data-ep-search placeholder="Emoji suchen …" class="input mb-2 !py-1 text-sm">' +
        '<div data-ep-tabs class="mb-1 flex justify-between"></div>' +
        '<div data-ep-grid class="grid max-h-44 grid-cols-8 gap-0.5 overflow-y-auto text-xl"></div>';
    document.body.appendChild(pop);
    const search = pop.querySelector('[data-ep-search]');
    const tabs = pop.querySelector('[data-ep-tabs]');
    const grid = pop.querySelector('[data-ep-grid]');

    let target = null;
    let activeCat = 0;

    CATS.forEach((c, i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'rounded px-1 text-lg hover:bg-gray-100 dark:hover:bg-gray-800';
        b.textContent = c[1];
        b.title = c[0];
        b.addEventListener('click', () => {
            activeCat = i;
            search.value = '';
            renderGrid();
        });
        tabs.appendChild(b);
    });

    function cell(emoji) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'rounded p-1 hover:bg-gray-100 dark:hover:bg-gray-800';
        b.textContent = emoji;
        b.addEventListener('click', () => insert(emoji));
        return b;
    }

    function renderGrid() {
        grid.innerHTML = '';
        const q = search.value.trim().toLowerCase();
        if (q) {
            const seen = new Set();
            CATS.forEach((c) =>
                c[2].forEach(([emoji, name]) => {
                    if (!seen.has(emoji) && (name.includes(q) || emoji === q)) {
                        seen.add(emoji);
                        grid.appendChild(cell(emoji));
                    }
                })
            );
            return;
        }
        const recent = getRecent();
        if (activeCat === 0 && recent.length) {
            const hdr = document.createElement('div');
            hdr.className = 'col-span-8 px-1 text-xs text-gray-400';
            hdr.textContent = 'Zuletzt verwendet';
            grid.appendChild(hdr);
            recent.forEach((e) => grid.appendChild(cell(e)));
            const hdr2 = document.createElement('div');
            hdr2.className = 'col-span-8 px-1 pt-1 text-xs text-gray-400';
            hdr2.textContent = CATS[0][0];
            grid.appendChild(hdr2);
        }
        CATS[activeCat][2].forEach(([emoji]) => grid.appendChild(cell(emoji)));
    }

    function insert(emoji) {
        pushRecent(emoji);
        if (!target) return;
        const el = target;
        if (typeof el.selectionStart === 'number') {
            const s = el.selectionStart;
            const eN = el.selectionEnd;
            el.value = el.value.slice(0, s) + emoji + el.value.slice(eN);
            el.selectionStart = el.selectionEnd = s + emoji.length;
        } else {
            el.value += emoji;
        }
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.focus();
    }

    function open(trigger) {
        target = document.querySelector(trigger.dataset.emojiTarget);
        if (!target) return;
        search.value = '';
        activeCat = 0;
        renderGrid();
        pop.classList.remove('hidden');
        const r = trigger.getBoundingClientRect();
        const top = Math.max(8, r.top - pop.offsetHeight - 8);
        const left = Math.min(window.innerWidth - pop.offsetWidth - 8, Math.max(8, r.left));
        pop.style.top = top + 'px';
        pop.style.left = left + 'px';
    }

    search.addEventListener('input', renderGrid);

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-emoji-trigger]');
        if (trigger) {
            e.preventDefault();
            if (!pop.classList.contains('hidden') && target === document.querySelector(trigger.dataset.emojiTarget)) {
                pop.classList.add('hidden');
            } else {
                open(trigger);
            }
            return;
        }
        if (!pop.contains(e.target)) pop.classList.add('hidden');
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') pop.classList.add('hidden');
    });
})();
