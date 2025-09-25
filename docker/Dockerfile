# Stage 1: Build frontend assets
FROM node:24-alpine AS node-builder
WORKDIR /app

# It's better to install all dependencies for building
COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build:ssr

# Stage 2: Base PHP image with common dependencies
FROM php:8.4-fpm-alpine AS base

ARG APP_NAME=laravel-app
ARG TZ=UTC
# Use a mirror close to Poland by default; can be overridden at build time
ARG ALPINE_MIRROR=https://mirror.plustech.pl/alpine

# Install only common system dependencies
RUN sed -i "s#https://dl-cdn.alpinelinux.org/alpine#${ALPINE_MIRROR}#g" /etc/apk/repositories \
    && apk add --no-cache \
    postgresql-dev \
    zip \
    unzip \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    supervisor \
    tzdata

# Set timezone
ENV TZ=$TZ
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install common PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        intl \
        mbstring \
        opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

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
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy built assets from node builder
COPY --from=node-builder /app/public/build ./public/build

# Set permissions for production
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copy production configurations
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Switch to a non-root user for security
USER www-data

EXPOSE 9000
CMD ["php-fpm"]


# Stage 4: Development image (with dev tools and full dependencies)
FROM base AS development

# Install development-specific system tools
RUN apk add --no-cache \
    git \
    fish \
    fzf \
    bash \
    mc \
    nodejs \
    npm

# FIX: Copy the application code and assets BEFORE running composer install.
COPY . .
COPY --from=node-builder /app/public/build ./public/build

# Install all PHP dependencies, including dev
RUN composer install --no-interaction

# Set up fish plugins and configuration for development
ENV FISH_CONFIG_DIR /root/.config/fish
RUN mkdir -p $FISH_CONFIG_DIR/functions && \
    curl -sL https://raw.githubusercontent.com/jorgebucaran/fisher/main/functions/fisher.fish > $FISH_CONFIG_DIR/functions/fisher.fish && \
    fish -c "fisher install IlanCosman/tide && fisher install jethrokuan/fzf && fisher install jorgebucaran/autopair.fish" && \
    fish -c "tide configure --auto --style=Rainbow --prompt_colors='True color' --show_time='24-hour format' --rainbow_prompt_separators=Slanted --powerline_prompt_heads=Round --powerline_prompt_tails=Flat --powerline_prompt_style='Two lines, character and frame' --prompt_connection=Dotted --powerline_right_prompt_frame=No --prompt_connection_andor_frame_color=Dark --prompt_spacing=Sparse --icons='Many icons' --transient=Yes"

# Backup the fish config
RUN mkdir -p /opt/fish_config_default && cp -r $FISH_CONFIG_DIR/* /opt/fish_config_default/

# Create directories and volumes for persistent fish history and config
RUN mkdir -p /root/.local/share/fish /root/.config/fish
VOLUME ["/root/.local/share/fish", "/root/.config/fish"]

# Development permissions can be more permissive if needed, but this is a good default
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# You can have separate php.ini for development (e.g., with Xdebug enabled)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000
CMD ["php-fpm"]

# Stage 5: SSR runtime image for running Inertia SSR server
FROM node:24-alpine AS ssr-runtime
WORKDIR /var/www/html
# Copy the application code (for env, config references if needed)
COPY . .
# Copy the built SSR bundle from the node-builder stage
COPY --from=node-builder /app/bootstrap/ssr ./bootstrap/ssr
# Default SSR port used by inertia-laravel
ENV PORT=13714
EXPOSE 13714
# Start the SSR server (use .js since Vite outputs ssr.js by default)
CMD ["node", "bootstrap/ssr/ssr.js"]
