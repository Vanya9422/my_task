# Use the official PHP-FPM image as base
FROM php:8.0-fpm

# Set working directory
WORKDIR /var/www/app

# Install required extensions
RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-install sockets
RUN apt-get update && \
    apt-get install -y libzip-dev && \
    docker-php-ext-install zip

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application files
COPY . /var/www/app

RUN chmod -R 777 /var/www/app

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]