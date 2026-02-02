FROM php:8.3-fpm-alpine

# Instalamos dependencias temporales para compilar redis
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_mysql \
    && apk del $PHPIZE_DEPS
