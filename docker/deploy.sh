#!/usr/bin/env bash
set -euo pipefail

# Always run from this script's directory so relative compose files resolve
cd "$(dirname "$0")"

COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"
DC="docker compose --env-file ../.env $COMPOSE_FILES"

echo "Building/pulling and starting services for production..."
$DC build app ssr
if $DC up --help | grep -q -- '--wait'; then
  $DC up -d --wait --wait-timeout 120
else
  echo "Warning: this Docker Compose version lacks --wait; using the SSR fallback check."
  $DC up -d
  if ! $DC exec -T ssr sh -c "command -v nc >/dev/null 2>&1"; then
    echo "Warning: 'nc' (netcat) is not installed in the SSR image."
  fi
  echo "Waiting for SSR service to be ready..."
  for i in {1..30}; do
    if $DC exec -T ssr sh -c "nc -z localhost 13714" >/dev/null 2>&1; then
      echo "SSR is up."; break
    fi
    echo "SSR not ready yet, retrying ($i/30)..."; sleep 2
    if [ "$i" -eq 30 ]; then echo "SSR failed to start in time."; exit 1; fi
  done
fi

# Optional: put app in maintenance mode to run caches/migrations safely
$DC exec -T app php artisan down || true

# Give PHP-FPM a brief moment
sleep 3

echo "Running Laravel production optimizations..."
$DC exec -T app php artisan config:cache
# Try to cache routes; if it fails (e.g., due to CompiledRouteCollection), clear and continue
$DC exec -T app sh -lc 'php artisan route:cache || { echo "route:cache failed; falling back to route:clear"; php artisan route:clear; }'
# Conditionally cache views only if resources/views exists
$DC exec -T app sh -lc "[ -d resources/views ] && php artisan view:cache || echo 'Skipping view:cache: resources/views not found'"
$DC exec -T app php artisan ziggy:generate || true

# Run database migrations
$DC exec -T app php artisan migrate --force

# Bring app back online
$DC exec -T app php artisan up || true

echo "Deployment complete!"
