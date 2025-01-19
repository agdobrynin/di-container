ARG PHP_IMAGE
FROM ${PHP_IMAGE:-php:8.1-cli-alpine}

ENV UID=1000
ENV GID=1000

RUN apk update && \
    apk add --no-cache git g++ autoconf make pcre2-dev && \
    pecl install pcov ast && \
    docker-php-ext-enable pcov ast && \
    apk del --no-cache g++ autoconf make pcre2-dev && \
    curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer && \
    addgroup -g $GID -S dev &&  \
    adduser -u $UID -S dev --ingroup dev && \
    chown -R $UID:$GID /var/www/html

USER dev
WORKDIR /var/www/html
