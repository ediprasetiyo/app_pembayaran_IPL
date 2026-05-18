# Dockerfile untuk deploy Laravel di Render.com
# Stack: PHP 8.2 + PostgreSQL + Composer

FROM php:8.2-cli

# Install system deps + PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    libsodium-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        mbstring \
        bcmath \
        zip \
        gd \
        intl \
        sodium \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first (untuk Docker layer caching)
COPY composer.json composer.lock ./

# Install PHP deps tanpa scripts (artisan belum copy)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copy rest of application
COPY . .

# Run scripts setelah semua file ada
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi || true

# Permissions
RUN chmod -R 775 storage bootstrap/cache \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

# Expose port (Render set $PORT env)
ENV PORT=8000
EXPOSE 8000

# Startup script: cache config, run migrations, serve
COPY render-start.sh /usr/local/bin/render-start.sh
RUN chmod +x /usr/local/bin/render-start.sh

CMD ["/usr/local/bin/render-start.sh"]
