FROM php:8.3-fpm-alpine

# Instalamos dependencias y extensiones
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Añadimos mysqli a la lista de instalación
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && apk del $PHPIZE_DEPS
