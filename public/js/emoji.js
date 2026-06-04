/**
 * Dependency-free emoji library: a broad, searchable dataset grouped into
 * categories, with recently-used (localStorage) and keyword search.
 *
 * Two ways to use it:
 *
 *   1. Declarative (composer):  a button with
 *        <button data-emoji-trigger data-emoji-target="#dm-body">
 *      opens the picker and inserts the chosen emoji at the target's caret.
 *
 *   2. Programmatic (reactions): other scripts call
 *        window.EmojiPicker.open(anchorEl, (emoji) => { ... });
 *      to receive the chosen emoji via callback.
 *
 * Kept in-house (no CDN / no extra npm dependency) to match the project's
 * vendor-locally convention, while still offering a large, filterable set.
 */
(function () {
    'use strict';

    // [name, tab glyph, [[emoji, search keywords], ...]]
    const CATS = [
        ['Smileys', '😀', [
            ['😀', 'grinning happy smile lachen'], ['😃', 'smile happy froh'], ['😄', 'happy laugh freude'], ['😁', 'grin beam'], ['😆', 'laugh squint'],
            ['😅', 'sweat smile relief'], ['🤣', 'rofl rolling floor'], ['😂', 'joy tears lachen weinen'], ['🙂', 'slight smile'], ['🙃', 'upside down'],
            ['😉', 'wink zwinkern'], ['😊', 'blush smile'], ['😇', 'innocent angel engel'], ['🥰', 'love hearts verliebt'], ['😍', 'heart eyes'],
            ['🤩', 'star struck excited'], ['😘', 'kiss kuss'], ['😗', 'kissing'], ['😚', 'kissing closed'], ['😙', 'kissing smile'],
            ['😋', 'yum tasty lecker'], ['😛', 'tongue zunge'], ['😜', 'winking tongue'], ['🤪', 'zany goofy'], ['😝', 'squint tongue'],
            ['🤑', 'money mouth geld'], ['🤗', 'hug umarmung'], ['🤭', 'hand mouth'], ['🤫', 'shush quiet leise'], ['🤔', 'thinking nachdenken'],
            ['🤐', 'zipper mouth'], ['🤨', 'raised brow skeptisch'], ['😐', 'neutral'], ['😑', 'expressionless'], ['😶', 'no mouth'],
            ['😏', 'smirk'], ['😒', 'unamused genervt'], ['🙄', 'eye roll augenrollen'], ['😬', 'grimace'], ['😮‍💨', 'exhale relieved'],
            ['🤥', 'lying liar'], ['😌', 'relieved calm'], ['😔', 'pensive sad traurig'], ['😪', 'sleepy müde'], ['🤤', 'drooling'],
            ['😴', 'sleep schlafen zzz'], ['😷', 'mask sick maske'], ['🤒', 'thermometer sick krank'], ['🤕', 'bandage hurt'], ['🤢', 'nausea sick'],
            ['🤮', 'vomit puke'], ['🤧', 'sneeze'], ['🥵', 'hot heiss'], ['🥶', 'cold kalt'], ['🥴', 'woozy dizzy'],
            ['😵', 'dizzy knockout'], ['🤯', 'mind blown explodiert'], ['🤠', 'cowboy'], ['🥳', 'party feier'], ['😎', 'cool sunglasses'],
            ['🤓', 'nerd geek'], ['🧐', 'monocle'], ['😕', 'confused'], ['😟', 'worried sorge'], ['🙁', 'frown'],
            ['☹️', 'frowning sad'], ['😮', 'open mouth wow'], ['😯', 'hushed'], ['😲', 'astonished'], ['😳', 'flushed blush'],
            ['🥺', 'pleading puppy bitte'], ['😦', 'frowning open'], ['😧', 'anguished'], ['😨', 'fearful angst'], ['😰', 'anxious sweat'],
            ['😥', 'sad relieved'], ['😢', 'cry weinen sad'], ['😭', 'sob crying'], ['😱', 'scream schrei'], ['😖', 'confounded'],
            ['😣', 'persevere'], ['😞', 'disappointed'], ['😓', 'downcast sweat'], ['😩', 'weary'], ['😫', 'tired'],
            ['🥱', 'yawn gähnen'], ['😤', 'triumph huff'], ['😡', 'rage angry wütend'], ['😠', 'angry sauer'], ['🤬', 'cursing swearing'],
            ['😈', 'devil teufel'], ['👿', 'imp angry'], ['💀', 'skull tod'], ['💩', 'poop'], ['🤡', 'clown'],
            ['👻', 'ghost geist'], ['👽', 'alien'], ['🤖', 'robot'], ['🎃', 'pumpkin halloween'],
        ]],
        ['Gesten', '👍', [
            ['👍', 'thumbs up like daumen'], ['👎', 'thumbs down dislike'], ['👏', 'clap applaus'], ['🙌', 'raised hands hurra'], ['👐', 'open hands'],
            ['🤲', 'palms together'], ['🤝', 'handshake deal'], ['🙏', 'pray thanks danke bitte'], ['✌️', 'peace victory'], ['🤞', 'fingers crossed glück'],
            ['🤟', 'love you'], ['🤘', 'rock horns'], ['🤙', 'call me shaka'], ['👈', 'left links'], ['👉', 'right rechts'],
            ['👆', 'up oben'], ['👇', 'down unten'], ['☝️', 'index up'], ['✋', 'raised hand stop'], ['🤚', 'back hand'],
            ['🖐️', 'splayed hand'], ['🖖', 'vulcan spock'], ['👋', 'wave hi hallo tschüss'], ['🤏', 'pinch small'], ['👌', 'ok perfect'],
            ['✊', 'fist faust'], ['👊', 'punch fist bump'], ['🤛', 'left fist'], ['🤜', 'right fist'], ['💪', 'muscle strong stark'],
            ['🫶', 'heart hands liebe'], ['🫰', 'finger heart'], ['👀', 'eyes augen schauen'], ['🧠', 'brain'], ['👅', 'tongue'],
            ['👄', 'lips mund'], ['🦷', 'tooth'], ['👂', 'ear ohr'], ['👃', 'nose nase'], ['🦶', 'foot'],
        ]],
        ['Tiere', '🐶', [
            ['🐶', 'dog hund'], ['🐱', 'cat katze'], ['🐭', 'mouse maus'], ['🐹', 'hamster'], ['🐰', 'rabbit hase'],
            ['🦊', 'fox fuchs'], ['🐻', 'bear bär'], ['🐼', 'panda'], ['🐻‍❄️', 'polar bear eisbär'], ['🐨', 'koala'],
            ['🐯', 'tiger'], ['🦁', 'lion löwe'], ['🐮', 'cow kuh'], ['🐷', 'pig schwein'], ['🐸', 'frog frosch'],
            ['🐵', 'monkey affe'], ['🙈', 'see no evil monkey'], ['🙉', 'hear no evil'], ['🙊', 'speak no evil'], ['🐔', 'chicken huhn'],
            ['🐧', 'penguin pinguin'], ['🐦', 'bird vogel'], ['🐤', 'chick küken'], ['🦆', 'duck ente'], ['🦅', 'eagle adler'],
            ['🦉', 'owl eule'], ['🦇', 'bat fledermaus'], ['🐺', 'wolf'], ['🐗', 'boar'], ['🐴', 'horse pferd'],
            ['🦄', 'unicorn einhorn'], ['🐝', 'bee biene'], ['🐛', 'bug raupe'], ['🦋', 'butterfly schmetterling'], ['🐌', 'snail schnecke'],
            ['🐞', 'ladybug marienkäfer'], ['🐜', 'ant ameise'], ['🕷️', 'spider spinne'], ['🐢', 'turtle schildkröte'], ['🐍', 'snake schlange'],
            ['🦎', 'lizard'], ['🐙', 'octopus krake'], ['🦑', 'squid'], ['🦐', 'shrimp'], ['🦀', 'crab krabbe'],
            ['🐠', 'fish fisch'], ['🐟', 'fish'], ['🐬', 'dolphin delfin'], ['🐳', 'whale wal'], ['🦈', 'shark hai'],
            ['🐊', 'crocodile krokodil'], ['🐘', 'elephant elefant'], ['🦏', 'rhino'], ['🦒', 'giraffe'], ['🐪', 'camel kamel'],
            ['🌵', 'cactus kaktus'], ['🌲', 'evergreen tree baum'], ['🌳', 'tree baum'], ['🌴', 'palm palme'], ['🌱', 'seedling'],
            ['🌿', 'herb'], ['🍀', 'clover kleeblatt glück'], ['🍁', 'maple leaf'], ['🍂', 'fallen leaves herbst'], ['🌷', 'tulip'],
            ['🌹', 'rose'], ['🌻', 'sunflower sonnenblume'], ['🌼', 'blossom'], ['🌸', 'cherry blossom'], ['💐', 'bouquet blumen'],
        ]],
        ['Essen', '🍎', [
            ['🍎', 'apple apfel'], ['🍏', 'green apple'], ['🍐', 'pear birne'], ['🍊', 'orange'], ['🍋', 'lemon zitrone'],
            ['🍌', 'banana'], ['🍉', 'watermelon melone'], ['🍇', 'grapes trauben'], ['🍓', 'strawberry erdbeere'], ['🫐', 'blueberries'],
            ['🍈', 'melon'], ['🍒', 'cherries kirschen'], ['🍑', 'peach pfirsich'], ['🥭', 'mango'], ['🍍', 'pineapple ananas'],
            ['🥥', 'coconut'], ['🥝', 'kiwi'], ['🍅', 'tomato tomate'], ['🍆', 'eggplant aubergine'], ['🥑', 'avocado'],
            ['🥦', 'broccoli'], ['🥕', 'carrot karotte'], ['🌽', 'corn mais'], ['🌶️', 'pepper chili'], ['🥔', 'potato kartoffel'],
            ['🍠', 'sweet potato'], ['🥐', 'croissant'], ['🍞', 'bread brot'], ['🥖', 'baguette'], ['🥨', 'pretzel brezel'],
            ['🧀', 'cheese käse'], ['🥚', 'egg ei'], ['🍳', 'fried egg spiegelei'], ['🥞', 'pancakes'], ['🧇', 'waffle'],
            ['🥓', 'bacon speck'], ['🍔', 'burger hamburger'], ['🍟', 'fries pommes'], ['🍕', 'pizza'], ['🌭', 'hotdog'],
            ['🥪', 'sandwich'], ['🌮', 'taco'], ['🌯', 'burrito'], ['🥗', 'salad salat'], ['🍝', 'pasta spaghetti'],
            ['🍜', 'ramen noodles'], ['🍣', 'sushi'], ['🍤', 'shrimp tempura'], ['🍙', 'rice ball'], ['🍚', 'rice reis'],
            ['🍦', 'soft ice eis'], ['🍨', 'ice cream'], ['🍧', 'shaved ice'], ['🍩', 'donut'], ['🍪', 'cookie keks'],
            ['🎂', 'cake birthday geburtstag'], ['🍰', 'cake kuchen'], ['🧁', 'cupcake'], ['🥧', 'pie'], ['🍫', 'chocolate schokolade'],
            ['🍬', 'candy bonbon'], ['🍭', 'lollipop'], ['🍯', 'honey honig'], ['🍿', 'popcorn'], ['🧂', 'salt salz'],
            ['☕', 'coffee kaffee'], ['🍵', 'tea tee'], ['🧃', 'juice saft'], ['🥤', 'soda drink'], ['🍺', 'beer bier'],
            ['🍻', 'beers prost cheers'], ['🍷', 'wine wein'], ['🥂', 'champagne cheers anstoßen'], ['🍸', 'cocktail'], ['🥃', 'whisky'],
        ]],
        ['Aktivität', '⚽', [
            ['⚽', 'soccer football fussball'], ['🏀', 'basketball'], ['🏈', 'football'], ['⚾', 'baseball'], ['🥎', 'softball'],
            ['🎾', 'tennis'], ['🏐', 'volleyball'], ['🏉', 'rugby'], ['🥏', 'frisbee'], ['🎱', 'pool billard'],
            ['🏓', 'ping pong tischtennis'], ['🏸', 'badminton'], ['🥅', 'goal tor'], ['🏒', 'hockey'], ['🏑', 'field hockey'],
            ['🥍', 'lacrosse'], ['🏏', 'cricket'], ['⛳', 'golf'], ['🏹', 'archery bogen'], ['🎣', 'fishing angeln'],
            ['🥊', 'boxing'], ['🥋', 'martial arts'], ['⛸️', 'ice skate'], ['🛹', 'skateboard'], ['🛼', 'roller skate'],
            ['🛷', 'sled schlitten'], ['⛷️', 'ski'], ['🏂', 'snowboard'], ['🏋️', 'weight lifting gym'], ['🤸', 'cartwheel'],
            ['⛹️', 'basketball player'], ['🤾', 'handball'], ['🏊', 'swimming schwimmen'], ['🚴', 'cycling rad'], ['🧗', 'climbing klettern'],
            ['🏆', 'trophy pokal'], ['🥇', 'gold medal'], ['🥈', 'silver medal'], ['🥉', 'bronze medal'], ['🎖️', 'medal'],
            ['🎯', 'target dart ziel'], ['🎲', 'dice würfel'], ['🎮', 'game controller spiel'], ['🕹️', 'joystick'], ['🎰', 'slot machine'],
            ['🎸', 'guitar gitarre'], ['🎹', 'piano keyboard'], ['🥁', 'drum trommel'], ['🎺', 'trumpet'], ['🎻', 'violin geige'],
            ['🎤', 'microphone mikrofon'], ['🎧', 'headphones kopfhörer'], ['🎵', 'music note musik'], ['🎶', 'notes'], ['🎼', 'score'],
            ['🎨', 'art palette kunst'], ['🎭', 'theater masks'], ['🎬', 'clapper film'], ['🎉', 'tada party feier'], ['🎊', 'confetti konfetti'],
            ['🎁', 'gift geschenk'], ['🎈', 'balloon ballon'], ['🎏', 'carp streamer'], ['🎀', 'ribbon schleife'],
        ]],
        ['Reisen', '✈️', [
            ['✈️', 'plane flugzeug'], ['🛫', 'takeoff'], ['🛬', 'landing'], ['🚀', 'rocket rakete'], ['🛸', 'ufo'],
            ['🚁', 'helicopter'], ['🚗', 'car auto'], ['🚕', 'taxi'], ['🚙', 'suv'], ['🚌', 'bus'],
            ['🚎', 'trolleybus'], ['🏎️', 'race car'], ['🚓', 'police car polizei'], ['🚑', 'ambulance'], ['🚒', 'fire truck feuerwehr'],
            ['🚐', 'van'], ['🚚', 'truck lkw'], ['🚛', 'lorry'], ['🚜', 'tractor traktor'], ['🏍️', 'motorcycle motorrad'],
            ['🛵', 'scooter roller'], ['🚲', 'bike fahrrad'], ['🛴', 'kick scooter'], ['🚄', 'train zug'], ['🚅', 'bullet train'],
            ['🚈', 'metro'], ['🚂', 'locomotive lokomotive'], ['🚆', 'train'], ['🚊', 'tram'], ['🚢', 'ship schiff'],
            ['⛴️', 'ferry fähre'], ['🛥️', 'motor boat'], ['⛵', 'sailboat segelboot'], ['🚤', 'speedboat'], ['⚓', 'anchor anker'],
            ['🗺️', 'map karte'], ['🧭', 'compass kompass'], ['🏔️', 'mountain berg'], ['⛰️', 'mountain'], ['🌋', 'volcano vulkan'],
            ['🏕️', 'camping zelt'], ['🏖️', 'beach strand'], ['🏝️', 'island insel'], ['🏜️', 'desert wüste'], ['🗽', 'statue liberty'],
            ['🗼', 'tower turm'], ['🏰', 'castle schloss'], ['🏯', 'japanese castle'], ['🎡', 'ferris wheel riesenrad'], ['🎢', 'roller coaster'],
            ['🎠', 'carousel karussell'], ['⛲', 'fountain brunnen'], ['🏙️', 'cityscape stadt'], ['🌆', 'city sunset'], ['🌃', 'night nacht'],
            ['🌉', 'bridge brücke'], ['🌅', 'sunrise sonnenaufgang'], ['🌄', 'sunrise mountains'], ['🏠', 'house haus'], ['🏡', 'home garden'],
            ['🏢', 'office building'], ['🏥', 'hospital krankenhaus'], ['🏦', 'bank'], ['🏨', 'hotel'], ['⛺', 'tent camp'],
        ]],
        ['Objekte', '💡', [
            ['💡', 'idea bulb glühbirne'], ['🔦', 'flashlight taschenlampe'], ['🕯️', 'candle kerze'], ['📱', 'phone handy'], ['📲', 'mobile'],
            ['💻', 'laptop'], ['🖥️', 'desktop computer'], ['⌨️', 'keyboard tastatur'], ['🖱️', 'mouse maus'], ['🖨️', 'printer drucker'],
            ['⌚', 'watch uhr'], ['⏰', 'alarm wecker'], ['⏳', 'hourglass sanduhr'], ['📷', 'camera kamera'], ['📸', 'camera flash'],
            ['🎥', 'movie camera'], ['📺', 'tv fernseher'], ['📻', 'radio'], ['🔋', 'battery akku'], ['🔌', 'plug stecker'],
            ['💰', 'money geld'], ['💵', 'dollar'], ['💶', 'euro'], ['💳', 'card karte'], ['🧾', 'receipt'],
            ['✏️', 'pencil stift'], ['✒️', 'pen'], ['🖋️', 'fountain pen'], ['📝', 'memo notiz'], ['📒', 'ledger'],
            ['📚', 'books bücher'], ['📖', 'open book'], ['📰', 'newspaper zeitung'], ['📌', 'pushpin pin'], ['📎', 'paperclip büroklammer'],
            ['✂️', 'scissors schere'], ['📏', 'ruler lineal'], ['🔑', 'key schlüssel'], ['🔒', 'lock schloss'], ['🔓', 'unlock'],
            ['🔔', 'bell glocke'], ['🔕', 'mute stumm'], ['📣', 'megaphone'], ['📢', 'loudspeaker'], ['🔍', 'search lupe'],
            ['🔬', 'microscope'], ['🔭', 'telescope teleskop'], ['💊', 'pill pille'], ['💉', 'syringe spritze'], ['🩹', 'bandage pflaster'],
            ['🧪', 'test tube'], ['🌡️', 'thermometer'], ['🧹', 'broom besen'], ['🛒', 'cart einkaufswagen'], ['🎓', 'graduation abschluss'],
            ['👑', 'crown krone'], ['💎', 'gem diamant'], ['⚙️', 'gear zahnrad'], ['🔨', 'hammer'], ['🛠️', 'tools werkzeug'],
            ['🧰', 'toolbox'], ['🧲', 'magnet'], ['📦', 'package paket'], ['✅', 'check done erledigt'], ['❌', 'cross no nein'],
            ['⚠️', 'warning warnung'], ['💯', 'hundred perfekt'],
        ]],
        ['Symbole', '❤️', [
            ['❤️', 'red heart love liebe herz'], ['🧡', 'orange heart'], ['💛', 'yellow heart'], ['💚', 'green heart'], ['💙', 'blue heart'],
            ['💜', 'purple heart'], ['🖤', 'black heart'], ['🤍', 'white heart'], ['🤎', 'brown heart'], ['💔', 'broken heart herzschmerz'],
            ['❣️', 'heart exclamation'], ['💕', 'two hearts'], ['💞', 'revolving hearts'], ['💓', 'beating heart'], ['💗', 'growing heart'],
            ['💖', 'sparkling heart'], ['💘', 'cupid arrow'], ['💝', 'heart gift'], ['💟', 'heart decoration'], ['❤️‍🔥', 'heart fire'],
            ['💋', 'kiss mark kuss'], ['💌', 'love letter'], ['⭐', 'star stern'], ['🌟', 'glowing star'], ['✨', 'sparkles funkeln'],
            ['💫', 'dizzy'], ['🔥', 'fire feuer lit'], ['💥', 'boom explosion'], ['💢', 'anger'], ['💦', 'sweat drops'],
            ['💧', 'droplet tropfen'], ['☀️', 'sun sonne'], ['🌤️', 'sun cloud'], ['⛅', 'partly cloudy'], ['☁️', 'cloud wolke'],
            ['🌧️', 'rain regen'], ['⛈️', 'thunder gewitter'], ['🌈', 'rainbow regenbogen'], ['❄️', 'snow schnee'], ['⛄', 'snowman schneemann'],
            ['🌙', 'moon mond'], ['🌝', 'full moon'], ['🌍', 'earth erde'], ['💤', 'sleep zzz'], ['🎵', 'music note'],
            ['✔️', 'check'], ['☑️', 'checkbox'], ['❓', 'question frage'], ['❗', 'exclamation'], ['‼️', 'double exclamation'],
            ['💲', 'dollar'], ['➕', 'plus'], ['➖', 'minus'], ['➗', 'divide'], ['♻️', 'recycle recycling'],
            ['✅', 'check mark'], ['🆗', 'ok'], ['🆕', 'new neu'], ['🔝', 'top'], ['🎶', 'notes'],
            ['🇩🇪', 'germany deutschland flagge flag'], ['🇦🇹', 'austria österreich flag'], ['🇨🇭', 'switzerland schweiz flag'], ['🇺🇸', 'usa flag'], ['🏳️‍🌈', 'rainbow flag pride'],
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
        'fixed z-[70] hidden w-72 rounded-xl border border-gray-200 bg-white p-2 shadow-2xl dark:border-gray-700 dark:bg-gray-900';
    pop.innerHTML =
        '<input type="search" data-ep-search placeholder="Emoji suchen …" class="input mb-2 !py-1 text-sm">' +
        '<div data-ep-tabs class="mb-1 flex justify-between"></div>' +
        '<div data-ep-grid class="grid max-h-44 grid-cols-8 gap-0.5 overflow-y-auto text-xl"></div>';
    document.body.appendChild(pop);
    const search = pop.querySelector('[data-ep-search]');
    const tabs = pop.querySelector('[data-ep-tabs]');
    const grid = pop.querySelector('[data-ep-grid]');

    let onSelect = null;       // current consumer callback
    let closeOnSelect = true;  // whether to hide the popup after a pick
    let activeCat = 0;
    // When opened programmatically, the triggering click keeps bubbling to the
    // outside-click handler below; this flag swallows that one event.
    let swallowNextOutside = false;

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
        b.addEventListener('click', () => choose(emoji));
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
            if (!grid.children.length) {
                const none = document.createElement('div');
                none.className = 'col-span-8 px-1 py-3 text-center text-xs text-gray-400';
                none.textContent = 'Keine Treffer';
                grid.appendChild(none);
            }
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

    // A user picked an emoji: remember it, hand it to the consumer, maybe close.
    function choose(emoji) {
        pushRecent(emoji);
        if (typeof onSelect === 'function') onSelect(emoji);
        if (closeOnSelect) hide();
    }

    function hide() {
        pop.classList.add('hidden');
    }

    function position(anchorEl) {
        const r = anchorEl.getBoundingClientRect();
        const top = Math.max(8, r.top - pop.offsetHeight - 8);
        const left = Math.min(window.innerWidth - pop.offsetWidth - 8, Math.max(8, r.left));
        pop.style.top = top + 'px';
        pop.style.left = left + 'px';
    }

    function openAt(anchorEl, handler, opts) {
        onSelect = handler;
        closeOnSelect = !(opts && opts.closeOnSelect === false);
        search.value = '';
        activeCat = 0;
        renderGrid();
        pop.classList.remove('hidden');
        position(anchorEl);
    }

    // Insert at the caret of a target input/textarea (composer use).
    function insertInto(el, emoji) {
        if (!el) return;
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

    // Public API for other scripts (e.g. message reactions).
    window.EmojiPicker = {
        open(anchorEl, handler, opts) {
            openAt(anchorEl, handler, opts);
            swallowNextOutside = true;
        },
        close: hide,
    };

    search.addEventListener('input', renderGrid);

    // Declarative trigger: insert into the element named by data-emoji-target.
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-emoji-trigger]');
        if (trigger) {
            e.preventDefault();
            const targetEl = document.querySelector(trigger.dataset.emojiTarget);
            if (!targetEl) return;
            const sameTarget = !pop.classList.contains('hidden') && pop.dataset.target === trigger.dataset.emojiTarget;
            if (sameTarget) {
                hide();
            } else {
                pop.dataset.target = trigger.dataset.emojiTarget;
                openAt(trigger, (emoji) => insertInto(targetEl, emoji), { closeOnSelect: false });
            }
            return;
        }
        if (swallowNextOutside) {
            swallowNextOutside = false;
            return;
        }
        if (!pop.contains(e.target)) hide();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') hide();
    });
})();
