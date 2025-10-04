# Use a variable for the compose files to avoid repetition
COMPOSE_FILES = -f docker/docker-compose.yml -f docker/docker-compose.dev.yml
DOCKER_COMPOSE = docker compose $(COMPOSE_FILES)

# Default command to run when no target is specified
.DEFAULT_GOAL := help

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
.PHONY: init dev build up down restart logs migrate key-gen npm-install composer-install fish setup-env help db-reset

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
	@if [ -n "$$($(DOCKER_COMPOSE) ps -q)" ]; then \
		echo "Containers are already running."; \
	else \
		echo "Containers are not running. Starting them now..."; \
		$(DOCKER_COMPOSE) up -d; \
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
DOCKER_COMPOSE_PROD = docker compose $(COMPOSE_FILES_PROD)

.PHONY: prod-up prod-down prod-restart prod-build prod-logs \
        prod-migrate prod-optimize prod-deploy prod-update prod-wait

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
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan route:cache
	$(DOCKER_COMPOSE_PROD) exec -T app php artisan view:cache
	-$(DOCKER_COMPOSE_PROD) exec -T app php artisan ziggy:generate

# Full deployment flow: pull code/images, rebuild, wait for app, optimize, migrate
prod-deploy: ## Build/Start prod, run optimizations & migrations
	# If building from source on the server, ensure latest code first:
	git fetch --all
	git pull --ff-only
	$(DOCKER_COMPOSE_PROD) up -d --build
	$(MAKE) prod-wait
	# Optional: use healthchecks and wait for healthy
	# $(DOCKER_COMPOSE_PROD) up -d --build --wait || true
	$(MAKE) prod-optimize
	$(MAKE) prod-migrate

# Shorthand target to update code and restart containers
prod-update: ## Update code from Git and restart services
	git fetch --all
	git pull --ff-only
	$(DOCKER_COMPOSE_PROD) up -d --build
