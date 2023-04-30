ARG PHP_VERSION="7.2"

FROM php:${PHP_VERSION}

RUN --mount=type=tmpfs,target=/tmp \
    --mount=type=cache,sharing=locked,target=/var/cache/apt \
    --mount=type=cache,sharing=locked,target=/var/lib/apt \
    set -o errexit -o xtrace; \
    apt-get --quiet --yes update; \
    apt-get --quiet --yes install unzip; \
    chown --verbose --recursive www-data:www-data /var/www

# Install composer 1 & 2.
COPY --from="composer:2" /usr/bin/composer /usr/local/bin/composer
COPY --from="composer:1" /usr/bin/composer /usr/local/bin/composer1

USER www-data

WORKDIR /var/www/html

RUN set -o errexit -o xtrace; \
    cp --verbose --recursive /etc/skel/. /var/www/; \
    mkdir --verbose --parents /var/www/html/vendor

VOLUME /var/www/html/vendor
