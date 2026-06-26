#!/bin/bash
set -e

# XenonBlog Deployment Script
# Usage: ./deploy.sh [environment] [branch]
# Example: ./deploy.sh production main
# Requirements: PHP 8.2+, Composer 2.x, MySQL 8+, Redis 7+

ENV=${1:-production}
BRANCH=${2:-main}
APP_DIR=$(pwd)
DATE=$(date +%Y-%m-%d_%H-%M-%S)
LOG_FILE="${APP_DIR}/storage/logs/deploy-${DATE}.log"

echo "=== XenonBlog Deployment ===" | tee -a ${LOG_FILE}
echo "Environment: ${ENV}" | tee -a ${LOG_FILE}
echo "Branch: ${BRANCH}" | tee -a ${LOG_FILE}
echo "Date: ${DATE}" | tee -a ${LOG_FILE}
echo "" | tee -a ${LOG_FILE}

# Maintenance mode
echo "1. Enabling maintenance mode..." | tee -a ${LOG_FILE}
php artisan down --retry=60 2>&1 | tee -a ${LOG_FILE}

# Fetch latest code
echo "2. Fetching latest code..." | tee -a ${LOG_FILE}
git fetch origin ${BRANCH} 2>&1 | tee -a ${LOG_FILE}
git reset --hard origin/${BRANCH} 2>&1 | tee -a ${LOG_FILE}

# Install dependencies
echo "3. Installing PHP dependencies..." | tee -a ${LOG_FILE}
if [ "${ENV}" = "production" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev 2>&1 | tee -a ${LOG_FILE}
else
    composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1 | tee -a ${LOG_FILE}
fi

# Build assets
echo "4. Building frontend assets..." | tee -a ${LOG_FILE}
if [ -d "node_modules" ]; then
    npm ci --no-optional 2>&1 | tee -a ${LOG_FILE}
    npm run build 2>&1 | tee -a ${LOG_FILE}
fi

# Clear caches
echo "5. Clearing caches..." | tee -a ${LOG_FILE}
php artisan optimize:clear 2>&1 | tee -a ${LOG_FILE}

# Run migrations
echo "6. Running database migrations..." | tee -a ${LOG_FILE}
php artisan migrate --force 2>&1 | tee -a ${LOG_FILE}

# Seed roles
echo "7. Seeding roles & permissions..." | tee -a ${LOG_FILE}
php artisan db:seed --class=RolePermissionSeeder --force 2>&1 | tee -a ${LOG_FILE}

# Storage link
echo "8. Creating storage link..." | tee -a ${LOG_FILE}
php artisan storage:link --force 2>&1 | tee -a ${LOG_FILE}

# Warm caches
echo "9. Warming caches..." | tee -a ${LOG_FILE}
php artisan cache:warm 2>&1 | tee -a ${LOG_FILE}

# Optimize
echo "10. Optimizing application..." | tee -a ${LOG_FILE}
php artisan optimize 2>&1 | tee -a ${LOG_FILE}
php artisan view:cache 2>&1 | tee -a ${LOG_FILE}

# Health check
echo "11. Running health check..." | tee -a ${LOG_FILE}
sleep 2
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/health 2>/dev/null || echo "000")
if [ "${HEALTH_STATUS}" = "200" ]; then
    echo "Health check passed (HTTP 200)" | tee -a ${LOG_FILE}
else
    echo "Warning: Health check returned HTTP ${HEALTH_STATUS}" | tee -a ${LOG_FILE}
fi

# Disable maintenance mode
echo "12. Disabling maintenance mode..." | tee -a ${LOG_FILE}
php artisan up 2>&1 | tee -a ${LOG_FILE}

echo "" | tee -a ${LOG_FILE}
echo "=== Deployment complete! ===" | tee -a ${LOG_FILE}
