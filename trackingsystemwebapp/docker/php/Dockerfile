FROM php:7.4-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libssl-dev \
    zlib1g-dev \
    unzip \
    libzip-dev \
    && pecl install mongodb-1.15.0 \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install zip

# Set working directory
WORKDIR /var/www

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer