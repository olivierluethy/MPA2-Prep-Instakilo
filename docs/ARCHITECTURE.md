# Architecture & Modernization Notes

This document explains the architecture of the modernized Instakilo and summarizes
what changed from the original procedural project, why, and what remains.

## 1. Architecture

### MVC, no framework

The app keeps a deliberate, minimal MVC structure with a tiny custom "framework"
under `app/Core/`:

```
Request ─▶ public/index.php (front controller)
            │  loads config, starts hardened session
            ▼
         Router (config/routes.php)  ── method + path ─▶ Controller@action
            │
   Controller (thin)  ── validates, enforces auth/CSRF ─▶ Model (PDO)
            │                                                 │
            └────────────── View (layout + partials) ◀────────┘
```

- **Models** (`app/Models`) own all SQL and extend a base `Model` that holds the
  shared PDO connection and `fetchOne/fetchAll/run` helpers.
- **Controllers** (`app/Controllers`) are thin: parse input via `Request`, enforce
  `requireAuth`/`requireCsrf`, delegate to models, and render a view or JSON.
- **Views** (`app/Views`) are plain PHP templates. `View::render()` renders a page
  template into `$content` and wraps it in `layouts/app.php`. Reusable pieces live
  in `partials/`.

### Centralized database layer

`App\Core\Database::connection()` returns a single, lazily-created PDO instance
configured once (exceptions on error, `utf8mb4`, real prepared statements). Every
model goes through it. This replaced **three** competing connection mechanisms in
the original code.

### Configuration

`config/config.php` is the single config source; it reads environment variables
with development fallbacks. `App\Core\Config` loads it once and exposes dot-notation
access (`Config::get('db.host')`).

### Security model

- **Document root = `public/`** — application source, config and the database layer
  are not reachable over HTTP.
- **CSRF** tokens on every POST (`App\Core\Csrf`).
- **All mutations are POST** (like/follow/unlike were previously GET).
- **Prepared statements** everywhere (no string-concatenated SQL).
- **Output escaping** via `e()`; rich text runs through an **allowlist HTML
  sanitizer** (`App\Core\HtmlSanitizer`, DOM-based) before storage.
- **Sessions**: `HttpOnly`, `SameSite=Lax`, `Secure` behind HTTPS; id regenerated
  on login; full teardown on logout.
- **Uploads** validated by real MIME (`getimagesize`), size and count; served from
  a dedicated endpoint, never inlined.
- Generic auth errors (no account enumeration). Security headers via `.htaccess`.

### Frontend

- **Tailwind CSS**, compiled from `resources/css/app.css` to `public/css/app.css`.
  A centralized component layer (`.btn`, `.card`, `.input`, …) keeps markup
  consistent and ships dark-mode variants.
- **Dark mode** is class-based, applied before first paint (no flash), toggled and
  persisted to `localStorage` — every page is themed via the shared layout.
- **Quill** (vendored, served from our origin) provides the rich-text editor.
- Vanilla JS modules: `theme.js`, `app.js` (dropdown, modal, carousels,
  AJAX like/follow), `upload.js` (drag-&-drop multi-image upload with preview,
  reorder, removal, progress).

### Data model

`users`, `posts`, `post_images` (many per post, ordered), `followers`, `likes`,
`comments`, `saved_posts`, `reposts`, `messages` (DMs; `kind`, `shared_post_id`,
`reply_to_id`, `edited_at`, `deleted_at`), `message_media` (attachment blobs,
many per message via `sort_order`), `message_reactions` (emoji per user/message)
and `dm_typing`. Foreign keys cascade on delete, and `UNIQUE` constraints make
likes/follows/saves/reposts/reactions idempotent. Image and attachment bytes are
stored as `LONGBLOB` and streamed via controllers.

### Refinements (post-modernization)

- **Consistent dates** — `format_datetime()` / `format_date()` helpers render every
  post/comment date in one readable style ("June 3, 2026, 21:14").
- **Comments** — `Comment` model + `/posts/comment` (add) and `/posts/comments`
  (paginated list) endpoints; plain-text, CSRF-protected, escaped on output. The
  feed embeds a 2-comment preview per post via a single window-function query.
- **Image slider** — a `translateX` track (`data-carousel-track`) shows exactly
  one image at a time with a smooth slide and no overlap, replacing the old
  opacity-stacked images.
- **User search** — `SearchController` + `User::search()` (bound LIKE with escaped
  metacharacters); the nav box queries `/search` with a 200 ms debounce and links
  results straight to the profile page.

