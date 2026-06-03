# Instakilo API

The app is server-rendered; most routes return HTML. State-changing routes are
**POST-only** and **CSRF-protected**, and several also respond with JSON when the
request is made with AJAX (`X-Requested-With: XMLHttpRequest` or
`Accept: application/json`).

## Conventions

### CSRF

Every session has a token. It is rendered into a `<meta name="csrf-token">` tag and
into a hidden `_csrf_token` field in every form. For POST requests send it either:

- as the `_csrf_token` form field, or
- as the `X-CSRF-Token` request header.

Missing/invalid token → `403` (JSON) or a redirect back (HTML form).

### JSON envelope

```jsonc
// success
{ "success": true,  "data": { /* ... */ } }
// error
{ "success": false, "message": "Human readable reason", "errors": { "field": "..." } }
```

### Status codes

| Code | Meaning                                   |
|------|-------------------------------------------|
| 200  | OK                                        |
| 201  | Created (post upload)                     |
| 302  | Redirect (HTML flows)                     |
| 304  | Not Modified (image endpoints, ETag)      |
| 401  | Not authenticated                         |
| 403  | CSRF failure                              |
| 404  | Not found                                 |
| 405  | Method not allowed                        |
| 422  | Validation failed                         |

---

## Endpoints

### Feed

#### `GET /` · `GET /home`
Renders the feed. Anonymous → public posts. Authenticated → public + followed +
own posts, each flagged with `liked_by_me`. Ordered by like count, newest first.

### Authentication

#### `GET /login`
Login/registration page. Redirects to `/home` if already authenticated.

#### `POST /login`
| Field      | Required | Notes                          |
|------------|----------|--------------------------------|
| `login`    | yes      | Email **or** username          |
| `password` | yes      |                                |
| `_csrf_token` | yes   |                                |

On success: sets the session and redirects to `/home`. On failure: redirects back
to `/login` with a flash message (generic — no account enumeration).

#### `POST /register`
| Field      | Required | Notes                     |
|------------|----------|---------------------------|
| `username` | yes      | unique, ≤ 255 chars       |
| `email`    | yes      | valid, unique             |
| `password` | yes      | ≥ 6 chars                 |
| `verypass` | yes      | must equal `password`     |
| `_csrf_token` | yes   |                           |

On success: creates the account, auto-logs-in, redirects to `/home`.

#### `POST /logout`
CSRF-protected. Destroys the session, redirects to `/login`.

### Profile

#### `GET /profile`
Authenticated user's own profile + settings + their posts. `401`/redirect if not
logged in.

#### `GET /profile/visit?id={userId}`
Another user's profile. Shows public posts; private posts are included only if the
viewer follows them. `404` if the user doesn't exist. Visiting your own id
redirects to `/profile`.

#### `POST /profile/update`
Authenticated. Send only the section being changed:

| Field         | Notes                                   |
|---------------|-----------------------------------------|
| `username`    | unique, ≤ 255                           |
| `description` | ≤ 500 chars                             |
| `password` + `verypass` | ≥ 6, must match               |
| `avatar`      | file (jpeg/png/gif/webp, ≤ limit)       |

Redirects to `/profile` with a flash message.

#### `GET /avatar?id={userId}`
Streams the user's avatar (`image/*`) with caching headers. Falls back to the
default avatar asset when none is set.

### Follow

#### `POST /follow?id={userId}` · `POST /unfollow?id={userId}`
Authenticated, CSRF-protected, idempotent. JSON response:

```json
{ "success": true, "data": { "following": true, "followerCount": 3 } }
```

Non-AJAX requests redirect to the target's profile.

### Posts

#### `POST /posts/store`
Authenticated, CSRF-protected. `multipart/form-data`. Creates a post with one or
more images. Always returns JSON.

| Field         | Required | Notes                                            |
|---------------|----------|--------------------------------------------------|
| `title`       | yes      | ≤ 255                                            |
| `description` | no       | Rich-text HTML (Quill) — **sanitized** server-side |
| `location`    | no       | ≤ 255                                            |
| `taken_on`    | no       | `YYYY-MM-DD`                                     |
| `is_public`   | no       | `1` = public                                     |
| `images[]`    | yes      | 1..`UPLOAD_MAX_FILES` files; each ≤ limit; jpeg/png/gif/webp |
| `_csrf_token` | yes      |                                                  |

Success `201`:
```json
{ "success": true, "data": { "postId": 42, "redirect": "/home" } }
```
Validation failure `422`:
```json
{ "success": false, "message": "Bitte einen Titel eingeben.", "errors": { "title": "..." } }
```

Image MIME types are verified from file **contents** (`getimagesize`), not the
client-supplied type.

#### `POST /posts/like?id={postId}` · `POST /posts/unlike?id={postId}`
Authenticated, CSRF-protected, idempotent (UNIQUE constraint). JSON:
```json
{ "success": true, "data": { "liked": true, "likeCount": 5 } }
```

#### `GET /posts/image?id={imageId}`
Streams a post image blob with `Cache-Control` + `ETag` (supports `304`). `404`
if the image doesn't exist.

### Comments

Comment bodies are **plain text**: markup is stripped on write and the text is
escaped on output. Dates are returned pre-formatted (`"June 3, 2026, 21:14"`).

#### `POST /posts/comment?id={postId}`
Authenticated, CSRF-protected. Add a comment.

| Field         | Required | Notes              |
|---------------|----------|--------------------|
| `body`        | yes      | 1..1000 chars      |
| `_csrf_token` | yes      |                    |

Success `201`:
```json
{ "success": true, "data": {
  "comment": { "id": 7, "user_id": 1, "username": "LE FOU",
               "body": "Nice shot!", "created_at": "June 3, 2026, 21:14" },
  "commentCount": 4
} }
```
`422` empty/too long · `404` unknown post.

#### `GET /posts/comments?id={postId}&page={n}`
Public, read-only. Paginated comment list (10 per page, oldest first).

```json
{ "success": true, "data": {
  "comments": [ { "id": 7, "user_id": 1, "username": "LE FOU",
                  "body": "Nice shot!", "created_at": "June 3, 2026, 21:14" } ],
  "page": 1, "total": 4, "hasMore": false
} }
```

The feed/profile pages already embed a preview of the 2 most recent comments per
post (fetched in one batched window-function query — no N+1).

### Search

#### `GET /search?q={term}`
Public. Returns up to 8 users whose username contains `term` (case-insensitive).
The term is bound as a parameter and LIKE metacharacters are escaped, so it is
injection- and wildcard-safe. Empty `q` returns an empty list.

```json
{ "success": true, "data": { "users": [ { "id": 1, "username": "LE FOU" } ] } }
```

Each result links to `GET /profile/visit?id={id}`.
