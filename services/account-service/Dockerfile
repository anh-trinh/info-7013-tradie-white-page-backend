FROM php:8.2-cli-alpine
WORKDIR /var/www/html

# PHP extensions
RUN apk add --no-cache oniguruma-dev linux-headers \
 && docker-php-ext-install pdo pdo_mysql mbstring

# Install Composer
RUN apk add --no-cache curl git unzip \
 && curl -sS https://getcomposer.org/installer -o composer-setup.php \
 && php composer-setup.php --install-dir=/usr/bin --filename=composer \
 && rm composer-setup.php

# Leverage caching for dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress || (cat /root/.composer/logs/* || true)

# Copy app source and optimize autoload
COPY . .
RUN composer dump-autoload -o || true

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
CMD ["sh", "/entrypoint.sh"]
