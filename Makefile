# Use a variable for the compose files to avoid repetition
COMPOSE_FILES = -f docker/docker-compose.yml -f docker/docker-compose.dev.yml
# Derive Docker Compose project name from current directory to keep it consistent
PROJECT_NAME = $(notdir $(CURDIR))-v2
# Always pass a fixed project name so we don't end up with multiple sets (e.g. running inside ./docker)
DOCKER_COMPOSE = docker compose --env-file .env -p $(PROJECT_NAME) $(COMPOSE_FILES)

# Default command to run when no target is specified
.DEFAULT_GOAL := help

# Use one shell per recipe to avoid multiline if/for syntax issues
.ONESHELL:
SHELL := /bin/sh

# Make output readability:
# - suppress "Entering/Leaving directory" even when make invokes make
MAKEFLAGS += --no-print-directory

# By default, `up` does not have any dependencies. This makes it fast.
BUILD_DEPENDENCY =

# For the special `init` target, we use a target-specific variable to override the
# above empty variable and inject `build` as a dependency for the `up` command.
init: BUILD_DEPENDENCY = build
init: migrate ## ✨ First-time project setup (builds, installs, migrates)
	@echo ""
	@echo "✅ Project initialized successfully!"
	@echo "➡️ Run 'make dev' to start the Vite HMR server or 'make fish' to get a shell."

# Phony targets are not files.
.PHONY: init dev build up down restart logs migrate db-seed db-reset key-gen npm-install composer-install fish setup-env help cache-clear

# ====================================================================================
# Main Commands
# ====================================================================================

help: ## 💬 Show this help message
	@echo "Usage: make [target]"
	@echo ""
	@echo "Available targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

dev: up ## 🚀 Start Vite dev server for hot-reloading (run after 'init')
	@echo "Starting Vite dev server... (Press Ctrl+C to stop)"
	$(DOCKER_COMPOSE) exec app npm run dev

# ====================================================================================
# Docker Commands
# ====================================================================================

up: $(BUILD_DEPENDENCY) ## ⬆️  Start development containers if they are not running
	@running="$$($(DOCKER_COMPOSE) ps -q)"; \
	if [ -n "$$running" ]; then \
		echo "Containers are already running."; \
	else \
		echo "Containers are not running. Starting them now..."; \
		$(DOCKER_COMPOSE) up -d --remove-orphans; \
		echo "Waiting for services to be ready..."; \
		sleep 5; \
	fi

down: ## ⬇️  Stop the development containers
	@echo "Stopping development environment..."
	$(DOCKER_COMPOSE) down

restart: down up ## 🔄 Restart the development containers

logs: ## 📜 View the logs from all running services
	@echo "Tailing logs..."
	$(DOCKER_COMPOSE) logs -f

build: setup-env ## 🔨 Build or rebuild the Docker services
	@echo "Building Docker images..."
	$(DOCKER_COMPOSE) build

# ====================================================================================
# Application Setup & Commands (chained dependencies)
# ====================================================================================

migrate: key-gen ## 🗄️ Run database migrations
	@echo "Running database migrations..."
	$(DOCKER_COMPOSE) exec app php artisan migrate
	@echo "Generating Ziggy routes..."
	$(DOCKER_COMPOSE) exec app php artisan ziggy:generate

db-seed: up ## 🌱 Seed the database with initial data
	@echo "Seeding the database..."
	$(DOCKER_COMPOSE) exec app php artisan db:seed

db-reset: up ## 🔄 Reset database and run all migrations from scratch
	@echo "Resetting database and running all migrations..."
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed
	@echo "Generating Ziggy routes..."
	$(DOCKER_COMPOSE) exec app php artisan ziggy:generate

key-gen: npm-install ## 🔑 Generate Laravel application key
	@echo "Generating application key..."
	$(DOCKER_COMPOSE) exec -T app php -r "file_exists('.env') && strpos(file_get_contents('.env'), 'APP_KEY=') === false && copy('.env.example', '.env');"
	$(DOCKER_COMPOSE) exec -T app php artisan key:generate

npm-install: composer-install ## 📦 Install JS dependencies with NPM
	@echo "Installing NPM dependencies..."
	$(DOCKER_COMPOSE) exec app npm install

composer-install: up ## 📦 Install PHP dependencies with Composer
	@echo "Installing Composer dependencies..."
	$(DOCKER_COMPOSE) exec app composer install

cache-clear: up ## 🧹 Clear all Laravel caches
	@echo "Clearing all Laravel caches..."
	$(DOCKER_COMPOSE) exec app php artisan optimize:clear

fish: up ## 🐟 Enter the app container with an interactive fish shell
	@echo "Entering the app container with fish shell..."
	$(DOCKER_COMPOSE) exec app fish

setup-env: ## 📝 Create .env file from .env.example if it doesn't exist
	@if [ ! -f .env ]; then \
		echo "Creating .env file from .env.example..."; \
		cp .env.example .env; \
	fi


# =============================
# Production Docker Compose
# =============================
COMPOSE_FILES_PROD = -f docker/docker-compose.yml -f docker/docker-compose.prod.yml
# Compatibility note:
# We intentionally pin the Docker Compose project name used for PRODUCTION targets
# to "docker" to preserve existing container and volume names on live servers
# (e.g. docker_public_files, docker_ssr_assets, docker_caddy_*), avoiding data
# loss or downtime from new, empty volumes being created when the repository
# directory name changes. If you intentionally plan to migrate names, override
# DOCKER_PROJECT_NAME_PROD at invocation time: `make DOCKER_PROJECT_NAME_PROD=laravel-vue-blog prod-update`.
DOCKER_PROJECT_NAME_PROD ?= docker
POSTGRES_IMAGE ?= postgres:15.15-alpine
REDIS_IMAGE ?= redis:7.4.7-alpine
PROD_BACKUP_DIR ?= /srv/laravel-blog/backups/pg-redis
PROD_REBUILD_TIMEOUT ?= 120
PROD_REBUILD_STOP_TIMEOUT ?= 30
PROD_REBUILD_CONFIRM ?= 0
ALLOW_POSTGRES_MAJOR_UPGRADE ?= 0
ALLOW_REDIS_MAJOR_UPGRADE ?= 0
# DOCKER_COMPOSE_PROD = docker compose --env-file .env -p $(PROJECT_NAME) $(COMPOSE_FILES_PROD)
DOCKER_COMPOSE_PROD = POSTGRES_IMAGE="$(POSTGRES_IMAGE)" REDIS_IMAGE="$(REDIS_IMAGE)" docker compose --env-file .env -p $(DOCKER_PROJECT_NAME_PROD) $(COMPOSE_FILES_PROD)
QUEUE_PAUSE_STATE_FILE ?= /tmp/laravel-blog-$(DOCKER_PROJECT_NAME_PROD)-queues-paused

.PHONY: prod-up prod-down prod-restart prod-build prod-logs \
        prod-migrate prod-optimize prod-deploy prod-update prod-wait \
        prod-maintenance-on prod-maintenance-off prod-rebuild-pg-redis \
        prod-rebuild-app-services prod-rebuild-caddy \
        prod-rebuild-pg-redis-preflight prod-backup-pg-redis \
        prod-prune prod-versions prod-check-assets prod-logs-queue prod-logs-app \
        prod-health-runtime prod-health-queue prod-queue-diag prod-queue-pause-all \
        prod-queue-continue-all prod-indexnow

prod-up: ## Start production services
	$(DOCKER_COMPOSE_PROD) up -d

prod-down: ## Stop production services
	$(DOCKER_COMPOSE_PROD) down

prod-restart: ## Restart production services
	$(DOCKER_COMPOSE_PROD) up -d

