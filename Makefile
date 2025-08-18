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

db-reset: up ## 🔄 Reset database and run all migrations from scratch
	@echo "Resetting database and running all migrations..."
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed

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
