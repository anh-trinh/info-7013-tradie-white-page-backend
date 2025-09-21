FROM php:8.2-cli-alpine
WORKDIR /var/www/html
RUN docker-php-ext-install pdo pdo_mysql
COPY . .
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
CMD ["sh", "/entrypoint.sh"]
