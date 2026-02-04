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
# DOCKER_COMPOSE_PROD = docker compose --env-file .env -p $(PROJECT_NAME) $(COMPOSE_FILES_PROD)
DOCKER_COMPOSE_PROD = docker compose --env-file .env -p $(DOCKER_PROJECT_NAME_PROD) $(COMPOSE_FILES_PROD)

.PHONY: prod-up prod-down prod-restart prod-build prod-logs \
        prod-migrate prod-optimize prod-deploy prod-update prod-wait \
        prod-maintenance-on prod-maintenance-off prod-rebuild-pg-redis \
        prod-versions prod-check-assets prod-logs-queue prod-logs-app \
        prod-health-queue prod-queue-diag

prod-up: ## Start production services
	$(DOCKER_COMPOSE_PROD) up -d

prod-down: ## Stop production services
	$(DOCKER_COMPOSE_PROD) down

prod-restart: ## Restart production services
	$(DOCKER_COMPOSE_PROD) up -d

prod-build: ## Build/rebuild production images
	$(DOCKER_COMPOSE_PROD) build

prod-logs: ## Tail production logs
	$(DOCKER_COMPOSE_PROD) logs -f

prod-logs-queue: ## Tail only queue container logs
	$(DOCKER_COMPOSE_PROD) logs -f queue

prod-logs-app: ## Tail only app container logs
	$(DOCKER_COMPOSE_PROD) logs -f app

prod-queue-clear-logs: ## 🧹 Clear the queue worker log file
	$(DOCKER_COMPOSE_PROD) exec -T queue sh -c '> /var/www/html/storage/logs/supervisor_queue.log'
	@echo "✅ Queue log cleared."

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

prod-check-assets: ## Verify built assets exist (prints only failures)
	@$(DOCKER_COMPOSE_PROD) exec -T ssr sh -lc '\
		if [ -d /var/www/html/bootstrap/ssr ] && [ "$$(ls -A /var/www/html/bootstrap/ssr 2>/dev/null)" ]; then \
			:; \
		else \
			echo "❌ No SSR assets in SSR container!"; \
		fi'
	@$(DOCKER_COMPOSE_PROD) exec -T app sh -lc '\
		if [ -d /var/www/html/bootstrap/ssr ] && [ "$$(ls -A /var/www/html/bootstrap/ssr 2>/dev/null)" ]; then \
			:; \
		else \
			echo "❌ No SSR assets in app container!"; \
		fi'
	@$(DOCKER_COMPOSE_PROD) exec -T app sh -lc '\
		if [ -d /var/www/html/public/build ] && [ "$$(ls -A /var/www/html/public/build 2>/dev/null)" ]; then \
			:; \
		else \
			echo "❌ No Vite build assets in app container!"; \
		fi'
	@$(DOCKER_COMPOSE_PROD) exec -T app sh -lc '\
		if [ -d /var/www/html/public/img ] && [ "$$(ls -A /var/www/html/public/img 2>/dev/null)" ]; then \
			:; \
		else \
			echo "❌ No public/img assets in app container!"; \
		fi'

