# Stage 1: Build frontend assets
FROM node:25-trixie-slim AS node-builder
WORKDIR /app

# It's better to install all dependencies for building
COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build && npm run build:ssr

# Stage 2: Base PHP image with common dependencies
FROM php:8.5-fpm-trixie AS base

ARG APP_NAME=laravel-app
ARG TZ=UTC

# Install system dependencies and install-php-extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    wget \
    zip \
    unzip \
    git \
    supervisor \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions using the installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    intl \
    mbstring \
    redis

# Set timezone
ENV TZ=$TZ
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer runs as root in containers; silence the warning and allow operations
ENV COMPOSER_ALLOW_SUPERUSER=1

# Fix Git "dubious ownership" when the workdir is owned by a different UID
RUN git config --global --add safe.directory /var/www/html

COPY composer.json composer.lock ./

# Create app name environment variable
ENV APP_NAME=$APP_NAME

# Copy entrypoint script early so it's available in all subsequent stages
COPY docker/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]

# Stage 3: Production image (lean and optimized)
FROM base AS production

# FIX: Copy the application code BEFORE running composer install.
# This ensures that 'artisan' and other files are available for composer scripts.
COPY . .

# Install production PHP dependencies
RUN mkdir -p bootstrap/cache && chmod -R 775 bootstrap/cache && chown -R www-data:www-data bootstrap/cache
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy built assets from node builder
COPY --from=node-builder /app/public/build ./public/build
# ✅ COPY SSR BUNDLE - This was missing!
COPY --from=node-builder /app/bootstrap/ssr ./bootstrap/ssr

# Prepare snapshots of built assets for seeding into mounted volumes at runtime
RUN mkdir -p /opt/built/public/build /opt/built/public/img /opt/built/bootstrap/ssr \
    && cp -a /var/www/html/public/build/. /opt/built/public/build/ \
    && cp -a /var/www/html/public/img/. /opt/built/public/img/ \
    && cp -a /var/www/html/bootstrap/ssr/. /opt/built/bootstrap/ssr/ \
    && cp /var/www/html/public/*.ico /var/www/html/public/*.png /var/www/html/public/*.svg /var/www/html/public/*.xml /var/www/html/public/*.html /var/www/html/public/index.php /var/www/html/public/.htaccess /opt/built/public/ 2>/dev/null || true \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copy production configurations
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Run php-fpm (master as root, workers as www-data by default). We keep root here
# so the entrypoint can fix ownership on host-mounted volumes at runtime.
EXPOSE 9000
CMD ["php-fpm"]


# Stage 4: Development image (with dev tools and full dependencies)
FROM base AS development

# Install development-specific system tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    fish \
    fzf \
    bash \
    mc \
    gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_25.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# FIX: Copy the application code and assets BEFORE running composer install.
COPY . .
COPY --from=node-builder /app/public/build ./public/build
COPY --from=node-builder /app/bootstrap/ssr ./bootstrap/ssr

# Install all PHP dependencies, including dev
RUN mkdir -p bootstrap/cache && chmod -R 775 bootstrap/cache && chown -R www-data:www-data bootstrap/cache
RUN composer install --no-interaction

# Set up fish plugins and configuration for development
ENV FISH_CONFIG_DIR /root/.config/fish
RUN mkdir -p $FISH_CONFIG_DIR/functions && \
    curl -sL https://raw.githubusercontent.com/jorgebucaran/fisher/main/functions/fisher.fish > $FISH_CONFIG_DIR/functions/fisher.fish && \
    fish -c "fisher install IlanCosman/tide@v6 && fisher install jethrokuan/fzf && fisher install jorgebucaran/autopair.fish" && \
    fish -c "tide configure --auto --style=Rainbow --prompt_colors='True color' --show_time='24-hour format' --rainbow_prompt_separators=Slanted --powerline_prompt_heads=Round --powerline_prompt_tails=Flat --powerline_prompt_style='Two lines, character and frame' --prompt_connection=Dotted --powerline_right_prompt_frame=No --prompt_connection_andor_frame_color=Dark --prompt_spacing=Sparse --icons='Many icons' --transient=Yes"

# Backup the fish config
RUN mkdir -p /opt/fish_config_default && cp -r $FISH_CONFIG_DIR/* /opt/fish_config_default/

# Create directories and volumes for persistent fish history and config
RUN mkdir -p /root/.local/share/fish /root/.config/fish
VOLUME ["/root/.local/share/fish", "/root/.config/fish"]

# Prepare snapshots of built assets for dev image too (useful when mounting volumes)
RUN mkdir -p /opt/built/public/build /opt/built/public/img /opt/built/bootstrap/ssr \
    && cp -a /var/www/html/public/build/. /opt/built/public/build/ \
    && cp -a /var/www/html/public/img/. /opt/built/public/img/ \
    && cp -a /var/www/html/bootstrap/ssr/. /opt/built/bootstrap/ssr/ \
    && cp /var/www/html/public/*.ico /var/www/html/public/*.png /var/www/html/public/*.svg /var/www/html/public/*.xml /var/www/html/public/*.html /var/www/html/public/index.php /var/www/html/public/.htaccess /opt/built/public/ 2>/dev/null || true \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# You can have separate php.ini for development (e.g., with Xdebug enabled)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000
CMD ["php-fpm"]

# Stage 5: SSR runtime image for running Inertia SSR server
FROM node:25-trixie-slim AS ssr-runtime
WORKDIR /var/www/html
# Install only runtime Node dependencies required by the SSR server
# We need netcat for the deploy.sh script's health check
RUN apt-get update && apt-get install -y --no-install-recommends netcat-openbsd && rm -rf /var/lib/apt/lists/*
COPY package*.json ./
RUN npm ci --omit=dev
# Copy the application code (for env, config references if needed)
COPY . .
# Copy the built SSR bundle from the node-builder stage
COPY --from=node-builder /app/bootstrap/ssr ./bootstrap/ssr
# Default SSR port used by inertia-laravel
ENV PORT=13714
EXPOSE 13714
# Start the SSR server (use .js since Vite outputs ssr.js by default)
CMD ["node", "bootstrap/ssr/ssr.js"]
