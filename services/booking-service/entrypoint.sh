#!/bin/sh
set -e

# Install composer dependencies if vendor is missing key framework files
if [ ! -f /var/www/html/vendor/autoload.php ] || [ ! -d /var/www/html/vendor/laravel/lumen-framework ]; then
  if [ -f /usr/bin/composer ]; then
    echo "Installing PHP dependencies..."
    composer update --no-dev --prefer-dist --no-interaction --ignore-platform-req=ext-sockets || true
    if [ ! -d /var/www/html/vendor/laravel/lumen-framework ]; then
      composer require laravel/lumen-framework:^10.0 --no-interaction --prefer-dist || true
      composer update --no-dev --prefer-dist --no-interaction --ignore-platform-req=ext-sockets || true
    fi
  else
    echo "Composer not found in container; attempting to use composer.phar"
    if [ -f /var/www/html/composer.phar ]; then
      php composer.phar update --no-dev --prefer-dist --no-interaction || true
      if [ ! -d /var/www/html/vendor/laravel/lumen-framework ]; then
        php composer.phar require laravel/lumen-framework:^10.0 --no-interaction --prefer-dist || true
        php composer.phar update --no-dev --prefer-dist --no-interaction || true
      fi
    fi
  fi
fi

# Ensure autoload is up to date
if [ -f /usr/bin/composer ]; then
  composer dump-autoload -o || true
fi

# Run migrations if artisan exists
if [ -f /var/www/html/artisan ]; then
  php artisan migrate --force || true
  if [ "${SEED_DEMO}" = "1" ] || [ "${SEED_DEMO}" = "true" ]; then
    php artisan db:seed --force || true
  fi
fi

PORT="${PORT:-80}"
exec php -S 0.0.0.0:"${PORT}" -t public public/index.php
