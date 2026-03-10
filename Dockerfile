FROM php:8.3-fpm-alpine

RUN apk add --no-cache linux-headers curl procps bash \
    && apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && apk del $PHPIZE_DEPS
