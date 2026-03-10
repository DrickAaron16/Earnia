#!/bin/bash
set -e  # Exit on error

echo "🚀 Starting Earnia Backend on Railway..."
echo "Environment: ${APP_ENV:-not set}"
echo "PHP Version: $(php -v | head -n 1)"

# Create SQLite database
echo "📦 Setting up SQLite database..."
mkdir -p /app/database
touch /app/database/database.sqlite
chmod 666 /app/database/database.sqlite

# Set proper permissions
echo "📁 Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Clear any cached config
echo "🧹 Clearing cache..."
php artisan config:clear || true

# Run migrations
echo "🔄 Running migrations..."
php artisan migrate --force --no-interaction

# Seed games
echo "🎮 Seeding games..."
php artisan db:seed --class=GameSeeder --force --no-interaction || echo "⚠️ Seeding skipped"

# Cache config for production
if [ "$APP_ENV" = "production" ]; then
    echo "⚙️ Caching config..."
    php artisan config:cache || echo "⚠️ Config cache failed"
    php artisan route:cache || echo "⚠️ Route cache failed"
fi

# Start server
echo "✅ Starting server on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
