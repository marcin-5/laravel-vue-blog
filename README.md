# Laravel Blog (Inertia + Vue 3 + SSR)

A Laravel-based blog starter that uses Inertia.js with Vue 3, TypeScript, Tailwind CSS, and Vite. The project supports
Server-Side Rendering (SSR) for improved SEO and performance on public pages. It includes a simple public home page and
a blog with landing and post pages.

## Features

- Laravel 12 backend
- Inertia.js + Vue 3 frontend with TypeScript
- Vite for dev server and builds; Tailwind CSS v4
- SSR rendering pipeline for Inertia (see resources/js/ssr.ts)
- I18n with vue-i18n and SSR-safe namespace loading
- Docker-based development workflow with Makefile helpers
- Example blog pages with markdown-to-HTML processing (server side)

## Prerequisites

Choose your setup:

- Docker (recommended): Docker Engine and Docker Compose plugin
- Or local tools:
    - PHP 8.2+
    - Composer 2.5+
    - Node.js 18+ and npm 9+
    - A database (MySQL/MariaDB/PostgreSQL) or SQLite

## Quick Start (with Docker)

1. Copy env and install dependencies, then migrate DB:
    - make init
2. Start Vite HMR dev server:
    - make dev
3. Visit the app in your browser at the app container’s exposed port (see docker/docker-compose.dev.yml).
   Typically http://localhost:8000 for Laravel and http://localhost:5173 for Vite if proxied.

Useful Make targets:

- make help — list available targets
- make up — start containers (if not running)
- make down — stop containers
- make restart — restart containers
- make logs — follow logs
- make db-reset — migrate:fresh --seed
- make fish — shell into the app container

## Quick Start (local environment)

1. Copy env and generate key:
    - cp .env.example .env
    - composer install
    - php artisan key:generate
2. Configure database in .env, then run:
    - php artisan migrate
3. Install and run frontend:
    - npm install
    - npm run dev
4. Serve the app:
    - php artisan serve

Then open http://127.0.0.1:8000. The Vite dev server will run on http://127.0.0.1:5173.

## NPM Scripts

- npm run dev — start Vite dev server
- npm run build — build client assets
- npm run build:ssr — build client and SSR bundles
- npm run lint — ESLint (fix mode)
- npm run format — format code with Prettier (resources/)
- npm run format:check — check formatting

## SSR

- SSR entry: resources/js/ssr.ts
- You can review SSR notes in SSR_ANALYSIS.md
- Test helpers present at repository root:
    - test_ssr.php
    - test_welcome_ssr.php

These help validate that Inertia + Vue SSR renders without runtime errors.

## Project Structure (high-level)

- app/Http/Controllers
    - PublicHomeController.php — renders Welcome page
    - PublicBlogController.php — renders blog landing/post with server-prepared data
- resources/js
    - app.ts — client entry
    - ssr.ts — SSR entry
    - pages/Welcome.vue, pages/Blog/Landing.vue, pages/Blog/Post.vue
- routes/
    - web.php, public.php, settings.php
- Makefile — developer tasks (Docker-based)
- docker/ — compose files for dev
- vite.config.ts — Vite configuration

## Routes

Public routes are defined in routes/public.php and routes/web.php. Example pages:

- / — Welcome page (SSR-enabled)
- /blog — Blog landing
- /blog/{slug} — Blog post page

## Environment

Key .env settings to verify:

- APP_KEY (generated)
- APP_URL (e.g., http://localhost)
- DB_* variables
- VITE_* variables if needed

## Testing

- PHP: run vendor/bin/phpunit or php artisan test
- SSR sanity checks: php test_ssr.php and php test_welcome_ssr.php

## Formatting & Linting

- npm run lint — fix ESLint issues
- npm run format — Prettier formatting for resources/

## Production Build & Deploy

1. Build assets:
    - npm run build:ssr
2. Set APP_ENV=production, APP_DEBUG=false
3. Configure a web server (Nginx/Apache) to serve public/ and point PHP-FPM to the app
4. Run migrations and caches:
    - php artisan migrate --force
    - php artisan config:cache route:cache view:cache

## Troubleshooting

- If Vite HMR isn’t working, ensure APP_URL and VITE_URL (if used) are aligned and that vite config allows the host.
- When using Docker, ensure containers are running (make up) and check logs (make logs).
- For SSR issues, run the test scripts and check SSR_ANALYSIS.md.

## License

This project is provided as-is for demonstration and can be adapted to your preferred license.

---
Last updated: 2025-09-16
