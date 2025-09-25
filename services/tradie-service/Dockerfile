FROM php:8.2-cli-alpine
WORKDIR /var/www/html

# Install PHP extensions required by Lumen
RUN apk add --no-cache linux-headers oniguruma-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring

# Install Composer
RUN apk add --no-cache curl git unzip \
 && curl -sS https://getcomposer.org/installer -o composer-setup.php \
 && php composer-setup.php --install-dir=/usr/bin --filename=composer \
 && rm composer-setup.php

# Copy composer files first to leverage Docker layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress || (cat /root/.composer/logs/* || true)

# Then copy the rest of the application source
COPY . .

# Install PHP dependencies at build time (best-effort)
RUN composer dump-autoload -o || true

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 3000
CMD ["sh", "/entrypoint.sh"]