### Social features (engagement round)

- **Comment & post management** — author-only edit/delete for comments
  (`Comment::isOwnedBy`) and posts (`Post::isOwnedBy`); post edit manages images
  (remove/reorder/add) with image-count validated **before** mutation; post delete
  is confirmed client-side and cascades.
- **Saves & reposts** — `saved_posts` / `reposts` tables with idempotent toggles;
  `/saved` collection page; reposts of public posts merge into followers'
  timelines (`HomeController::buildFeed`) with a "Reposted by" banner. The feed is
  now a **chronological timeline** (the old like-count ordering didn't fit a
  repost-aware timeline) with an `all` / `following` scope toggle.
- **Direct messages** — `messages` table + `MessageController` (conversation list,
  thread, send, share). A post can be shared via DM (preview + link + attribution);
  the nav shows an unread badge. Bodies are stored stripped of markup and escaped
  on output.
- **Search posts + single-post page** — search now returns users *and* posts;
  `GET /post?id=` renders one post (used by share links, reposts and search).
- **Compact feed** — narrower single-column cards (`max-w-md`, smaller controls).

### Rich messaging, real-time & UX round

**Data model additions:** `messages.kind` (text|post|image|gif|video|file|link),
`message_media` (DM attachment blobs, cascade-deleted), `dm_typing` (short-lived
typing signal). DM attachments are stored as blobs and streamed via
`/messages/media` with a **participant-only** SQL join (authorization in the
query) — inline only for known-safe types, everything else forced to download
with `nosniff`.

**Real-time choice — polling (not WebSockets):** the stack is PHP + Apache with
no long-lived worker process, and a core constraint is "avoid unnecessary
dependencies / keep `docker compose up` single-stack". WebSockets would require a
separate Node/Ratchet/Swoole service and a second container. Polling fits the
architecture with zero new dependencies and is efficient because every poll is
**incremental and indexed**:
- DM thread: `GET /messages/poll?with=&after={lastId}` every 3 s returns only
  rows with `id > lastId` (uses the `(sender,recipient,created_at)` index) plus a
  typing flag; the server renders the bubble HTML so sanitization stays in PHP.
- Feed counters: `GET /posts/stats?ids=…` every 15 s reconciles like/comment/
  repost counts (capped at 60 ids) — always backend truth, never fake increments.
- Nav DM badge: `GET /messages/unread` every 20 s.
Trade-off: updates are near-real-time (bounded by the interval), not instant push.
For this project's scale that is the right balance; the polling layer could later
be swapped for SSE/WebSockets behind the same JSON endpoints without touching the
UI.

**DM filters (all client-side, combinable):** content-type, fuzzy text, and a
time-range histogram operate on already-loaded messages via `data-*` attributes —
no extra queries. Fuzzy search uses **Levenshtein distance** with a length-scaled
threshold; the timeline buckets messages per day, renders an activity histogram
(hover shows date + count) and two range sliders hide out-of-range messages.

**Post creation:** images can be added by file, drag-drop, **URL** (downloaded
server-side, SSRF-guarded) or **clipboard paste**; location has autocomplete and
nearest-city autofill from a **local** city dataset (`config/cities.php`) so no
external geocoding API is needed. External links anywhere (DMs, post text)
trigger a "leave Instakilo?" confirmation before navigating.

**Performance considerations:** incremental/indexed polls; per-conversation client
filtering avoids server round-trips; `stats` is batched and id-capped; media/blobs
are cached (`Cache-Control`/`ETag`) and never base64-inlined. At larger scale the
next steps would be message pagination, moving blobs to object storage, and a
push transport — all isolated behind the current endpoints/models.

### DM expansion round (multi-media, reactions, replies, edit/delete)

**Schema:** `messages` gained `reply_to_id` (self-FK, `ON DELETE SET NULL`),
`edited_at`, `deleted_at`; `message_media` gained `sort_order`; new
`message_reactions` (`UNIQUE(message_id,user_id,emoji)`).

**Multi-attachment storage:** a message is **not** duplicated per file. One
`messages` row (kind `media`) owns N `message_media` rows ordered by `sort_order`;
the model hydrates each message with its `media[]` in one batched
`WHERE message_id IN (…)` query (no N+1). The bubble renders them in a grid, each
attachment individually viewable (image/video) or downloadable (file) through the
participant-scoped `/messages/media` endpoint.

