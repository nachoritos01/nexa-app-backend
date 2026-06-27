# =============================================================================
# Stage 1: Build frontend assets (Vite + Tailwind CSS)
# =============================================================================
FROM node:20-alpine AS frontend-build

WORKDIR /app

# Copy package files and install dependencies
COPY package.json package-lock.json* ./
RUN npm ci

# Copy frontend source files needed by Vite
COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

# Build production assets
RUN npm run build

# =============================================================================
# Stage 2: PHP runtime
# =============================================================================
FROM php:8.4-cli-alpine AS runtime

# Install system dependencies for PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    libxml2-dev \
    curl-dev \
    oniguruma-dev \
    autoconf \
    gcc \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    gd \
    intl \
    zip \
    curl \
    mbstring \
    xml \
    bcmath \
    pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application code
COPY . .

# Re-run composer scripts (post-autoload-dump, etc.) with full app context
RUN composer dump-autoload --optimize

# Copy built frontend assets from stage 1
COPY --from=frontend-build /app/public/build/ ./public/build/

# Set storage permissions
RUN chmod -R 775 storage bootstrap/cache \
    && mkdir -p storage/logs \
    && mkdir -p storage/framework/{sessions,views,cache}

# Copy and set up entrypoint
COPY scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Railway provides PORT env var
EXPOSE ${PORT:-8000}

ENTRYPOINT ["docker-entrypoint.sh"]
