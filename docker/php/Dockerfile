FROM php:7.4.15-fpm-buster

# Install required PHP extensions
RUN apt-get update \
    && apt-get install -y \
        libzip-dev zip unzip \
        libicu-dev \
    && docker-php-ext-install zip pdo_mysql intl pcntl

# Copy the Composer binary over from the official Docker image so we're not coupled to the latest PHP version/extensions it ships with
# @see https://github.com/docker-library/docs/tree/master/composer#php-version--extensions
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Copy over custom PHP configurations (applies to CLI and PHP-FPM)
COPY php.ini "$PHP_INI_DIR/php.ini"
