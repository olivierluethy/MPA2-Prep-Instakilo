<p align="center">
  <img src="public/assets/logo.png" alt="Instakilo" width="80" height="80">
</p>

<h1 align="center">Instakilo</h1>

<p align="center">A small Instagram-style photo-sharing app — a modernized PHP MVC reference project.</p>

---

## Overview

Instakilo lets users share photo posts, follow each other, like and comment on
posts, and search for other users. Anonymous visitors see public posts; logged-in
users get a personalized feed (public posts + posts from people they follow + their
own), can upload multi-image posts (with a swipeable slider) and a rich-text
description, and manage their profile.

This repository is a **full modernization** of an older procedural PHP project. See
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for what changed and why.

### Technology stack

| Layer     | Technology                                            |
|-----------|-------------------------------------------------------|
| Backend   | PHP 8.2, custom MVC (no framework), PDO               |
| Database  | MySQL 8                                               |
| Frontend  | Tailwind CSS (compiled), vanilla JS, Quill editor     |
| Web server| Apache + `mod_rewrite` (document root = `public/`)    |
| Tooling   | Docker / Docker Compose, Node (Tailwind build only)   |

## Quick start (Docker)

The only requirement is Docker with the Compose plugin.

```bash
cp .env.example .env          # optional — sensible defaults are built in
docker compose up --build
```

Then open **http://localhost:8080**.

On first start the `assets` service compiles Tailwind and vendors Quill into
`public/`, and MySQL loads the schema + seed data from `db/init/`. Give it a few
seconds; the page styles itself once `public/css/app.css` has been written.

> Change the host port with `APP_PORT` in `.env` if `8080` is taken.

### Demo accounts

| Email             | Password   |
|-------------------|------------|
| `olivier@kauz.ch` | `kauz.git` |
| `test@test.ch`    | `kauz.git` |

## Project structure

```
.
├── app/
│   ├── Core/           # Framework: Router, Database, Controller, Model, Auth,
│   │                   #   Csrf, Validator, View, HtmlSanitizer, Request, ...
│   ├── Controllers/    # HTTP entry points (Home, Auth, Profile, Post,
│   │                   #   Follow, Search) — thin
│   ├── Models/         # Data access: User, Post, PostImage, Like, Follow, Comment
│   └── Views/          # Templates: layouts/, partials/, <page>/
├── config/
│   ├── config.php      # Single config source (reads env vars)
│   └── routes.php      # Route table
├── public/             # *** Web root *** — only this is web-accessible
│   ├── index.php       # Front controller
│   ├── .htaccess       # Rewrite + security headers
│   ├── css/app.css     # Built Tailwind (generated)
│   ├── js/             # theme.js, app.js, upload.js
│   ├── vendor/quill/   # Vendored Quill (generated)
│   └── assets/         # Images/icons
├── resources/css/      # Tailwind source (app.css)
├── db/init/            # Schema + seed (auto-loaded by MySQL)
├── docs/               # ARCHITECTURE.md, API.md
├── Dockerfile          # Multi-stage: Node asset build -> PHP/Apache
└── docker-compose.yml  # db + app + assets
```

## Local development (without Docker)

You need PHP 8.2+ (`pdo_mysql`, `mbstring`, `dom`), MySQL 8 and Node 20+.

```bash
# 1. Database
mysql -u root -p -e "CREATE DATABASE instakilo"
mysql -u root -p instakilo < db/init/01-schema.sql

# 2. Frontend assets
npm install
npm run build          # or: npm run watch  (rebuild on change)

# 3. Configure (env vars or a .env loaded into your shell)
export DB_HOST=127.0.0.1 DB_NAME=instakilo DB_USER=root DB_PASSWORD=

# 4. Serve with the document root at public/
php -S localhost:8080 -t public
```

> The built-in PHP server doesn't read `.htaccess`; routing still works because
> `index.php` is the directory index and the router reads the path from `?url=`.
> For pretty URLs locally, use Apache/Nginx with `public/` as the document root.

## Deployment (production)

1. Build the self-contained image (assets are compiled into it):
   ```bash
   docker build -t instakilo:latest .
   ```
2. Run it against a managed MySQL, supplying configuration via environment
   variables (`DB_*`, `APP_ENV=production`, `APP_DEBUG=false`). The image serves
   from `public/` on port 80.
3. Terminate TLS at a reverse proxy / load balancer and forward
   `X-Forwarded-Proto: https` so the session cookie is marked `Secure`.

For production hardening notes and known limitations, see
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## API

All endpoints, request/response formats and auth requirements are documented in
[`docs/API.md`](docs/API.md).

## Configuration reference

| Variable               | Default     | Description                          |
|------------------------|-------------|--------------------------------------|
| `APP_ENV`              | development | `development` or `production`        |
| `APP_DEBUG`            | true        | Show errors (set `false` in prod)    |
| `APP_PORT`             | 8080        | Host port for the app                |
| `APP_BASE_PATH`        | *(empty)*   | Sub-directory if not served at root  |
| `DB_HOST` / `DB_PORT`  | db / 3306   | Database host/port                   |
| `DB_NAME`              | instakilo   | Database name                        |
| `DB_USER` / `DB_PASSWORD` | instakilo | Database credentials              |
| `DB_ROOT_PASSWORD`     | rootsecret  | MySQL root password (container)      |
| `DB_PORT_HOST`         | 3307        | Host port exposing MySQL             |
| `UPLOAD_MAX_FILE_SIZE` | 5242880     | Max bytes per image                  |
| `UPLOAD_MAX_FILES`     | 10          | Max images per post                  |
