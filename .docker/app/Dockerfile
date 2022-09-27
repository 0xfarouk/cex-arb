# Native image
FROM php:8.1.1-apache

#
# Packages
#

RUN apt-get update -y  \
    && apt-get install -y --no-install-recommends \
    graphviz libpng-dev libzip-dev libicu-dev libfreetype6-dev libjpeg62-turbo-dev \
    default-mysql-client  git zip unzip wget gnupg2

RUN apt-get clean \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

#
# PHP
#

RUN docker-php-ext-configure zip; \
    docker-php-ext-configure intl; \
    docker-php-ext-configure mysqli --with-mysqli=mysqlnd; \
    docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd; \
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j "$(nproc)" gd intl mysqli opcache pdo_mysql zip exif

RUN mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

#
# Composer
#

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_MEMORY_LIMIT=-1

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

#
# Apache
#

RUN a2enmod rewrite
COPY .docker/app/vhost.conf /etc/apache2/sites-available/000-default.conf

# Perms
RUN usermod -u 1000 www-data
