FROM php:7.4.15-cli-alpine

RUN apk add --update --no-cache git openssh zip unzip p7zip
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --from=composer /usr/bin/composer /usr/bin/composer
