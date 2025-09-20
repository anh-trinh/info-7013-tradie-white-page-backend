FROM php:8.2-cli-alpine

# System deps
RUN apk add --no-cache bash git icu-dev oniguruma-dev libzip-dev zip unzip mysql-client curl

# PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring intl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader || true \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
 && chown -R www-data:www-data /var/www/html \
 && chmod -R 775 storage bootstrap/cache || true

EXPOSE 8000

# Simple dev server; in production, prefer FPM behind Nginx inside the container or a separate web tier
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
CMD ["/usr/local/bin/start.sh"]
