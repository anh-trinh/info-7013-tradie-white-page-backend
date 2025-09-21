FROM php:8.2-cli-alpine
WORKDIR /var/www/html

# Install PHP extensions required by Lumen (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Copy application source (including vendor if present)
COPY . .

# Expose the port the PHP built-in server will use
EXPOSE 80

# Start Lumen using PHP's built-in server
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
