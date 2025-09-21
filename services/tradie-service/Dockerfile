FROM php:8.2-cli-alpine
WORKDIR /var/www/html
RUN docker-php-ext-install pdo pdo_mysql
COPY . .
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
