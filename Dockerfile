FROM php:8.5-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS linux-headers icu-dev oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring intl opcache \
    && apk del $PHPIZE_DEPS linux-headers

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .
RUN composer dump-autoload --optimize --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]