prod-build: ## Build/rebuild production application and SSR images
	$(DOCKER_COMPOSE_PROD) build app ssr

prod-logs: ## Tail production logs
	$(DOCKER_COMPOSE_PROD) logs -f

prod-logs-queue: ## Tail only queue container logs
	$(DOCKER_COMPOSE_PROD) logs -f queue

prod-logs-app: ## Tail only app container logs
	$(DOCKER_COMPOSE_PROD) logs -f app

# `--locale` may be omitted when the blog slug is unique across locales; use it when the slug is shared.
prod-indexnow: ## 🚀 Run IndexNow command in production (Examples: make prod-indexnow ARGS="blog-slug --locale=pl" or ARGS="blog-slug/about --locale=en")
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan blog:indexnow $(ARGS)

prod-indexnow-logs: ## 📜 Show recent IndexNow API response logs from production
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan blog:indexnow --logs

prod-queue-clear-logs: ## 🧹 Clear the queue worker log file
	$(DOCKER_COMPOSE_PROD) exec -T queue sh -c '> /var/www/html/storage/logs/supervisor_queue.log'
	@echo "✅ Queue log cleared."

# `queue:pause --all` and `queue:continue --all` are not available in older
# Laravel releases. Check the command and option before executing them so
# production deployments remain compatible with older application images.
prod-queue-pause-all: ## ⏸️ Pause processing on all production queues when supported
	@if $(DOCKER_COMPOSE_PROD) exec -T queue php artisan queue:pause --all --help >/dev/null 2>&1; then
		if $(DOCKER_COMPOSE_PROD) exec -T queue php artisan queue:pause --all; then
			if touch "$(QUEUE_PAUSE_STATE_FILE)"; then
				echo "✅ All production queues paused."
			else
				echo "❌ Could not record the queue pause state."; exit 1
			fi
		else
			echo "❌ Failed to pause production queues."; exit 1
		fi
	else
		echo "⚠️  queue:pause --all is not available in this Laravel version; skipping queue pause."
	fi

prod-queue-continue-all: ## ▶️ Resume processing on all production queues when supported
	@if [ ! -f "$(QUEUE_PAUSE_STATE_FILE)" ]; then
		echo "ℹ️  No queue pause recorded; skipping queue resume."
	elif $(DOCKER_COMPOSE_PROD) exec -T queue php artisan queue:continue --all --help >/dev/null 2>&1; then
		if $(DOCKER_COMPOSE_PROD) exec -T queue php artisan queue:continue --all; then
			if rm -f "$(QUEUE_PAUSE_STATE_FILE)"; then
				echo "✅ All production queues resumed."
			else
				echo "❌ Queues resumed, but the pause state could not be cleared."; exit 1
			fi
		else
			echo "❌ Failed to resume production queues; pause state was preserved."; exit 1
		fi
	else
		echo "⚠️  queue:continue --all is not available in this Laravel version; skipping queue resume."
	fi

prod-queue-diag: ## 🔍 Generate diagnostic data for queue worker debugging
	@echo "=== Queue Worker Diagnostics ==="
	@echo ""
	@echo "📦 Container status:"
	$(DOCKER_COMPOSE_PROD) ps queue
	@echo ""
	@echo "🔄 Queue monitor:"
	-$(DOCKER_COMPOSE_PROD) exec -T queue php artisan queue:monitor redis || echo "Failed to run queue:monitor"
	@echo ""
	@echo "⚙️ Laravel Queue Config:"
	-$(DOCKER_COMPOSE_PROD) exec -T queue php artisan config:show queue || echo "Failed to show queue config"
	@echo ""
	@echo "📋 Supervisor status:"
	-$(DOCKER_COMPOSE_PROD) exec -T --env SUPERVISOR_PASSWORD=$(SUPERVISOR_PASSWORD) queue sh -c 'supervisorctl -s unix:///var/run/supervisor.sock -u supervisor -p "$$SUPERVISOR_PASSWORD" status' || echo "supervisorctl not available"
	@echo ""
	@echo "🔧 Running processes:"
	-$(DOCKER_COMPOSE_PROD) exec -T queue ps aux | grep -E 'queue|supervisord|php.*artisan'
	@echo ""
	@echo "📜 Supervisor logs (last 50 lines):"
	-$(DOCKER_COMPOSE_PROD) exec -T queue tail -n 50 /var/log/supervisor/supervisord.log
	@echo ""
	@echo "📜 Queue worker logs (last 50 lines):"
	-$(DOCKER_COMPOSE_PROD) exec -T queue tail -n 50 /var/www/html/storage/logs/supervisor_queue.log 2>/dev/null || echo "No queue log file"
	@echo ""
	@echo "💡 Tip: If you see old errors above, you can clear the log file using:"
	@echo "   make prod-queue-clear-logs"
	@echo "   Note: Since 'storage' is a persistent volume, old logs stay there after restart."
	@echo ""
	@echo "🗄️ Database connection test:"
	-$(DOCKER_COMPOSE_PROD) exec -T queue php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" || echo "DB connection failed"
	@echo ""
	@echo "📡 Redis connection test:"
	-$(DOCKER_COMPOSE_PROD) exec -T queue php artisan tinker --execute="Illuminate\\Support\\Facades\\Redis::ping(); echo 'OK';" || echo "Redis connection failed"
	@echo ""
	@echo "=== End of Diagnostics ==="

prod-health-queue: ## Check health status of the queue worker container (uses Docker healthcheck)
 cid=$$($(DOCKER_COMPOSE_PROD) ps -q queue); \
	if [ -z "$$cid" ]; then \
	  echo "❌ queue container not found"; exit 1; \
	fi; \
	for i in `seq 1 12`; do \
	  status=$$(docker inspect -f '{{.State.Health.Status}}' $$cid 2>/dev/null || echo "unknown"); \
	  if [ "$$status" = "healthy" ]; then \
	    echo "✅ Queue worker is healthy."; \
	    exit 0; \
	  fi; \
	  if [ $$i -eq 12 ]; then \
	    echo "❌ Queue healthcheck failed after 120s ($$status)"; \
	    exit 1; \
	  fi; \
	  echo "Queue is $$status ($$i/12), waiting 10s..."; \
	  sleep 10; \
	done

prod-health-runtime: ## Wait for app, SSR and queue Docker healthchecks
	@set -eu; \
	deadline=$$(( $$(date +%s) + $(PROD_REBUILD_TIMEOUT) )); missing_healthcheck=0; \
	while :; do \
		all_healthy=1; details=''; \
		for service in app ssr queue; do \
			cid=$$($(DOCKER_COMPOSE_PROD) ps -q "$$service"); \
			if [ -z "$$cid" ]; then status=missing; else status=$$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' "$$cid" 2>/dev/null || echo unknown); fi; \
			details="$$details $$service=$$status"; \
			[ "$$status" = no-healthcheck ] && missing_healthcheck=1; \
			[ "$$status" = healthy ] || all_healthy=0; \
		done; \
		if [ "$$missing_healthcheck" -eq 1 ]; then echo "❌ Runtime healthchecks are missing:$$details. Recreate app, ssr and queue with the current Compose configuration." >&2; exit 1; fi; \
		if [ "$$all_healthy" -eq 1 ]; then echo "✅ Runtime is healthy:$$details"; exit 0; fi; \
		if [ "$$(date +%s)" -ge "$$deadline" ]; then echo "❌ Runtime healthchecks failed before timeout:$$details" >&2; exit 1; fi; \
		echo "⏳ Waiting for runtime healthchecks:$$details"; sleep 5; \
	done

