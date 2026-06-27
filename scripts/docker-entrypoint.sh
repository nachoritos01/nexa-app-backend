#!/bin/sh
set -e

echo "==> Starting SaaS Template..."

# Clear old cached config so env() works for migrations and seeders
echo "==> Clearing old cache..."
php artisan config:clear

# Run database migrations
echo "==> Running migrations..."
php artisan migrate --force

# Seed initial data (solo si las tablas estan vacias)
echo "==> Seeding data..."
php artisan db:seed --force

# Create storage symlink if it doesn't exist
if [ ! -L public/storage ]; then
    echo "==> Creating storage link..."
    php artisan storage:link
fi

# Cache configuration for production performance
echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start queue worker in background if not sync
if [ "${QUEUE_CONNECTION:-database}" != "sync" ]; then
    echo "==> Starting queue worker in background..."
    php artisan queue:work --tries=3 --timeout=60 --sleep=3 --max-time=3600 &
fi

# Start the application
echo "==> Starting server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
