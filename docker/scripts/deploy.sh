#!/bin/bash
set -euo pipefail

ENVIRONMENT="${1:-staging}"
echo "Deploying to $ENVIRONMENT..."

# Pull latest
git pull origin $(git branch --show-current)

# Build and deploy with zero downtime
docker compose -f docker-compose.yml \
    -f docker-compose.${ENVIRONMENT}.yml \
    build --no-cache app

# Rolling update (zero downtime)
docker compose -f docker-compose.yml \
    -f docker-compose.${ENVIRONMENT}.yml \
    up -d --no-deps --build app nginx

# Run migrations
docker compose exec app php artisan migrate --force

# Clear and re-cache
docker compose exec app php artisan optimize

# Restart queue gracefully
docker compose exec app php artisan queue:restart

# Health check
sleep 5
HEALTH=$(docker compose exec app php artisan env:validate 2>&1)
echo "$HEALTH"

echo "✓ Deployed to $ENVIRONMENT"