prod-wait: ## Wait until the app container is ready to accept php exec
	@echo "Waiting for app container to be ready..."
	@for i in $$(seq 1 30); do \
		$(DOCKER_COMPOSE_PROD) exec -T app php -v >/dev/null 2>&1 && { echo "App is ready."; exit 0; }; \
		echo "App not ready yet ($$i/30), waiting..."; \
		sleep 5; \
		if [ $$i -eq 30 ]; then echo "App failed to become ready in time."; exit 1; fi; \
	 done

prod-migrate: ## Run DB migrations (force)
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan migrate --force

prod-optimize: ## Cache config/routes/views and generate Ziggy
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan config:cache
	-$(DOCKER_COMPOSE_PROD) exec -T app sh -lc 'php artisan route:cache || { echo "route:cache failed; falling back to route:clear"; php artisan route:clear; }'
	# Conditionally cache views only if resources/views exists
	-$(DOCKER_COMPOSE_PROD) exec -T app sh -lc "[ -d resources/views ] && php artisan view:cache || echo 'Skipping view:cache: resources/views not found'"
	-$(DOCKER_COMPOSE_PROD) exec -T app php artisan ziggy:generate

prod-check-assets: ## Verify built assets in app and SSR containers
	@set -eu; \
	$(DOCKER_COMPOSE_PROD) exec -T app sh -lc '
		for asset_dir in /var/www/html/bootstrap/ssr /var/www/html/public/build /var/www/html/public/img /var/www/html/public/pl /var/www/html/public/en; do
			if [ ! -d "$$asset_dir" ] || [ -z "$$(find "$$asset_dir" -mindepth 1 -maxdepth 1 -print -quit)" ]; then
				echo "❌ Missing or empty asset directory: $$asset_dir" >&2
				exit 1
			fi
		done
	'; \
	$(DOCKER_COMPOSE_PROD) exec -T ssr sh -lc '
		test -d /var/www/html/bootstrap/ssr && test -n "$$(find /var/www/html/bootstrap/ssr -mindepth 1 -maxdepth 1 -print -quit)"
	'

