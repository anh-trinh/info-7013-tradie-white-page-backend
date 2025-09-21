#!/bin/sh
set -e

# Run migrations if artisan exists
if [ -f /var/www/html/artisan ]; then
  php artisan migrate --force || true
fi

exec php -S 0.0.0.0:8000 -t public public/index.php
