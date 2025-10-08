#!/bin/sh
set -e

FISH_CONFIG_DIR="/root/.config/fish"
DEFAULT_CONFIG_DIR="/opt/fish_config_default"
APP_NAME=${APP_NAME:-laravel-app}
TZ=${TZ:-UTC}

echo "Starting $APP_NAME..."

# Set timezone at runtime
if [ "$TZ" != "UTC" ]; then
    echo "Setting timezone to $TZ"
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime
    echo $TZ > /etc/timezone
fi

# Make sure the app directory exists
mkdir -p /var/www/html

# If the config directory is mounted but uninitialized (i.e., fisher is missing),
# copy the default config from the image into the volume.
if [ ! -f "$FISH_CONFIG_DIR/functions/fisher.fish" ]; then
   echo "Initializing fish config in $FISH_CONFIG_DIR..."
   # Ensure the target functions directory exists before copying
   mkdir -p "$FISH_CONFIG_DIR/functions"

   # Check if default config exists before copying
   if [ -d "$DEFAULT_CONFIG_DIR" ] && [ "$(ls -A $DEFAULT_CONFIG_DIR)" ]; then
      cp -a "$DEFAULT_CONFIG_DIR/." "$FISH_CONFIG_DIR/"
   else
      echo "Warning: Default fish configuration not found in $DEFAULT_CONFIG_DIR"
      # Create a minimal fish config if the default one doesn't exist
      echo "# Basic fish configuration for Laravel" > "$FISH_CONFIG_DIR/config.fish"
      echo "# Add Laravel artisan alias" >> "$FISH_CONFIG_DIR/config.fish"
      echo "alias artisan='php artisan'" >> "$FISH_CONFIG_DIR/config.fish"
      echo "alias tinker='php artisan tinker'" >> "$FISH_CONFIG_DIR/config.fish"
      echo "alias migrate='php artisan migrate'" >> "$FISH_CONFIG_DIR/config.fish"
   fi
fi

# Set working directory
cd /var/www/html

# Ensure Laravel writable directories exist and have correct permissions
mkdir -p storage bootstrap/cache

# Initialize/sync built assets into mounted volumes if needed
# 1) Public directory (may be a named volume that masks image files)
if [ -d /opt/built/public ]; then
    # If public is empty or index.php missing, seed entire public directory
    if [ ! -e /var/www/html/public/index.php ] || [ -z "$(ls -A /var/www/html/public 2>/dev/null)" ]; then
        echo "Seeding public/ directory from image snapshot..."
        mkdir -p /var/www/html/public
        cp -a /opt/built/public/. /var/www/html/public/
    fi
    # Always refresh Vite build assets to ensure latest client bundle
    if [ -d /opt/built/public/build ]; then
        echo "Syncing Vite build assets (public/build)..."
        rm -rf /var/www/html/public/build
        mkdir -p /var/www/html/public
        cp -a /opt/built/public/build /var/www/html/public/
    fi
fi

# 2) SSR bundle
if [ -d /opt/built/bootstrap/ssr ]; then
    echo "Syncing SSR bundle (bootstrap/ssr)..."
    rm -rf /var/www/html/bootstrap/ssr
    mkdir -p /var/www/html/bootstrap
    cp -a /opt/built/bootstrap/ssr /var/www/html/bootstrap/ssr
fi

# If running as root, fix ownership and permissions on mounted volumes
if [ "$(id -u)" = "0" ]; then
    echo "Fixing permissions on storage, bootstrap/cache, public, and SSR..."
    chown -R www-data:www-data storage bootstrap/cache public bootstrap/ssr || true
    chmod -R 775 storage bootstrap/cache || true
fi

# Laravel-specific initialization for production
if [ "$APP_ENV" = "production" ]; then
    echo "Production environment detected, waiting for database (for artisan readiness checks)..."

    # Wait for database to be ready (non-fatal loop)
    if command -v php >/dev/null 2>&1; then
        echo "Waiting for database connection..."
        for i in $(seq 1 30); do
            if php artisan migrate:status >/dev/null 2>&1; then
                echo "Database reachable."; break
            fi
            echo "Database not ready, retry $i/30..."
            sleep 5
        done
    fi
fi

echo "Working in Laravel directory: /var/www/html"

# Execute the command passed to the container
exec "$@"
