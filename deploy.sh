#!/bin/bash

# Exit on error, undefined variables, and pipe failures
set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1"
}

# Trap errors and cleanup
cleanup() {
    if [ $? -ne 0 ]; then
        error "Deployment failed! Taking site out of maintenance mode..."
        php artisan up 2>/dev/null || true
    fi
}
trap cleanup EXIT

log "Starting deployment..."

cd /home/ploi/optiflow.com.do || {
    error "Failed to change to project directory"
    exit 1
}

log "Enabling maintenance mode..."
php artisan down --retry=60 || warning "Failed to enable maintenance mode"

log "Pulling latest code from main branch..."
if ! git pull origin main; then
    error "Git pull failed"
    php artisan up
    exit 1
fi

log "Pulling latest tags..."
if ! git fetch --tags; then
    error "Git fetch tags failed"
    php artisan up
    exit 1
fi

log "Installing composer dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

log "Reloading PHP-FPM..."
sudo -S service php8.4-fpm reload || warning "Failed to reload PHP-FPM"

log "Running database migrations..."
php artisan migrate --force

log "Running tenant migrations..."
php artisan tenants:migrate --force

log "Installing npm dependencies..."
npm ci

log "Building frontend assets..."
NODE_OPTIONS="--max-old-space-size=4096" npm run build

# Optimize application
log "Optimizing application..."
php artisan optimize

log "Reloading application..."
php artisan reload

# Disable maintenance mode
log "Disabling maintenance mode..."
php artisan up

log "Deployment completed successfully!"