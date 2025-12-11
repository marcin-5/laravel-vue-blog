# Laravel Blog (Laravel 12 + Inertia v2 + Vue 3 + Tailwind v4 + SSR)

A Laravel-based blog starter that uses Inertia v2 with Vue 3, TypeScript, Tailwind CSS v4, and Vite. The project
supports Server-Side Rendering (SSR) for improved SEO and performance on public pages. It includes a public home page,
static pages, and a blog with landing and post pages.

## Features

- Laravel 12 backend (PHP 8.4)
- Inertia v2 + Vue 3 frontend with TypeScript
- Vite 7 for dev server and builds; Tailwind CSS v4
- SSR rendering pipeline for Inertia (see `resources/js/ssr.ts`)
- I18n with `vue-i18n` and SSR-safe namespace loading
- Docker-based development workflow with Makefile helpers
- Example blog pages with markdown-to-HTML processing (server side)
- Ziggy v2 for client-side `route()` helper
- Pest v3 test setup

## Prerequisites

Choose your setup:

- Docker (recommended): Docker Engine and Docker Compose plugin
- Or local tools:
    - PHP 8.4+
    - Composer 2.6+
    - Node.js 18+ and npm 9+
    - A database (MySQL/MariaDB/PostgreSQL) or SQLite

## Quick Start (with Docker)

1. First-time setup (copies `.env`, installs Composer & NPM deps, generates key, migrates, generates Ziggy):
    - `make init`
2. Start the Vite HMR dev server (containers must be up):
    - `make dev`
3. Open the app:
   - App: http://localhost:8000
   - Vite (HMR): http://localhost:5173

Useful Make targets:

- `make help` — list available targets
- `make up` — start containers (if not running)
- `make down` — stop containers
- `make restart` — restart containers
- `make logs` — follow logs
- `make db-reset` — `migrate:fresh --seed` then regenerate Ziggy
- `make fish` — shell into the app container

## Quick Start (local environment)

1. Copy env and install:
    - `cp .env.example .env`
    - `composer install`
    - `php artisan key:generate`
2. Configure database in `.env`, then run:
    - `php artisan migrate`
3. Frontend:
    - `npm install`
    - `npm run dev` (or `npm run build` for production assets)
4. Serve the app (if not using Valet):
    - `php artisan serve`

Then open http://127.0.0.1:8000. The Vite dev server will run on http://127.0.0.1:5173.

## NPM Scripts

- `npm run dev` — start Vite dev server
- `npm run build` — build client assets
- `npm run build:ssr` — build client and SSR bundles
- `npm run lint` — ESLint (fix mode)
- `npm run format` — format code with Prettier (resources/)
- `npm run format:check` — check formatting

Composer convenience scripts:

- `composer run dev` — runs queue listener, pail, and Vite concurrently for local dev
- `composer run dev:ssr` — also starts SSR server through Inertia

## SSR

- SSR entry: `resources/js/ssr.ts`
- Vite config points to SSR entry: see `vite.config.ts` (`ssr: 'resources/js/ssr.ts'`)
- For local SSR testing, you can use `composer run dev:ssr` to boot a basic SSR process

## Project Structure (high-level)

- `app/Http/Controllers`
    - `PublicHomeController.php` — renders Welcome, About, Contact pages
    - `PublicBlogController.php` — renders blog landing/post with server-prepared data
- `resources/js`
    - `app.ts` — client entry
    - `ssr.ts` — SSR entry
    - `pages/Welcome.vue`, `pages/Blog/Landing.vue`, `pages/Blog/Post.vue`
- `routes/`
    - `public.php`, `web.php`, `settings.php`
- `Makefile` — developer tasks (Docker-based)
- `docker/` — compose files for dev
- `vite.config.ts` — Vite configuration

## Routes

Public routes are defined in `routes/public.php` and `routes/web.php`. Example pages:

- `/` — Welcome page (SSR-enabled)
- `/about` — About page (SSR-enabled)
- `/contact` — Contact page (SSR-enabled)
- `/{blog:slug}` — Blog landing
- `/{blog:slug}/{postSlug}` — Blog post page
- `/sitemap.xml` — Sitemap
- `/robots.txt` — Robots

## Environment

Key .env settings to verify:

- `APP_KEY` (generated)
- `APP_URL` (e.g., http://localhost)
- `DB_*` variables
- `VITE_*` variables if needed

## Testing

- PHP (Pest): `php artisan test`
- You can also run `composer test`

## Formatting & Linting

- `vendor/bin/pint --dirty` — format PHP code to project standard
- `npm run lint` — fix ESLint issues
- `npm run format` — Prettier formatting for `resources/`

## Production Build & Deploy

1. Build assets:
    - `npm run build:ssr`
2. Set `APP_ENV=production`, `APP_DEBUG=false`
3. Configure a web server (Nginx/Apache) to serve `public/` and point PHP-FPM to the app
4. Run migrations and caches:
    - `php artisan migrate --force`
    - `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## Troubleshooting

- If Vite HMR isn’t working, ensure `APP_URL` and any `VITE_*` host config are aligned and that Vite allows the host.
- When using Docker, ensure containers are running (`make up`) and check logs (`make logs`).
- For SSR issues, build the SSR bundle (`npm run build:ssr`) and try `composer run dev:ssr` locally.

## License

This project is provided as-is for demonstration and can be adapted to your preferred license.

---
Last updated: 2025-12-11
