FROM php:8.4-fpm

COPY php.ini /usr/local/etc/php/

RUN apt update \
    && apt install -y --no-install-recommends \
        default-mysql-client \
        zlib1g-dev \
        libzip-dev \
        unzip \
        curl \
        git \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www