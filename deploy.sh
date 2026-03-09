#!/bin/bash

# Earnia Backend Deployment Script
# Usage: ./deploy.sh [environment]
# Example: ./deploy.sh production

set -e

ENVIRONMENT=${1:-production}
PROJECT_DIR="/var/www/earnia-api"
BACKUP_DIR="/var/backups/earnia"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "🚀 Starting Earnia API deployment for $ENVIRONMENT environment..."

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Function to log messages
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Function to handle errors
handle_error() {
    log "❌ Error occurred during deployment. Rolling back..."
    if [ -d "$BACKUP_DIR/backup_$TIMESTAMP" ]; then
        log "🔄 Restoring from backup..."
        rsync -av --delete "$BACKUP_DIR/backup_$TIMESTAMP/" "$PROJECT_DIR/"
        cd $PROJECT_DIR
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        sudo systemctl reload nginx
        log "✅ Rollback completed"
    fi
    exit 1
}

# Set error handler
trap handle_error ERR

# Pre-deployment checks
log "🔍 Running pre-deployment checks..."

# Check if required commands exist
command -v php >/dev/null 2>&1 || { log "❌ PHP is required but not installed."; exit 1; }
command -v composer >/dev/null 2>&1 || { log "❌ Composer is required but not installed."; exit 1; }
command -v git >/dev/null 2>&1 || { log "❌ Git is required but not installed."; exit 1; }

# Check if project directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    log "❌ Project directory $PROJECT_DIR does not exist."
    exit 1
fi

# Backup current deployment
log "💾 Creating backup..."
mkdir -p "$BACKUP_DIR/backup_$TIMESTAMP"
rsync -av --exclude='.git' --exclude='node_modules' --exclude='vendor' "$PROJECT_DIR/" "$BACKUP_DIR/backup_$TIMESTAMP/"

# Navigate to project directory
cd $PROJECT_DIR

# Enable maintenance mode
log "🔧 Enabling maintenance mode..."
php artisan down --retry=60 --secret="earnia-deploy-$TIMESTAMP"

# Pull latest code
log "📥 Pulling latest code from repository..."
git fetch origin
git reset --hard origin/main

# Copy environment file
log "⚙️ Setting up environment configuration..."
if [ "$ENVIRONMENT" = "production" ]; then
    cp .env.production .env
else
    cp .env.example .env
fi

# Install/update dependencies
log "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Generate application key if not exists
if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY= .env | cut -d '=' -f2)" ]; then
    log "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
log "🗄️ Running database migrations..."
php artisan migrate --force

# Seed database if needed (only for staging/development)
if [ "$ENVIRONMENT" != "production" ]; then
    log "🌱 Seeding database..."
    php artisan db:seed --force
fi

# Clear and cache configurations
log "🧹 Clearing and caching configurations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate API documentation
log "📚 Generating API documentation..."
php artisan l5-swagger:generate || log "⚠️ Warning: Could not generate Swagger documentation"

# Set proper permissions
log "🔐 Setting file permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Restart services
log "🔄 Restarting services..."
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx

# Restart queue workers
log "👷 Restarting queue workers..."
php artisan queue:restart

# Disable maintenance mode
log "✅ Disabling maintenance mode..."
php artisan up

# Run health checks
log "🏥 Running health checks..."
sleep 5

# Check if application is responding
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health || echo "000")
if [ "$HTTP_STATUS" = "200" ]; then
    log "✅ Health check passed - Application is responding"
else
    log "❌ Health check failed - HTTP status: $HTTP_STATUS"
    handle_error
fi

# Check database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';" || {
    log "❌ Database connection failed"
    handle_error
}

# Clean up old backups (keep last 5)
log "🧹 Cleaning up old backups..."
cd $BACKUP_DIR
ls -t | tail -n +6 | xargs -r rm -rf

# Send deployment notification (if configured)
if [ ! -z "$SLACK_WEBHOOK_URL" ]; then
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"✅ Earnia API deployed successfully to $ENVIRONMENT at $(date)\"}" \
        $SLACK_WEBHOOK_URL || log "⚠️ Warning: Could not send Slack notification"
fi

log "🎉 Deployment completed successfully!"
log "📊 Deployment summary:"
log "   - Environment: $ENVIRONMENT"
log "   - Timestamp: $TIMESTAMP"
log "   - Backup location: $BACKUP_DIR/backup_$TIMESTAMP"
log "   - Application URL: $(php artisan route:list | grep 'api/' | head -1 | awk '{print $4}' | sed 's/api.*//' || echo 'Check your routes')"

exit 0