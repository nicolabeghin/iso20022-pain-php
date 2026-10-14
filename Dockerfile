# Test/dev image: PHP CLI with the extensions the library needs plus Composer.
FROM php:8.2-cli-alpine

RUN apk add --no-cache icu-dev libxml2-dev git unzip $PHPIZE_DEPS \
    && docker-php-ext-install intl \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
