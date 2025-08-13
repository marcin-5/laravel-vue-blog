#!/bin/bash

# Build and deploy the application
echo "Building and starting services for production..."
# The --build flag will build images if they are out of date
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

echo "Waiting for services to be ready..."
sleep 10

echo "Running Laravel production optimizations..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan view:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force

echo "Deployment complete!"
