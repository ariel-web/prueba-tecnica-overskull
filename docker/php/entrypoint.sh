#!/bin/sh
set -e

# Create .env if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Ensure storage and cache directories exist before Composer runs
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# Permissions
chmod -R 775 storage bootstrap/cache

# Install dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-security-blocking

# Generate app key if not set
if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

# Run migrations (skip in worker to avoid race conditions)
if [ "${SKIP_MIGRATIONS}" != "true" ]; then
    php artisan migrate --seed --force
fi

# Execute the passed command (php-fpm for backend, queue:work for worker)
exec "$@"
