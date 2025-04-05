FROM composer:lts AS composer

FROM php:8.2-cli

WORKDIR /app
VOLUME /app

RUN apt-get update; \
    apt-get install -y \
        git \
        libicu-dev \
		libfreetype-dev \
		libjpeg62-turbo-dev \
		libpng-dev \        
        unzip

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install -j$(nproc) gd intl

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --from=composer /usr/bin/composer /usr/bin/composer

EXPOSE 80
