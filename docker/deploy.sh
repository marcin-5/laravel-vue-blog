#!/bin/bash

# Build and deploy the application
echo "Building and starting services for production..."
# The --build flag will build images if they are out of date
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Wait for SSR service to be healthy/responding on port 13714
echo "Waiting for SSR service to be ready..."
for i in {1..30}; do
  if docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T ssr sh -c "nc -z localhost 13714" >/dev/null 2>&1; then
    echo "SSR is up."; break
  fi
  echo "SSR not ready yet, retrying ($i/30)..."; sleep 2
  if [ "$i" -eq 30 ]; then echo "SSR failed to start in time."; exit 1; fi
done

# Give PHP-FPM a brief moment too
echo "Waiting for app service to be ready..."
sleep 5

echo "Running Laravel production optimizations..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force

echo "Deployment complete!"
