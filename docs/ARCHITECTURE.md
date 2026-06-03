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

`users`, `posts`, `post_images` (many per post, ordered), `followers`, `likes`.
Foreign keys with `ON DELETE CASCADE`, and `UNIQUE` constraints make likes/follows
idempotent. Image bytes are stored as `LONGBLOB` and streamed via controllers.

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
- **No pagination** on the feed/profile yet — fine for the project's scale; add
  keyset pagination as data grows.
- **Search box** in the nav is a placeholder (it was non-functional originally too).
- **No automated test suite** — verification was done end-to-end against the running
  stack. Adding PHPUnit for models/sanitizer and a smoke test would harden CI.
- **Tailwind dev rebuilds** rely on the `assets` watcher service; the production
  image bakes the compiled CSS in.
- Rate limiting and email verification are out of scope but would be natural next
  steps for a real deployment.
