FROM php:8.2-fpm-alpine
RUN apk add --no-cache bash git icu-dev oniguruma-dev libzip-dev zip unzip mysql-client curl nginx
RUN docker-php-ext-install pdo_mysql mbstring intl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader || true
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html/storage
EXPOSE 9000
CMD ["php-fpm"]