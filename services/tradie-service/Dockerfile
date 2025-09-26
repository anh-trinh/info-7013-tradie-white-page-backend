FROM php:8.2-cli-alpine
WORKDIR /var/www/html

# Install PHP extensions required by Lumen (with retry) + cleanup
RUN set -eux; \
	for i in 1 2 3; do \
		apk update && apk add --no-cache --virtual .build-deps linux-headers oniguruma-dev && break || sleep 4; \
	done; \
	docker-php-ext-install pdo pdo_mysql mbstring sockets; \
	apk del .build-deps

# Install Composer
RUN apk add --no-cache curl git unzip \
 && curl -sS https://getcomposer.org/installer -o composer-setup.php \
 && php composer-setup.php --install-dir=/usr/bin --filename=composer \
 && rm composer-setup.php

# Copy composer files first (for layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress || (cat /root/.composer/logs/* || true)

# Copy the rest of the application source
COPY . .
RUN composer dump-autoload -o || true

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 3000
CMD ["sh", "/entrypoint.sh"]
