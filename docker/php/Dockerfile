FROM php:8.4-apache

# Copy your custom php.ini
COPY php.ini /usr/local/etc/php/php.ini

# Install needed packages, enable PHP extensions and Apache mods
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
       git unzip zip \
       libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
       libonig-dev libxml2-dev libcurl4-openssl-dev libxslt1-dev \
       libicu-dev libssl-dev \
       build-essential \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
       gd pdo_mysql mbstring zip opcache exif xsl soap sockets intl \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && a2enmod rewrite headers expires \
 && rm -rf /var/lib/apt/lists/*

# Composer (from official image)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Optional: set Composer defaults (no memory limit, longer timeout)
ENV COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_PROCESS_TIMEOUT=2000

WORKDIR /var/www/html

# Simple HTTP healthcheck
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

EXPOSE 80
CMD ["apache2-foreground"]
