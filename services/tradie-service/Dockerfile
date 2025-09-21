FROM php:8.2-cli-alpine
WORKDIR /var/www/html

# Install PHP extensions required by Lumen (PDO MySQL)
RUN apk add --no-cache linux-headers \
 && docker-php-ext-install pdo pdo_mysql

# Install Composer
RUN apk add --no-cache curl git unzip \
 && curl -sS https://getcomposer.org/installer -o composer-setup.php \
 && php composer-setup.php --install-dir=/usr/bin --filename=composer \
 && rm composer-setup.php

# Copy application source (including vendor if present)
COPY . .

# Install PHP dependencies at build time (best-effort)
RUN composer update --no-dev --prefer-dist --no-interaction || true \
 && composer dump-autoload -o || true

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 8000
CMD ["sh", "/entrypoint.sh"]
