FROM php:8.4-fpm

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libpq-dev libzip-dev libonig-dev \
        libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev \
        libssl-dev libcurl4-openssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install -j$(nproc) intl zip opcache bcmath pdo_mysql gd

# Redis extension
RUN pecl install redis \
 && docker-php-ext-enable redis

# Xdebug (build optional; enable via php.ini if needed)
RUN pecl install xdebug \    && docker-php-ext-enable xdebug || true

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html