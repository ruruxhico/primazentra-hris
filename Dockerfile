FROM php:8.4-apache

# Enable mod_rewrite for CI4 URL routing
RUN a2enmod rewrite

# System dependencies (libicu-dev needed for intl extension)
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zlib1g-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions (intl required by CI4 v4.7.2)
RUN docker-php-ext-install zip intl

# gRPC + Protobuf — required by google/cloud-firestore
# $PHPIZE_DEPS provides compilers needed by pecl
# MAKEFLAGS="-j1" limits parallel compilation to 1 thread to prevent OOM kill
RUN apt-get update && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && MAKEFLAGS="-j1" pecl install grpc protobuf \
    && docker-php-ext-enable grpc protobuf \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer manifests first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

<<<<<<< HEAD
# Copy the rest of the project files
=======
# Copy project files
>>>>>>> ebd9bfe3f4c5825559388747f6c6fd87aaec202d
COPY . .

# Point Apache document root to CI4's public/ folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/conf-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf

# Allow .htaccess to work (CI4 needs this for routing)
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Create CI4 writable directories with correct permissions
RUN mkdir -p writable/credentials writable/logs writable/cache \
             writable/session writable/uploads \
    && chown -R www-data:www-data writable \
    && chmod -R 775 writable

# Entrypoint writes Firebase credentials at container startup
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
