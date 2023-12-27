FROM php:8.0-cli-alpine3.16
ARG UID
ARG GID
ENV UID=${UID}
ENV GID=${GID}

RUN apk update && \
    apk add --no-cache git g++ autoconf make pcre2-dev && \
    pecl install pcov && \
    docker-php-ext-enable pcov && \
    apk del --no-cache g++ autoconf make pcre2-dev && \
    curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer && \
    addgroup -g $GID -S dev &&  \
    adduser -u $UID -S dev --ingroup dev && \
    chown -R $UID:$GID /var/www/html

USER dev
WORKDIR /var/www/html
