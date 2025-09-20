#!/usr/bin/env sh
set -e

echo "Waiting for database ${DB_HOST:-booking-db}..."
until php -r "new PDO('mysql:host='.(getenv('DB_HOST')?:'booking-db').';port='.(getenv('DB_PORT')?:'3306'), getenv('DB_USERNAME')?:'root', getenv('DB_PASSWORD')?:'root'); echo 'ok';" >/dev/null 2>&1; do
  sleep 2
  echo "...still waiting"
done

echo "Running migrations (if any)..."
php artisan migrate --force || true

echo "Starting HTTP server on :8000"
# Use public/index.php as router so all requests are handled by Lumen
exec php -S 0.0.0.0:8000 -t public public/index.php
