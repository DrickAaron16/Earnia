#!/bin/bash

# Railway startup script for Laravel

echo "🚀 Starting Earnia Backend on Railway..."

# Set proper permissions
echo "📁 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Clear any cached config (important for Railway)
echo "🧹 Clearing cached config..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force --no-interaction || {
    echo "❌ Migration failed!"
    exit 1
}

# Seed database with games (only if games table is empty)
echo "🎮 Checking games..."
GAME_COUNT=$(php artisan tinker --execute="echo App\Models\Game::count();")
if [ "$GAME_COUNT" -eq "0" ]; then
    echo "🎮 Seeding games..."
    php artisan db:seed --class=GameSeeder --force --no-interaction
else
    echo "✅ Games already seeded ($GAME_COUNT games found)"
fi

# Optimize for production
echo "⚙️ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start the server
echo "✅ Starting web server on port ${PORT:-8000}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
