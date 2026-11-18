# syntax=docker/dockerfile:1.7

ARG WORDPRESS_BASE_IMAGE=wordpress:php8.3-apache
ARG PHP_REDIS_VERSION=6.3.0

FROM ${WORDPRESS_BASE_IMAGE} AS redis-extension-builder
ARG PHP_REDIS_VERSION

RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && pecl install redis-${PHP_REDIS_VERSION} \
    && docker-php-ext-enable redis

FROM ${WORDPRESS_BASE_IMAGE}

COPY --from=redis-extension-builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=redis-extension-builder /usr/local/etc/php/conf.d/docker-php-ext-redis.ini /usr/local/etc/php/conf.d/docker-php-ext-redis.ini

ENV OPCACHE_VALIDATE_TIMESTAMPS=1 \
    OPCACHE_REVALIDATE_FREQ=2 \
    REALPATH_CACHE_TTL=120

RUN a2enmod expires headers rewrite

COPY docker/apache-security.conf /etc/apache2/conf-available/opendoors-security.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/opendoors.ini
RUN a2enconf opendoors-security

COPY --chown=www-data:www-data . /var/www/html/

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=90s --retries=5 \
    CMD curl --fail --silent --show-error http://127.0.0.1/healthz.php >/dev/null || exit 1
