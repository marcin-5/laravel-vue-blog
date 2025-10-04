#!/usr/bin/env bash
set -euo pipefail

# Always run from this script's directory so relative compose files resolve
cd "$(dirname "$0")"

COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"
DC="docker compose $COMPOSE_FILES"

echo "Building/pulling and starting services for production..."
$DC up -d --build

# If you define healthchecks for services in compose, you can prefer this:
# $DC up -d --build --wait || { echo "Services failed to become healthy"; exit 1; }
# $DC wait ssr app || { echo "Services not healthy"; exit 1; }

# Fallback wait for SSR if healthchecks are not in place
if ! $DC exec -T ssr sh -c "command -v nc >/dev/null 2>&1"; then
  echo "Warning: 'nc' (netcat) is not installed in the SSR image. Consider adding a healthcheck instead."
fi

echo "Waiting for SSR service to be ready..."
for i in {1..30}; do
  if $DC exec -T ssr sh -c "nc -z localhost 13714" >/dev/null 2>&1; then
    echo "SSR is up."; break
  fi
  echo "SSR not ready yet, retrying ($i/30)..."; sleep 2
  if [ "$i" -eq 30 ]; then echo "SSR failed to start in time."; exit 1; fi
done

# Optional: put app in maintenance mode to run caches/migrations safely
$DC exec -T app php artisan down || true

# Give PHP-FPM a brief moment
sleep 3

echo "Running Laravel production optimizations..."
$DC exec -T app php artisan config:cache
$DC exec -T app php artisan route:cache
$DC exec -T app php artisan view:cache
$DC exec -T app php artisan ziggy:generate || true

# Run database migrations
$DC exec -T app php artisan migrate --force

# Bring app back online
$DC exec -T app php artisan up || true

echo "Deployment complete!"
