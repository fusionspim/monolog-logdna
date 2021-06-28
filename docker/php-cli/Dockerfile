FROM php:7.4.15-cli

RUN apt-get update && apt-get install -yqq git zip unzip
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --from=composer /usr/bin/composer /usr/bin/composer
