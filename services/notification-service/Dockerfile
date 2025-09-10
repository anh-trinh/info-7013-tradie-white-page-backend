FROM php:8.2-cli

WORKDIR /var/www

COPY . .

RUN apt-get update && apt-get install -y libzip-dev unzip \
    && docker-php-ext-install pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install

CMD ["php", "artisan", "listen:notifications"]
