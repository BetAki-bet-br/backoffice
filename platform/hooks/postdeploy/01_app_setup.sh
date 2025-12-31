#!/bin/bash
set -e

# Espera o app subir e ter DB acessível (RDS pode demorar alguns segundos)
echo "==> Waiting DB connectivity..."
for i in $(seq 1 30); do
  if docker compose exec -T app php -r "exit((int)!@fsockopen(getenv('DB_HOST')?:'localhost', (int)(getenv('DB_PORT')?:5432)));"; then
    echo "DB reachable."
    break
  fi
  echo "DB not reachable yet... retry $i/30"
  sleep 2
done

echo "==> Running migrations..."
docker compose exec -T app php artisan migrate --force

echo "==> Caching config/routes/views..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache || true
docker compose exec -T app php artisan view:cache || true

echo "==> Done."