# Full deployment flow: pull code/images, rebuild, wait for app, optimize, migrate
prod-deploy: ## Build/Start prod, run optimizations & migrations
	# If building from source on the server, ensure latest code first:
	git pull --ff-only
	@echo "🧹 Clearing old bootstrap cache to prevent worker stuck..."
	rm -rf /srv/laravel-blog/bootstrap_cache/* 2>/dev/null || true
	$(DOCKER_COMPOSE_PROD) build app ssr
	$(DOCKER_COMPOSE_PROD) up -d
	$(MAKE) prod-health-runtime
	$(MAKE) prod-versions
	$(MAKE) prod-check-assets
	$(MAKE) prod-optimize
	$(MAKE) prod-migrate

# Shorthand target to update code and restart selected services
prod-versions: ## Show runtime versions for debugging (Node/NPM in SSR container, PHP in app)
	@echo ""
	@echo "🔎 Runtime versions:"
	@echo " - app:  PHP"
	$(DOCKER_COMPOSE_PROD) exec -T app php -v | head -n 1 || true
	@echo " - ssr:  Node / npm"
	$(DOCKER_COMPOSE_PROD) exec -T ssr node -v || true
	$(DOCKER_COMPOSE_PROD) exec -T ssr npm -v || true
	@echo ""

prod-ready: ## Check if the app is ready to handle requests (PHP-FPM/DB)
	@echo "⏳ Waiting for HTTP application endpoint to be reachable..."
	@for i in $$(seq 1 15); do \
	  if $(DOCKER_COMPOSE_PROD) exec -T app php artisan migrate:status >/dev/null 2>&1; then \
	    echo "✅ Application is ready (PHP-FPM/DB reachable)."; \
	    exit 0; \
	  fi; \
	  echo "⏳ App not ready yet ($$i/15); waiting..."; \
	  sleep 2; \
	done; \
	echo "❌ App failed to become ready in time."; exit 1

# Production deployment paths:
# - `prod-update` is the cached daily application deployment and does not rebuild Caddy.
# - `prod-rebuild-app-services` is for dependency/base-image changes requiring a fresh application build.
# - `prod-rebuild-caddy` is for Caddyfile, plugin, or Caddy image changes only.
prod-rebuild-app-services: ## Force a no-cache rebuild of the application services (app, ssr, queue, scheduler)
	@set -eu; \
	maintenance_enabled=0; queues_paused=0; rollback_attempted=0; migration_attempted=0; \
	rollback_file=$$(mktemp "$${TMPDIR:-/tmp}/laravel-blog-prod-rebuild.XXXXXX"); \
	rollback_prefix="laravel-blog-prod-rebuild-$$$$"; \
	fail() { printf '%s\n' "❌ [prod-rebuild-app-services] $$1" >&2; exit 1; }; \
	cleanup() { \
		rm -f "$$rollback_file"; \
		for service in app ssr queue scheduler; do docker image rm -f "$${rollback_prefix}-$$service" >/dev/null 2>&1 || true; done; \
	}; \
 verify_runtime() { \
		make prod-health-runtime || return 1; \
		make prod-check-assets || return 1; \
	}; \
	rollback_runtime() { \
		printf '%s\n' '↩️  Restoring the previous application runtime...'; \
		$(DOCKER_COMPOSE_PROD) -f "$$rollback_file" up -d --force-recreate --no-deps app ssr queue scheduler; \
		verify_runtime; \
	}; \
	on_exit() { \
		status=$$?; \
		trap - EXIT; \
		if [ "$$status" -eq 0 ]; then cleanup; return 0; fi; \
		if [ "$$maintenance_enabled" -eq 0 ]; then \
			printf '%s\n' '❌ Full application rebuild failed before maintenance; the running production was not switched.' >&2; \
			cleanup; return "$$status"; \
		fi; \
		if [ "$$migration_attempted" -eq 1 ]; then \
			printf '%s\n' '❌ Migration failed; maintenance remains enabled because the old runtime may not be compatible with the new schema.' >&2; \
			printf '%s\n' 'ℹ️  Diagnose with make prod-logs, make prod-logs-queue and make prod-queue-diag.' >&2; \
			cleanup; return "$$status"; \
		fi; \
		rollback_status=0; \
		if [ "$$rollback_attempted" -eq 0 ]; then \
			rollback_attempted=1; \
			set +e; rollback_runtime || rollback_status=$$?; \
			if [ "$$rollback_status" -eq 0 ] && [ "$$queues_paused" -eq 1 ]; then \
				make prod-queue-continue-all || rollback_status=$$?; \
				[ "$$rollback_status" -eq 0 ] && queues_paused=0; \
			fi; \
			if [ "$$rollback_status" -eq 0 ]; then \
				make prod-maintenance-off || rollback_status=$$?; \
				[ "$$rollback_status" -eq 0 ] && maintenance_enabled=0; \
			fi; \
			set -e; \
		fi; \
		if [ "$$rollback_status" -eq 0 ]; then \
			printf '%s\n' '✅ Previous application runtime restored and verified; maintenance disabled.' >&2; \
		else \
			printf '%s\n' '❌ Automatic rollback failed; maintenance remains enabled.' >&2; \
			printf '%s\n' 'ℹ️  Diagnose with make prod-logs, make prod-logs-queue and make prod-queue-diag, then rerun make prod-rebuild-app-services.' >&2; \
		fi; \
		cleanup; return "$$status"; \
	}; \
	trap on_exit EXIT; \
	printf '%s\n' '🔎 Validating production Compose and required running services...'; \
	$(DOCKER_COMPOSE_PROD) config --quiet || fail 'Production Compose configuration is invalid.'; \
	for service in app ssr queue scheduler caddy postgres redis; do \
		container_id=$$($(DOCKER_COMPOSE_PROD) ps -q "$$service"); \
		[ -n "$$container_id" ] || fail "Required '$$service' container does not exist or is not running."; \
	done; \
	[ ! -e "$(QUEUE_PAUSE_STATE_FILE)" ] || fail 'A previous queue pause is still recorded; inspect the queue before continuing.'; \
	app_container=$$($(DOCKER_COMPOSE_PROD) ps -q app); ssr_container=$$($(DOCKER_COMPOSE_PROD) ps -q ssr); queue_container=$$($(DOCKER_COMPOSE_PROD) ps -q queue); scheduler_container=$$($(DOCKER_COMPOSE_PROD) ps -q scheduler); \
	old_app_image=$$(docker inspect -f '{{.Image}}' "$$app_container"); old_ssr_image=$$(docker inspect -f '{{.Image}}' "$$ssr_container"); old_queue_image=$$(docker inspect -f '{{.Image}}' "$$queue_container"); old_scheduler_image=$$(docker inspect -f '{{.Image}}' "$$scheduler_container"); \
	for image in "$$old_app_image" "$$old_ssr_image" "$$old_queue_image" "$$old_scheduler_image"; do docker image inspect "$$image" >/dev/null || fail "Could not inspect rollback image '$$image'."; done; \
	printf '%s\n' '⬇️  Pulling the latest fast-forwardable revision...'; \
	git pull --ff-only; \
	printf '%s\n' '🔨 Building fresh application images without cache...'; \
	$(DOCKER_COMPOSE_PROD) build --no-cache --pull app ssr; \
	printf '%s\n' '💾 Recording rollback image references...'; \
	printf '%s\n' 'services:' > "$$rollback_file"; \
	for service in app ssr queue scheduler; do \
		case "$$service" in \
			app) old_image="$$old_app_image" ;; ssr) old_image="$$old_ssr_image" ;; queue) old_image="$$old_queue_image" ;; scheduler) old_image="$$old_scheduler_image" ;; \
		esac; \
		rollback_image="$${rollback_prefix}-$$service"; \
		docker tag "$$old_image" "$$rollback_image"; \
		printf '  %s:\n    image: %s\n' "$$service" "$$rollback_image" >> "$$rollback_file"; \
	done; \
	printf '%s\n' '🛠️  Enabling maintenance mode...'; \
	maintenance_enabled=1; \
	make prod-maintenance-on; \
	printf '%s\n' '⏸️  Pausing production queues when supported...'; \
	make prod-queue-pause-all; \
	if [ -f "$(QUEUE_PAUSE_STATE_FILE)" ]; then queues_paused=1; fi; \
	printf '%s\n' '🧹 Clearing old bootstrap cache to prevent stale workers...'; \
	rm -rf /srv/laravel-blog/bootstrap_cache/* 2>/dev/null || true; \
	printf '%s\n' '🚀 Recreating only application services...'; \
	$(DOCKER_COMPOSE_PROD) up -d --force-recreate --no-deps app ssr queue scheduler; \
	verify_runtime; \
	make prod-versions; \
	printf '%s\n' '🗄️  Running database migrations...'; \
	migration_attempted=1; \
	make prod-migrate; \
	printf '%s\n' '♻️  Re-caching the application...'; \
	make prod-optimize; \
	printf '%s\n' '▶️  Resuming production queues...'; \
	if [ "$$queues_paused" -eq 1 ]; then make prod-queue-continue-all; queues_paused=0; fi; \
	make prod-maintenance-off; \
	maintenance_enabled=0; \
	printf '%s\n' '✅ Full production application rebuild completed.'

prod-rebuild-caddy: ## Force a no-cache rebuild of Caddy without recreating application services
	@set -eu; \
	maintenance_enabled=0; rollback_attempted=0; \
	rollback_file=$$(mktemp "$${TMPDIR:-/tmp}/laravel-blog-caddy-rollback.XXXXXX"); \
	maintenance_file=$$(mktemp "$${TMPDIR:-/tmp}/laravel-blog-caddy-maintenance.XXXXXX"); \
	normal_file=$$(mktemp "$${TMPDIR:-/tmp}/laravel-blog-caddy-normal.XXXXXX"); \
	rollback_image="laravel-blog-caddy-rollback-$$$$"; \
	fail() { printf '%s\n' "❌ [prod-rebuild-caddy] $$1" >&2; exit 1; }; \
	cleanup() { \
		rm -f "$$rollback_file" "$$maintenance_file" "$$normal_file"; \
		docker image rm -f "$$rollback_image" >/dev/null 2>&1 || true; \
	}; \
	verify_caddy() { \
		cid=$$($(DOCKER_COMPOSE_PROD) ps -q caddy) || return 1; \
		[ -n "$$cid" ] || return 1; \
		[ "$$(docker inspect -f '{{.State.Running}}' "$$cid" 2>/dev/null)" = 'true' ] || return 1; \
		$(DOCKER_COMPOSE_PROD) exec -T caddy caddy validate --config /etc/caddy/Caddyfile || return 1; \
	}; \
	rollback_runtime() { \
		printf '%s\n' '↩️  Restoring the previous Caddy runtime...'; \
		$(DOCKER_COMPOSE_PROD) -f "$$rollback_file" up -d --force-recreate --no-deps caddy || return 1; \
		cid=$$($(DOCKER_COMPOSE_PROD) -f "$$rollback_file" ps -q caddy) || return 1; \
		[ "$$(docker inspect -f '{{.State.Running}}' "$$cid" 2>/dev/null)" = 'true' ] || return 1; \
		$(DOCKER_COMPOSE_PROD) -f "$$rollback_file" exec -T caddy caddy validate --config /etc/caddy/Caddyfile || return 1; \
		$(DOCKER_COMPOSE_PROD) -f "$$rollback_file" -f "$$normal_file" up -d --force-recreate --no-deps caddy || return 1; \
		$(DOCKER_COMPOSE_PROD) -f "$$rollback_file" -f "$$normal_file" exec -T caddy caddy validate --config /etc/caddy/Caddyfile || return 1; \
		make prod-maintenance-off || return 1; \
	}; \
	on_exit() { \
		status=$$?; \
		trap - EXIT; \
		if [ "$$status" -eq 0 ]; then cleanup; return 0; fi; \
		if [ "$$maintenance_enabled" -eq 0 ]; then \
			printf '%s\n' '❌ Caddy rebuild failed before maintenance; the running production was not switched.' >&2; \
			cleanup; return "$$status"; \
		fi; \
		rollback_status=0; \
		if [ "$$rollback_attempted" -eq 0 ]; then \
			rollback_attempted=1; \
			set +e; rollback_runtime || rollback_status=$$?; set -e; \
		fi; \
		if [ "$$rollback_status" -eq 0 ]; then \
			maintenance_enabled=0; \
			printf '%s\n' '✅ Previous Caddy runtime restored and verified; maintenance disabled.' >&2; \
		else \
			printf '%s\n' '❌ Automatic Caddy rollback failed; maintenance remains enabled.' >&2; \
			printf '%s\n' 'ℹ️  Diagnose with make prod-logs and rerun make prod-rebuild-caddy after fixing the issue.' >&2; \
		fi; \
		cleanup; return "$$status"; \
	}; \
	trap on_exit EXIT; \
	printf '%s\n' '🔎 Validating production Compose and the running Caddy service...'; \
	$(DOCKER_COMPOSE_PROD) config --quiet || fail 'Production Compose configuration is invalid.'; \
	caddy_container=$$($(DOCKER_COMPOSE_PROD) ps -q caddy); \
	[ -n "$$caddy_container" ] || fail 'Caddy container does not exist or is not running.'; \
	[ "$$(docker inspect -f '{{.State.Running}}' "$$caddy_container" 2>/dev/null)" = 'true' ] || fail 'Caddy container is not running.'; \
	$(DOCKER_COMPOSE_PROD) exec -T caddy caddy validate --config /etc/caddy/Caddyfile || fail 'The current normal Caddyfile is invalid.'; \
	old_caddy_image=$$(docker inspect -f '{{.Image}}' "$$caddy_container"); \
	docker image inspect "$$old_caddy_image" >/dev/null || fail 'Could not inspect the current Caddy image for rollback.'; \
	printf '%s\n' '⬇️  Pulling the latest fast-forwardable revision...'; \
	git pull --ff-only; \
	printf '%s\n' '🔨 Building the Caddy image without cache...'; \
	$(DOCKER_COMPOSE_PROD) build --no-cache --pull caddy; \
	docker tag "$$old_caddy_image" "$$rollback_image"; \
	printf '%s\n' 'services:' > "$$rollback_file"; \
	printf '  caddy:\n    image: %s\n    volumes:\n      - ./Caddyfile.maintenance:/etc/caddy/Caddyfile:ro\n' "$$rollback_image" >> "$$rollback_file"; \
	printf '%s\n' 'services:' > "$$maintenance_file"; \
	printf '  caddy:\n    volumes:\n      - ./Caddyfile.maintenance:/etc/caddy/Caddyfile:ro\n' >> "$$maintenance_file"; \
	printf '%s\n' 'services:' > "$$normal_file"; \
	printf '%s\n' '  caddy:' '    volumes:' '      - ./Caddyfile:/etc/caddy/Caddyfile:ro' >> "$$normal_file"; \
	printf '%s\n' '🛠️  Enabling maintenance mode before replacing Caddy...'; \
	maintenance_enabled=1; \
	make prod-maintenance-on; \
	printf '%s\n' '🚀 Recreating only the Caddy service with its maintenance configuration...'; \
	$(DOCKER_COMPOSE_PROD) -f "$$maintenance_file" up -d --force-recreate --no-deps caddy; \
	$(DOCKER_COMPOSE_PROD) -f "$$maintenance_file" exec -T caddy caddy validate --config /etc/caddy/Caddyfile; \
	caddy_container=$$($(DOCKER_COMPOSE_PROD) -f "$$maintenance_file" ps -q caddy); \
	[ "$$(docker inspect -f '{{.State.Running}}' "$$caddy_container" 2>/dev/null)" = 'true' ] || fail 'The rebuilt Caddy container is not running.'; \
	printf '%s\n' '🔗 Restoring and validating the normal Caddyfile...'; \
	$(DOCKER_COMPOSE_PROD) -f "$$normal_file" up -d --force-recreate --no-deps caddy; \
	$(DOCKER_COMPOSE_PROD) -f "$$normal_file" exec -T caddy caddy validate --config /etc/caddy/Caddyfile; \
	make prod-maintenance-off; \
	maintenance_enabled=0; \
	printf '%s\n' '✅ Caddy-only production rebuild completed.'

# Shorthand target to update code and restart selected services using cached images
prod-update: ## Update code from Git and restart selected services with zero-502 maintenance
	git pull --ff-only
	@echo "🔨 Building cached images for application services..."
	$(DOCKER_COMPOSE_PROD) build app ssr
	$(MAKE) prod-maintenance-on
	$(MAKE) prod-queue-pause-all
	@echo "🧹 Clearing old bootstrap cache to prevent worker stuck..."
	rm -rf /srv/laravel-blog/bootstrap_cache/* 2>/dev/null || true
	@echo "🔧 Clearing Laravel caches before recreate..."
	-$(DOCKER_COMPOSE_PROD) exec -T app php artisan optimize:clear || true
	-$(DOCKER_COMPOSE_PROD) exec -T app php artisan package:discover --ansi || true
	@echo "🚀 Recreating core services without touching caddy..."
	$(DOCKER_COMPOSE_PROD) up -d --force-recreate --no-deps app ssr queue scheduler
	$(MAKE) prod-health-runtime
	$(MAKE) prod-versions
	@echo ""
	@echo "🔍 Checking production assets..."
	$(MAKE) prod-check-assets
	@echo ""
	@echo ""
	@echo "🗄️  Running database migrations..."
	$(MAKE) prod-migrate
	@echo ""
	@echo "♻️  Re-caching configuration..."
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan config:cache
	@echo ""
	$(MAKE) prod-queue-continue-all
	$(MAKE) prod-maintenance-off
	@echo ""
	@echo "✅ Production update complete."

# Shorthand target to update application data only
prod-update-data: ## Pull code and rebuild only the app container for data/code updates
	git pull --ff-only
	@echo "🔨 Building fresh image for app service..."
	$(DOCKER_COMPOSE_PROD) build app
	$(MAKE) prod-maintenance-on
	$(MAKE) prod-queue-pause-all
	@echo "🚀 Recreating app service..."
	$(DOCKER_COMPOSE_PROD) up -d --force-recreate --no-deps app
	$(MAKE) prod-wait
	$(MAKE) prod-ready
	@echo "🧹 Clearing Laravel caches..."
	-$(DOCKER_COMPOSE_PROD) exec -T app php artisan optimize:clear
	$(MAKE) prod-queue-continue-all
	$(MAKE) prod-maintenance-off
	@echo "✅ Data update complete."

# =============================
# Rebuild Postgres & Redis (production)
# make POSTGRES_IMAGE=postgres:15.16-alpine REDIS_IMAGE=redis:7.4.7-alpine PROD_REBUILD_CONFIRM=1 prod-rebuild-pg-redis
# =============================
prod-rebuild-pg-redis: prod-rebuild-pg-redis-preflight ## Recreate postgres and redis services with zero-502 maintenance window
	@set -eu; \
	maintenance_enabled=0; \
	dependencies_stopped=0; \
	maintenance_on() { \
		printf '%s\n' '[prod-rebuild-pg-redis] Ensuring maintenance.html exists...'; \
		$(DOCKER_COMPOSE_PROD) exec -T app sh -lc 'test -f /var/www/html/public/maintenance.html || printf %s "<!doctype html><title>Maintenance</title><h1>Trwa aktualizacja…</h1>" > /var/www/html/public/maintenance.html'; \
		cid=$$($(DOCKER_COMPOSE_PROD) ps -q caddy); \
		[ -n "$$cid" ] || { printf '%s\n' '[prod-rebuild-pg-redis] Caddy container not found.' >&2; return 1; }; \
		[ "$$(docker inspect -f '{{.State.Running}}' "$$cid" 2>/dev/null)" = 'true' ] || { printf '%s\n' '[prod-rebuild-pg-redis] Caddy container is not running.' >&2; return 1; }; \
		docker cp docker/Caddyfile.maintenance "$$cid:/etc/caddy/Caddyfile.maintenance"; \
		$(DOCKER_COMPOSE_PROD) exec -T caddy caddy validate --config /etc/caddy/Caddyfile.maintenance; \
		$(DOCKER_COMPOSE_PROD) exec -T caddy caddy reload --config /etc/caddy/Caddyfile.maintenance; \
	}; \
	create_backup() { \
		timestamp=$$(date -u +%Y%m%dT%H%M%SZ); \
		postgres_container=$$($(DOCKER_COMPOSE_PROD) ps -q postgres); redis_container=$$($(DOCKER_COMPOSE_PROD) ps -q redis); \
		[ -n "$$postgres_container" ] && [ -n "$$redis_container" ] || { printf '%s\n' '❌ Both PostgreSQL and Redis containers must be running for backup.' >&2; return 1; }; \
		umask 077; \
		postgres_backup="$(PROD_BACKUP_DIR)/postgres-$$timestamp.dump"; postgres_tmp="$$postgres_backup.tmp"; \
		if ! $(DOCKER_COMPOSE_PROD) exec -T postgres sh -lc 'PGPASSWORD="$${POSTGRES_PASSWORD}" pg_dump --format=custom --no-owner --no-privileges --username="$${POSTGRES_USER}" "$${POSTGRES_DB}"' > "$$postgres_tmp"; then rm -f "$$postgres_tmp"; printf '%s\n' '❌ PostgreSQL logical dump failed.' >&2; return 1; fi; \
		if [ ! -s "$$postgres_tmp" ]; then rm -f "$$postgres_tmp"; printf '%s\n' '❌ PostgreSQL logical dump is empty.' >&2; return 1; fi; \
		mv "$$postgres_tmp" "$$postgres_backup"; \
		redis_result=$$($(DOCKER_COMPOSE_PROD) exec -T redis redis-cli --raw BGSAVE) || { printf '%s\n' '❌ Redis BGSAVE failed.' >&2; return 1; }; \
		case "$$redis_result" in *'Background saving started'*|*'Background saving terminated'*) ;; *) printf '%s\n' "❌ Redis returned an unexpected BGSAVE response: $$redis_result" >&2; return 1 ;; esac; \
		deadline=$$(( $$(date +%s) + $(PROD_REBUILD_TIMEOUT) )); \
		while :; do \
			redis_persistence=$$($(DOCKER_COMPOSE_PROD) exec -T redis redis-cli --raw INFO persistence) || { printf '%s\n' '❌ Could not read Redis snapshot status.' >&2; return 1; }; \
		redis_in_progress=$$(printf '%s\n' "$$redis_persistence" | tr -d '\r' | sed -n 's/^rdb_bgsave_in_progress://p'); redis_last_status=$$(printf '%s\n' "$$redis_persistence" | tr -d '\r' | sed -n 's/^rdb_last_bgsave_status://p'); \
			if [ "$$redis_in_progress" = '0' ] && [ "$$redis_last_status" = 'ok' ]; then break; fi; \
			[ "$$(date +%s)" -lt "$$deadline" ] || { printf '%s\n' '❌ Redis BGSAVE did not complete successfully before the timeout.' >&2; return 1; }; sleep 1; \
		done; \
		redis_backup="$(PROD_BACKUP_DIR)/redis-$$timestamp.rdb"; redis_tmp="$$redis_backup.tmp"; \
		if ! docker cp "$${redis_container}:/data/dump.rdb" "$$redis_tmp"; then rm -f "$$redis_tmp"; printf '%s\n' '❌ Could not copy the Redis RDB snapshot from the container.' >&2; return 1; fi; \
		if [ ! -s "$$redis_tmp" ]; then rm -f "$$redis_tmp"; printf '%s\n' '❌ Redis RDB snapshot is empty.' >&2; return 1; fi; \
		mv "$$redis_tmp" "$$redis_backup"; \
		printf '%s\n' "✅ PostgreSQL backup: $$postgres_backup"; printf '%s\n' "✅ Redis snapshot: $$redis_backup"; \
	}; \
	maintenance_off() { \
		cid=$$($(DOCKER_COMPOSE_PROD) ps -q caddy); \
		[ -n "$$cid" ] || { printf '%s\n' '[prod-rebuild-pg-redis] Caddy container not found while disabling maintenance.' >&2; return 1; }; \
		[ "$$(docker inspect -f '{{.State.Running}}' "$$cid" 2>/dev/null)" = 'true' ] || { printf '%s\n' '[prod-rebuild-pg-redis] Caddy container is not running while disabling maintenance.' >&2; return 1; }; \
		$(DOCKER_COMPOSE_PROD) exec -T caddy caddy validate --config /etc/caddy/Caddyfile; \
		$(DOCKER_COMPOSE_PROD) exec -T caddy caddy reload --config /etc/caddy/Caddyfile; \
	}; \
	on_exit() { \
		status=$$?; \
		if [ "$$status" -ne 0 ]; then \
			if [ "$$maintenance_enabled" -eq 1 ]; then \
				printf '%s\n' '❌ Production database rebuild failed; maintenance remains enabled.' >&2; \
			else \
				printf '%s\n' '❌ Production database rebuild failed before maintenance was confirmed active.' >&2; \
			fi; \
			if [ "$$dependencies_stopped" -eq 1 ]; then \
				printf '%s\n' "⚠️  queue and scheduler were not restarted automatically." >&2; \
			fi; \
			printf '%s\n' "ℹ️  Backups are stored in $(PROD_BACKUP_DIR)." >&2; \
		fi; \
		return "$$status"; \
	}; \
	trap on_exit EXIT; \
	printf '%s\n' '🛠️  Enabling and verifying maintenance mode...'; \
	maintenance_on; \
	maintenance_enabled=1; \
	printf '%s\n' '⏹️  Gracefully stopping scheduler and queue workers...'; \
	$(DOCKER_COMPOSE_PROD) stop --timeout=$(PROD_REBUILD_STOP_TIMEOUT) scheduler queue; \
	dependencies_stopped=1; \
	for service in scheduler queue; do \
		container_id=$$($(DOCKER_COMPOSE_PROD) ps -aq "$$service"); \
		if [ -z "$$container_id" ] || [ "$$(docker inspect -f '{{.State.Running}}' "$$container_id" 2>/dev/null)" = 'true' ]; then \
			printf '%s\n' "❌ Service '$$service' did not stop cleanly." >&2; \
			exit 1; \
		fi; \
	done; \
	printf '%s\n' '💾 Creating PostgreSQL and Redis backups...'; \
	create_backup; \
	printf '%s\n' '⬇️  Pulling target PostgreSQL and Redis images...'; \
	$(DOCKER_COMPOSE_PROD) pull postgres redis; \
	printf '%s\n' '♻️  Recreating PostgreSQL and Redis without touching other services or volumes...'; \
	$(DOCKER_COMPOSE_PROD) up -d --force-recreate --no-deps postgres redis; \
	deadline=$$(( $$(date +%s) + $(PROD_REBUILD_TIMEOUT) )); \
	while :; do \
		all_healthy=1; \
		for service in postgres redis; do \
			container_id=$$($(DOCKER_COMPOSE_PROD) ps -q "$$service"); \
			status=$$(docker inspect -f '{{.State.Health.Status}}' "$$container_id" 2>/dev/null || printf '%s' unknown); \
			printf '%s\n' "$$service health: $$status"; \
			if [ "$$status" != 'healthy' ]; then all_healthy=0; fi; \
		done; \
		if [ "$$all_healthy" -eq 1 ]; then break; fi; \
		if [ "$$(date +%s)" -ge "$$deadline" ]; then \
			printf '%s\n' '❌ PostgreSQL or Redis did not become healthy before the timeout.' >&2; \
			exit 1; \
		fi; \
		sleep 2; \
	done; \
	printf '%s\n' '🔌 Verifying database and Redis connectivity from the app container...'; \
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan migrate:status >/dev/null; \
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan tinker --execute='Illuminate\\Support\\Facades\\Redis::connection()->ping();' >/dev/null; \
	printf '%s\n' '🚀 Starting scheduler and queue workers after readiness checks...'; \
	$(DOCKER_COMPOSE_PROD) up -d --no-deps scheduler queue; \
	for service in scheduler queue; do \
		container_id=$$($(DOCKER_COMPOSE_PROD) ps -q "$$service"); \
		if [ -z "$$container_id" ] || [ "$$(docker inspect -f '{{.State.Running}}' "$$container_id" 2>/dev/null)" != 'true' ]; then \
			printf '%s\n' "❌ Service '$$service' failed to start; maintenance remains enabled." >&2; \
			exit 1; \
		fi; \
	done; \
	printf '%s\n' '✅ PostgreSQL, Redis and application connectivity checks passed.'; \
	maintenance_off; \
	maintenance_enabled=0; \
	printf '%s\n' '✅ Production PostgreSQL and Redis rebuild completed.'

prod-rebuild-pg-redis-preflight: ## Validate production rebuild confirmation, versions, services, volumes and images
	@set -eu; \
	fail() { \
		printf '%s\n' "❌ [prod-rebuild-pg-redis-preflight] $$1" >&2; \
		exit 1; \
	}; \
	if [ "$(PROD_REBUILD_CONFIRM)" != "1" ]; then fail 'Production rebuild is blocked. Re-run with PROD_REBUILD_CONFIRM=1.'; fi; \
	if ! $(DOCKER_COMPOSE_PROD) config --quiet; then fail 'Production Compose configuration is invalid.'; fi; \
	for image in "$(POSTGRES_IMAGE)" "$(REDIS_IMAGE)"; do \
		case "$$image" in \
			latest|*:latest|'') fail "Image '$$image' must use an explicit tag or digest; latest is not allowed." ;; \
			*@sha256:*) ;; \
			*@*) fail "Image '$$image' must use a sha256 digest when using a digest reference." ;; \
			*/*) \
				image_name=$${image##*/}; \
				case "$$image_name" in \
					*:*) ;; \
					*) fail "Image '$$image' must use an explicit tag or digest." ;; \
				esac; \
				;; \
			*:*) ;; \
			*) fail "Image '$$image' must use an explicit tag or digest." ;; \
		esac; \
	done; \
	for service in postgres redis app caddy queue scheduler; do \
		container_id=$$($(DOCKER_COMPOSE_PROD) ps -aq "$$service") || fail "Could not inspect the required '$$service' service."; \
		[ -n "$$container_id" ] || fail "Required '$$service' container does not exist."; \
		[ "$$(docker inspect -f '{{.State.Running}}' "$$container_id" 2>/dev/null)" = 'true' ] || fail "Required '$$service' service is not running."; \
	done; \
	for volume in docker_postgres_data docker_redis_data; do \
		docker volume inspect "$$volume" >/dev/null 2>&1 || fail "Required external volume '$$volume' does not exist."; \
	done; \
	if ! mkdir -p "$(PROD_BACKUP_DIR)" || [ ! -w "$(PROD_BACKUP_DIR)" ]; then fail "Backup directory '$(PROD_BACKUP_DIR)' is not writable."; fi; \
	printf '%s\n' '⬇️  Pulling target PostgreSQL and Redis images...'; \
	$(DOCKER_COMPOSE_PROD) pull postgres redis || fail 'Could not pull the target PostgreSQL and Redis images.'; \
	postgres_running_version=$$($(DOCKER_COMPOSE_PROD) exec -T postgres sh -lc 'PGPASSWORD="$${POSTGRES_PASSWORD}" psql -U "$${POSTGRES_USER}" -d "$${POSTGRES_DB}" -Atqc "SHOW server_version"') || fail 'Could not read the running PostgreSQL version.'; \
	redis_running_version=$$($(DOCKER_COMPOSE_PROD) exec -T redis sh -lc "redis-cli --raw INFO server | sed -n 's/^redis_version://p' | head -n 1") || fail 'Could not read the running Redis version.'; \
	postgres_target_version=$$(docker run --rm --entrypoint postgres "$(POSTGRES_IMAGE)" --version) || fail 'Could not read the PostgreSQL version from the target image.'; \
	redis_target_version=$$(docker run --rm --entrypoint redis-server "$(REDIS_IMAGE)" --version) || fail 'Could not read the Redis version from the target image.'; \
	version_parts() { printf '%s\n' "$$1" | sed -nE 's/^[^0-9]*([0-9]+)\.([0-9]+)(\.([0-9]+))?.*/\1 \2 \4/p' | awk '{ printf "%s %s %s\n", $$1, $$2, ($$3 == "" ? 0 : $$3) }'; }; \
	postgres_running_parts=$$(version_parts "$$postgres_running_version") || fail "The running PostgreSQL version '$$postgres_running_version' is not parseable."; \
	postgres_target_parts=$$(version_parts "$$postgres_target_version") || fail "The target PostgreSQL version '$$postgres_target_version' is not parseable."; \
	redis_running_parts=$$(version_parts "$$redis_running_version") || fail "The running Redis version '$$redis_running_version' is not parseable."; \
	redis_target_parts=$$(version_parts "$$redis_target_version") || fail "The target Redis version '$$redis_target_version' is not parseable."; \
	[ -n "$$postgres_running_parts" ] || fail "The running PostgreSQL version '$$postgres_running_version' is not parseable."; \
	[ -n "$$postgres_target_parts" ] || fail "The target PostgreSQL version '$$postgres_target_version' is not parseable."; \
	[ -n "$$redis_running_parts" ] || fail "The running Redis version '$$redis_running_version' is not parseable."; \
	[ -n "$$redis_target_parts" ] || fail "The target Redis version '$$redis_target_version' is not parseable."; \
	postgres_running_major=$$(printf '%s\n' "$$postgres_running_parts" | awk '{ print $$1 }'); postgres_running_minor=$$(printf '%s\n' "$$postgres_running_parts" | awk '{ print $$2 }'); postgres_running_patch=$$(printf '%s\n' "$$postgres_running_parts" | awk '{ print $$3 }'); \
	postgres_target_major=$$(printf '%s\n' "$$postgres_target_parts" | awk '{ print $$1 }'); postgres_target_minor=$$(printf '%s\n' "$$postgres_target_parts" | awk '{ print $$2 }'); postgres_target_patch=$$(printf '%s\n' "$$postgres_target_parts" | awk '{ print $$3 }'); \
	redis_running_major=$$(printf '%s\n' "$$redis_running_parts" | awk '{ print $$1 }'); redis_running_minor=$$(printf '%s\n' "$$redis_running_parts" | awk '{ print $$2 }'); redis_running_patch=$$(printf '%s\n' "$$redis_running_parts" | awk '{ print $$3 }'); \
	redis_target_major=$$(printf '%s\n' "$$redis_target_parts" | awk '{ print $$1 }'); redis_target_minor=$$(printf '%s\n' "$$redis_target_parts" | awk '{ print $$2 }'); redis_target_patch=$$(printf '%s\n' "$$redis_target_parts" | awk '{ print $$3 }'); \
	printf '%s\n' "PostgreSQL running: $$postgres_running_version (major=$$postgres_running_major minor=$$postgres_running_minor patch=$$postgres_running_patch)"; \
	printf '%s\n' "PostgreSQL target:  $$postgres_target_version (major=$$postgres_target_major minor=$$postgres_target_minor patch=$$postgres_target_patch)"; \
	printf '%s\n' "Redis running:      $$redis_running_version (major=$$redis_running_major minor=$$redis_running_minor patch=$$redis_running_patch)"; \
	printf '%s\n' "Redis target:       $$redis_target_version (major=$$redis_target_major minor=$$redis_target_minor patch=$$redis_target_patch)"; \
	major_upgrade_blocked=0; \
	if [ "$$postgres_running_major" != "$$postgres_target_major" ]; then \
		if [ "$(ALLOW_POSTGRES_MAJOR_UPGRADE)" != '1' ]; then printf '%s\n' "❌ PostgreSQL major upgrade $$postgres_running_major.x -> $$postgres_target_major.x is blocked. Set ALLOW_POSTGRES_MAJOR_UPGRADE=1 only after checking compatibility and migration requirements." >&2; major_upgrade_blocked=1; else printf '%s\n' '⚠️  PostgreSQL major upgrade explicitly allowed; compatibility and data migration remain the operator’s responsibility.' >&2; fi; \
	fi; \
	if [ "$$redis_running_major" != "$$redis_target_major" ]; then \
		if [ "$(ALLOW_REDIS_MAJOR_UPGRADE)" != '1' ]; then printf '%s\n' "❌ Redis major upgrade $$redis_running_major.x -> $$redis_target_major.x is blocked. Set ALLOW_REDIS_MAJOR_UPGRADE=1 only after checking compatibility and migration requirements." >&2; major_upgrade_blocked=1; else printf '%s\n' '⚠️  Redis major upgrade explicitly allowed; compatibility and data migration remain the operator’s responsibility.' >&2; fi; \
	fi; \
	[ "$$major_upgrade_blocked" -eq 0 ] || fail 'Version gate failed; no maintenance or service stop was performed.'; \
	printf '%s\n' '✅ Preflight passed; same-major updates are allowed and explicit major opt-ins were honored.'

