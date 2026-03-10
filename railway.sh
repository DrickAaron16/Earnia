#!/bin/bash

# Railway startup script for Laravel

echo "🚀 Starting Earnia Backend on Railway..."

# Create database directory if using SQLite
if [ "$DB_CONNECTION" = "sqlite" ]; then
    echo "📦 Setting up SQLite database..."
    mkdir -p /app/database
    touch /app/database/database.sqlite
    chmod 777 /app/database/database.sqlite
fi

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force --no-interaction

# Seed database with games
echo "🎮 Seeding games..."
php artisan db:seed --class=GameSeeder --force --no-interaction

# Clear and cache config
echo "⚙️ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start the server
echo "✅ Starting web server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
