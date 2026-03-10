#!/bin/bash
set -e  # Exit on error

echo "🚀 Starting Earnia Backend on Railway..."
echo "Environment: ${APP_ENV:-not set}"
echo "PHP Version: $(php -v | head -n 1)"

# Check critical environment variables
if [ -z "$APP_KEY" ]; then
    echo "❌ ERROR: APP_KEY is not set!"
    exit 1
fi

if [ -z "$DB_HOST" ]; then
    echo "❌ ERROR: DB_HOST is not set!"
    exit 1
fi

echo "✅ Environment variables OK"

# Set proper permissions
echo "📁 Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Clear any cached config
echo "🧹 Clearing cache..."
php artisan config:clear || true
php artisan cache:clear || true

# Run migrations
echo "🔄 Running migrations..."
php artisan migrate --force --no-interaction

# Seed games if needed
echo "🎮 Checking games..."
php artisan db:seed --class=GameSeeder --force --no-interaction || echo "⚠️ Seeding skipped (may already exist)"

# Cache config for production
if [ "$APP_ENV" = "production" ]; then
    echo "⚙️ Caching config..."
    php artisan config:cache
    php artisan route:cache
fi

# Start server
echo "✅ Starting server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
