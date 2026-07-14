FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y git unzip libicu-dev libzip-dev default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