prod-backup-pg-redis: ## Create a PostgreSQL logical dump and Redis RDB snapshot
	@set -eu; \
	fail() { printf '%s\n' "❌ [prod-backup-pg-redis] $$1" >&2; exit 1; }; \
	[ "$(PROD_REBUILD_CONFIRM)" = '1' ] || fail 'Production backup is blocked. Re-run with PROD_REBUILD_CONFIRM=1.'; \
	if ! mkdir -p "$(PROD_BACKUP_DIR)" || [ ! -w "$(PROD_BACKUP_DIR)" ]; then fail "Backup directory '$(PROD_BACKUP_DIR)' is not writable."; fi; \
	timestamp=$$(date -u +%Y%m%dT%H%M%SZ); \
	postgres_container=$$($(DOCKER_COMPOSE_PROD) ps -q postgres); redis_container=$$($(DOCKER_COMPOSE_PROD) ps -q redis); \
	[ -n "$$postgres_container" ] && [ -n "$$redis_container" ] || fail 'Both PostgreSQL and Redis containers must be running for backup.'; \
	umask 077; \
	postgres_backup="$(PROD_BACKUP_DIR)/postgres-$$timestamp.dump"; postgres_tmp="$$postgres_backup.tmp"; \
	if ! $(DOCKER_COMPOSE_PROD) exec -T postgres sh -lc 'PGPASSWORD="$${POSTGRES_PASSWORD}" pg_dump --format=custom --no-owner --no-privileges --username="$${POSTGRES_USER}" "$${POSTGRES_DB}"' > "$$postgres_tmp"; then rm -f "$$postgres_tmp"; fail 'PostgreSQL logical dump failed.'; fi; \
	if [ ! -s "$$postgres_tmp" ]; then rm -f "$$postgres_tmp"; fail 'PostgreSQL logical dump is empty.'; fi; \
	mv "$$postgres_tmp" "$$postgres_backup"; \
	redis_result=$$($(DOCKER_COMPOSE_PROD) exec -T redis redis-cli --raw BGSAVE) || fail 'Redis BGSAVE failed.'; \
	case "$$redis_result" in *'Background saving started'*|*'Background saving terminated'*) ;; *) fail "Redis returned an unexpected BGSAVE response: $$redis_result" ;; esac; \
	deadline=$$(( $$(date +%s) + $(PROD_REBUILD_TIMEOUT) )); \
	while :; do \
		redis_persistence=$$($(DOCKER_COMPOSE_PROD) exec -T redis redis-cli --raw INFO persistence) || fail 'Could not read Redis snapshot status.'; \
		redis_in_progress=$$(printf '%s\n' "$$redis_persistence" | tr -d '\r' | sed -n 's/^rdb_bgsave_in_progress://p'); redis_last_status=$$(printf '%s\n' "$$redis_persistence" | tr -d '\r' | sed -n 's/^rdb_last_bgsave_status://p'); \
		if [ "$$redis_in_progress" = '0' ] && [ "$$redis_last_status" = 'ok' ]; then break; fi; \
		[ "$$(date +%s)" -lt "$$deadline" ] || fail 'Redis BGSAVE did not complete successfully before the timeout.'; sleep 1; \
	done; \
	redis_backup="$(PROD_BACKUP_DIR)/redis-$$timestamp.rdb"; redis_tmp="$$redis_backup.tmp"; \
	if ! docker cp "$${redis_container}:/data/dump.rdb" "$$redis_tmp"; then rm -f "$$redis_tmp"; fail 'Could not copy the Redis RDB snapshot from the container.'; fi; \
	if [ ! -s "$$redis_tmp" ]; then rm -f "$$redis_tmp"; fail 'Redis RDB snapshot is empty.'; fi; \
	mv "$$redis_tmp" "$$redis_backup"; \
	printf '%s\n' "✅ PostgreSQL backup: $$postgres_backup"; printf '%s\n' "✅ Redis snapshot: $$redis_backup"

