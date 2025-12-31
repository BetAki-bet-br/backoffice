# docker/php/Dockerfile
FROM php:8.4-fpm-alpine

# SO e libs de runtime + build deps para phpize/pecl
RUN apk add --no-cache \
    bash git unzip \
    icu-data-full icu-dev oniguruma-dev \
    libpng libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    libzip-dev curl-dev \
    postgresql-dev libpq-dev \
    $PHPIZE_DEPS

# Extensões do PHP (pgsql, redis, gd, intl, zip, bcmath, opcache)
# Observação: gd precisa das flags de freetype/jpeg/webp quando instaladas via -dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd intl zip bcmath opcache pdo pdo_pgsql pcntl \
 && pecl install redis \
 && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP ini
COPY docker/php/conf.d/php.ini /usr/local/etc/php/conf.d/php.ini

# Usuário não-root
RUN addgroup -g 1000 www \
 && adduser -G www -g www -s /bin/sh -D www \
 && chown -R www:www /var/www/html

USER www

CMD ["php-fpm", "-F"]