FROM php:8.2-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    git \
    curl \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    autoconf \
    build-base \
    linux-headers \
    zip \
    unzip

# PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Ensure Laravel writable directories exist with correct permissions
RUN mkdir -p /var/www/storage/logs /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/bootstrap/cache && \
    chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

WORKDIR /var/www

ENTRYPOINT ["entrypoint.sh"]
