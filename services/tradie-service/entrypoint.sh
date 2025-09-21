#!/bin/sh
set -e

# Run migrations if artisan exists
if [ -f /var/www/html/artisan ]; then
  php artisan migrate --force || true
fi

PORT="${PORT:-3000}"
exec php -S 0.0.0.0:${PORT} -t public public/index.php
