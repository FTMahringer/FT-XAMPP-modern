# -----------------------------------------
# PHP 8.4 + Apache (FT-XAMPP compatible)
# -----------------------------------------
FROM php:8.4-apache

# Copy your custom PHP config
COPY php.ini /usr/local/etc/php/php.ini

# Install required system packages + PHP extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      git unzip zip curl \
      libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
      libonig-dev libxml2-dev libcurl4-openssl-dev libxslt1-dev \
      libicu-dev libssl-dev build-essential \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
      gd pdo_mysql mbstring zip opcache exif xsl soap sockets intl \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && a2enmod rewrite headers expires \
 && rm -rf /var/lib/apt/lists/*

# Composer (from official installer)
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php \
 && composer --version

# Environment for Composer
ENV COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_PROCESS_TIMEOUT=2000 \
    COMPOSER_ALLOW_SUPERUSER=1

# Working directory -> htdocs
WORKDIR /var/www/html

# Healthcheck (requires curl)
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
  CMD curl -fsS http://localhost/ || exit 1

# Default ports
EXPOSE 80

# Launch Apache
CMD ["apache2-foreground"]