# Full deployment flow: pull code/images, rebuild, wait for app, optimize, migrate
prod-deploy: ## Build/Start prod, run optimizations & migrations
	# If building from source on the server, ensure latest code first:
	git fetch --all
	git pull --ff-only
	@echo "🧹 Clearing old bootstrap cache to prevent worker stuck..."
	rm -rf /srv/laravel-blog/bootstrap_cache/* 2>/dev/null || true
	$(DOCKER_COMPOSE_PROD) up -d --build
	$(MAKE) prod-wait
	$(MAKE) prod-versions
	$(MAKE) prod-check-assets
	# Optional: use healthchecks and wait for healthy
	# $(DOCKER_COMPOSE_PROD) up -d --build --wait || true
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

# Shorthand target to update code and restart selected services
prod-update: ## Update code from Git and restart selected services with zero-502 maintenance
	$(MAKE) prod-maintenance-on
	git fetch --all
	git pull --ff-only
	@echo "🧹 Clearing old bootstrap cache to prevent worker stuck..."
	rm -rf /srv/laravel-blog/bootstrap_cache/* 2>/dev/null || true
	@echo "🔨 Building fresh images for core services (app, ssr, queue, scheduler)..."
	$(DOCKER_COMPOSE_PROD) build --no-cache --pull app ssr queue scheduler
	@echo "🔧 Clearing Laravel caches before recreate..."
	-$(DOCKER_COMPOSE_PROD) exec -T app php artisan optimize:clear || true
	-$(DOCKER_COMPOSE_PROD) exec -T app php artisan package:discover --ansi || true
	@echo "🚀 Recreating core services without touching caddy..."
	$(DOCKER_COMPOSE_PROD) up -d --force-recreate --no-deps app ssr queue scheduler
	$(MAKE) prod-wait
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
	@echo "🔗 Testing SSR server with a test request..."
	@echo "SSR Server response:"
	$(DOCKER_COMPOSE_PROD) exec -T app wget -q -O- --timeout=5 "http://ssr:13714/render" 2>&1 || echo "SSR server not responding to /render"
	@echo ""
	@echo ""
	@echo "🔍 Verifying queue worker process..."
	@$(DOCKER_COMPOSE_PROD) exec -T queue sh -lc 'ps aux | grep -q "[q]ueue:work" || ps aux | grep -q "[s]upervisord"' \
      && echo "✅ Queue process or supervisor detected." \
      || echo "❌ No queue process found! Check logs: make prod-logs-queue"
	$(MAKE) prod-health-queue
	@echo "🔎 Queue status:"
	$(DOCKER_COMPOSE_PROD) exec app php artisan queue:monitor redis
	@echo ""
	@echo "✅ Production update complete."
	@echo ""
	@echo "If SSR still doesn't work, check your Dockerfile to ensure 'npm run build' creates bootstrap/ssr/"
	$(MAKE) prod-maintenance-off

# =============================
# Rebuild Postgres & Redis (production)
# =============================
prod-rebuild-pg-redis: ## Recreate postgres and redis services with zero-502 maintenance window
	@echo "🛠️  Enabling maintenance mode..."
	$(MAKE) prod-maintenance-on
	@echo "⬇️  Pulling latest images for postgres and redis (if available)..."
	$(DOCKER_COMPOSE_PROD) pull postgres redis || true
	@echo "♻️  Recreating postgres and redis containers without touching other services..."
	$(DOCKER_COMPOSE_PROD) up -d --force-recreate --no-deps postgres redis
	@echo "⏳ Giving services a moment to start..."
	sleep 5
	@echo "✅ Postgres and Redis have been recreated. Disabling maintenance mode..."
	$(MAKE) prod-maintenance-off

# =============================
# Production maintenance helpers
# =============================

prod-maintenance-on: ## Enable Caddy maintenance mode (serve static maintenance.html)
	@printf '%s\n' '[prod-maintenance-on] Ensuring maintenance.html exists...'
	@$(DOCKER_COMPOSE_PROD) exec -T app sh -lc 'test -f /var/www/html/public/maintenance.html || printf %s "<!doctype html><title>Maintenance</title><h1>Trwa aktualizacja…</h1>" > /var/www/html/public/maintenance.html'

	@printf '%s\n' '[prod-maintenance-on] Copying maintenance Caddyfile into container...'
	@docker cp docker/Caddyfile.maintenance $$($(DOCKER_COMPOSE_PROD) ps -q caddy):/etc/caddy/Caddyfile.maintenance

	@cid=$$($(DOCKER_COMPOSE_PROD) ps -q caddy); \
	if [ -z "$$cid" ]; then \
	  printf '%s\n' '[prod-maintenance-on] Caddy container not found; skipping reload.'; \
	else \
	  printf '%s\n' "[prod-maintenance-on] Waiting for Caddy container ($$cid) to be running..."; \
	  for i in $$(seq 1 10); do \
	    if docker inspect -f '{{.State.Running}}' $$cid 2>/dev/null | grep -q true; then \
	      printf '%s\n' '[prod-maintenance-on] Caddy is running; reloading maintenance config...'; \
	      if ! $(DOCKER_COMPOSE_PROD) exec -T caddy caddy reload --config /etc/caddy/Caddyfile.maintenance; then \
	        printf '%s\n' '[prod-maintenance-on] Warning: failed to reload Caddy into maintenance mode.'; \
	      fi; \
	      exit 0; \
	    fi; \
	    printf '%s\n' "[prod-maintenance-on] Caddy not running yet ($$i/10); waiting..."; \
	    sleep 2; \
	  done; \
	  printf '%s\n' '[prod-maintenance-on] Caddy failed to become running; continuing without reload.'; \
	fi

prod-maintenance-off: ## Disable Caddy maintenance mode (restore normal config)
	@cid=$$($(DOCKER_COMPOSE_PROD) ps -q caddy); \
	if [ -z "$$cid" ]; then \
	  printf '%s\n' '[prod-maintenance-off] Caddy container not found; skipping reload.'; \
	else \
	  printf '%s\n' "[prod-maintenance-off] Waiting for Caddy container ($$cid) to be running..."; \
	  for i in $$(seq 1 10); do \
	    if docker inspect -f '{{.State.Running}}' $$cid 2>/dev/null | grep -q true; then \
	      printf '%s\n' '[prod-maintenance-off] Caddy is running; reloading default config...'; \
	      if ! $(DOCKER_COMPOSE_PROD) exec -T caddy caddy reload --config /etc/caddy/Caddyfile; then \
	        printf '%s\n' '[prod-maintenance-off] Warning: failed to reload Caddy back to default config.'; \
	      fi; \
	      exit 0; \
	    fi; \
	    printf '%s\n' "[prod-maintenance-off] Caddy not running yet ($$i/10); waiting..."; \
	    sleep 2; \
	  done; \
	  printf '%s\n' '[prod-maintenance-off] Caddy failed to become running; continuing without reload.'; \
	fi