prod-prune: ## 🧹 Safe Docker cleanup (removes unused images/cache but keeps stopped containers)
	@echo "Cleaning up unused Docker resources (images, networks, and build cache)..."
	docker image prune -a -f
	docker builder prune -f
	docker network prune -f

# =============================
# Production maintenance helpers
# =============================

prod-maintenance-on: ## Enable Caddy maintenance mode (serve static maintenance.html)
	@set -eu; \
	printf '%s\n' '[prod-maintenance-on] Ensuring maintenance.html exists...'; \
	$(DOCKER_COMPOSE_PROD) exec -T app sh -lc 'test -f /var/www/html/public/maintenance.html || printf %s "<!doctype html><title>Maintenance</title><h1>Trwa aktualizacja…</h1>" > /var/www/html/public/maintenance.html'; \
	cid=$$($(DOCKER_COMPOSE_PROD) ps -q caddy); \
	[ -n "$$cid" ] || { printf '%s\n' '[prod-maintenance-on] Caddy container not found.' >&2; exit 1; }; \
	[ "$$(docker inspect -f '{{.State.Running}}' "$$cid" 2>/dev/null)" = 'true' ] || { printf '%s\n' '[prod-maintenance-on] Caddy container is not running.' >&2; exit 1; }; \
	printf '%s\n' '[prod-maintenance-on] Copying maintenance Caddyfile into container...'; \
	docker cp docker/Caddyfile.maintenance "$$cid:/etc/caddy/Caddyfile.maintenance"; \
	printf '%s\n' '[prod-maintenance-on] Validating maintenance Caddyfile...'; \
	$(DOCKER_COMPOSE_PROD) exec -T caddy caddy validate --config /etc/caddy/Caddyfile.maintenance; \
	printf '%s\n' '[prod-maintenance-on] Reloading maintenance Caddyfile...'; \
	$(DOCKER_COMPOSE_PROD) exec -T caddy caddy reload --config /etc/caddy/Caddyfile.maintenance; \
	printf '%s\n' '[prod-maintenance-on] Maintenance mode is active.'

prod-maintenance-off: ## Disable Caddy maintenance mode (restore normal config)
	@set -eu; \
	cid=$$($(DOCKER_COMPOSE_PROD) ps -q caddy); \
	[ -n "$$cid" ] || { printf '%s\n' '[prod-maintenance-off] Caddy container not found.' >&2; exit 1; }; \
	[ "$$(docker inspect -f '{{.State.Running}}' "$$cid" 2>/dev/null)" = 'true' ] || { printf '%s\n' '[prod-maintenance-off] Caddy container is not running.' >&2; exit 1; }; \
	printf '%s\n' '[prod-maintenance-off] Validating normal Caddyfile...'; \
	$(DOCKER_COMPOSE_PROD) exec -T caddy caddy validate --config /etc/caddy/Caddyfile; \
	printf '%s\n' '[prod-maintenance-off] Reloading normal Caddyfile...'; \
	$(DOCKER_COMPOSE_PROD) exec -T caddy caddy reload --config /etc/caddy/Caddyfile; \
	printf '%s\n' '[prod-maintenance-off] Maintenance mode is disabled.'
