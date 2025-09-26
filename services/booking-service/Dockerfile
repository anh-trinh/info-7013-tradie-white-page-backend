FROM php:8.2-cli-alpine
WORKDIR /var/www/html

# Install PHP extensions required by Lumen (PDO, MySQL, sockets, mbstring) with retry + cleanup
RUN set -eux; \
	for i in 1 2 3; do \
		apk update && apk add --no-cache --virtual .build-deps linux-headers oniguruma-dev && break || sleep 4; \
	done; \
	docker-php-ext-install pdo pdo_mysql sockets mbstring; \
	apk del .build-deps

# Install Composer
RUN apk add --no-cache curl git unzip \
 && curl -sS https://getcomposer.org/installer -o composer-setup.php \
 && php composer-setup.php --install-dir=/usr/bin --filename=composer \
 && rm composer-setup.php

# Copy application source (including vendor if present)
COPY . .
# Install PHP dependencies at build time
RUN composer update --no-dev --prefer-dist --no-interaction \
 && composer dump-autoload -o
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose the port the PHP built-in server will use
EXPOSE 80

# Start Lumen using PHP's built-in server
CMD ["sh", "/entrypoint.sh"]
