FROM php:8.2-fpm
WORKDIR /var/www/html
COPY . .
RUN apt-get update && apt-get install -y libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-interaction --prefer-dist --optimize-autoloader
EXPOSE 9000
CMD ["php-fpm"]