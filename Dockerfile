FROM php:8.4-fpm-bookworm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends $PHPIZE_DEPS pkg-config libicu-dev libonig-dev libxml2-dev libzip-dev git unzip

RUN set -eux; \
    docker-php-ext-install -j1 pdo_mysql mbstring intl dom xml xmlreader xmlwriter zip; \
    php -m | grep -E 'dom|intl|mbstring|pdo_mysql|xml|zip'

RUN rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .
RUN composer dump-autoload --optimize --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]