**Reaction & reply data model:** reactions are rows in `message_reactions`;
`toggle()` flips a row, and `forConversation()` aggregates `emoji → {count, mine}`
per message in a single grouped query (the whole conversation, bounded). Replies
are a nullable self-reference `reply_to_id`; the model attaches a small `reply`
preview (author + snippet) so a bubble can quote and link to its target. Reactions
and edits/deletes propagate to the other session through the **poll** response
(`reactions` map + `revisions` list keyed on a `rev` epoch cursor), so existing
bubbles update live without new messages.

**Time-filtering fix:** timestamps are now UTC end-to-end (PHP
`date_default_timezone_set('UTC')` + MySQL `SET time_zone='+00:00'`), and the
client timeline buckets the **epoch range** (`min..max` seconds) into fixed
buckets instead of calendar days — so filtering is correct at second/minute/hour
granularity, including dense same-day (and same-second) conversations. The old
day-bucketed slider (which assumed ≥1-day gaps) is gone.

**Emoji picker:** a dependency-free picker (categories, search, localStorage
recents) inserts at the input caret and dispatches `input` so the typing signal
still fires.

**Soft delete:** deletes set `deleted_at` and blank the body/attachments but keep
the row, preserving conversation flow and reply references; the bubble shows
"Nachricht gelöscht".

## 2. What changed and why

### Bugs fixed (correctness first)

| Problem in the original | Fix |
|-------------------------|-----|
| Three DB layers; the `db()` helper referenced an out-of-scope `$db` and never returned | One `Database` class |
| `ImageUpload` insert used column names that didn't exist (`title` vs `titel`, …) and validated a string as a resource → **upload could never work** | New `Post`/`PostImage` models against a clean schema; verified working |
| Feed queries used `fetch()` (one row) but views `foreach`/`count()`-ed them; aggregate `COUNT()` without `GROUP BY` | Correct `fetchAll()` + `GROUP BY`; feed annotated with `liked_by_me` |
| `Login` model queried a non-existent `Person` table; `create`/`update` views posted to unrouted actions | Removed dead code/views |
| Likes/follows mutated state over **GET**; no CSRF | POST + CSRF everywhere |
| Description output unescaped (stored XSS) | Escaping + HTML sanitizer |
| Hardcoded `http://localhost/Instakilo/...` redirects and DB credentials | `url()` helper + env-based config |

### Improvements

- Multi-image posts with drag-&-drop, reorder, preview and progress.
- Rich-text descriptions (Quill) with server-side sanitization.
- Consistent JSON API envelope and correct HTTP status codes.
- One-command Docker startup; reproducible multi-stage build.
- Images served via cacheable endpoints (ETag/`304`) instead of base64-inlining
  (smaller HTML, browser-cacheable).
- Responsive, accessible, fully dark-mode UI.

### Migration summary

**Removed** (legacy): `index.php`, `.htaccess`, `core/*`, the old
`InstakiloController`/`LoginController`/`ImageUploadController`, the
`Instakilo`/`Login`/`ImageUpload` models, all `*.view.php` templates, the SCSS/CSS
and old JS, `instakilo.sql`, `instakilo.bak`, `app/Views/login/config.php`.

**Added**: `app/Core/*`, new controllers/models/views, `config/*`, `public/`
front controller + assets, `resources/css/app.css`, `db/init/01-schema.sql`,
`Dockerfile`, `docker-compose.yml`, `.env.example`, `package.json`,
`tailwind.config.js`, `scripts/copy-vendor.js`, this docs set.

## 3. Known limitations & future improvements

- **Image storage in MySQL** (`LONGBLOB`). Kept by design for zero-setup
  containerization; for scale, move to object storage / filesystem and keep only
  references. The serving endpoint already isolates this choice.
- **No pagination** on the feed/profile yet (comments *are* paginated) — fine for
  the project's scale; add keyset pagination as data grows.
- **Schema change**: the `comments` table was added to `db/init/01-schema.sql`.
  The init scripts only run on a *fresh* database, so existing local stacks need a
  `docker compose down -v && docker compose up` (or apply the table manually).
- **No automated test suite** — verification was done end-to-end against the running
  stack. Adding PHPUnit for models/sanitizer and a smoke test would harden CI.
- **Tailwind dev rebuilds** rely on the `assets` watcher service; the production
  image bakes the compiled CSS in.
- Rate limiting and email verification are out of scope but would be natural next
  steps for a real deployment.
