#!/bin/sh
set -e

# Copy .env.example to .env if .env doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Install dependencies
composer install --no-interaction --no-plugins --no-scripts

# Generate application key
php artisan key:generate --no-interaction --force

sleep 10

# Run migrations
php artisan migrate --force

# Run seeders
php artisan db:seed --force

# Start PHP-FPM
exec "$@"
