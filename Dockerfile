FROM php:8.0-cli-alpine3.16
ARG USER_ID
ARG GROUP_ID

RUN apk update && \
    apk add --no-cache git g++ autoconf make pcre2-dev && \
    pecl install pcov && \
    docker-php-ext-enable pcov && \
    apk del --no-cache g++ autoconf make pcre2-dev && \
    curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer && \
    addgroup -g $GROUP_ID -S dev &&  \
    adduser -u $USER_ID -S dev --ingroup dev && \
    chown -R $USER_ID:$GROUP_ID /var/www/html

USER dev
WORKDIR /var/www/html
