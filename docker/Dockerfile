ARG PHP_CLI_IMAGE=php:8.4-cli-bookworm
ARG COMPOSER_IMAGE=composer:2
ARG PECL_REDIS_VERSION=6.3.0
ARG PECL_REDIS_URL=https://github.com/phpredis/phpredis/archive/refs/tags/6.3.0.tar.gz

FROM ${PHP_CLI_IMAGE} AS base

WORKDIR /var/www/html

ARG PECL_REDIS_VERSION
ARG PECL_REDIS_URL

# 使用国内 Debian 源，加速 apt 安装（Bookworm：deb822 或经典 sources.list）
RUN set -eux; \
    if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
        sed -i 's|deb.debian.org|mirrors.aliyun.com|g' /etc/apt/sources.list.d/debian.sources; \
        sed -i 's|security.debian.org|mirrors.aliyun.com|g' /etc/apt/sources.list.d/debian.sources; \
    elif [ -f /etc/apt/sources.list ]; then \
        sed -i 's|deb.debian.org|mirrors.aliyun.com|g' /etc/apt/sources.list; \
        sed -i 's|security.debian.org|mirrors.aliyun.com|g' /etc/apt/sources.list; \
    fi

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
        postgresql-client \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        gd \
        mbstring \
        pdo_pgsql \
        pdo_sqlite \
        pcntl \
        zip \
    && curl -fsSL "${PECL_REDIS_URL}" -o /tmp/redis.tgz \
    && mkdir -p /tmp/redis-src \
    && tar -xzf /tmp/redis.tgz -C /tmp/redis-src --strip-components=1 \
    && cd /tmp/redis-src \
    && phpize \
    && ./configure --enable-redis \
    && make -j"$(nproc)" \
    && make install \
    && cd /var/www/html \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/redis.tgz /tmp/redis-src \
    && rm -rf /var/lib/apt/lists/*

FROM ${COMPOSER_IMAGE} AS composer

FROM base

WORKDIR /var/www/html

COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_PROCESS_TIMEOUT=2000

ARG COMPOSER_PACKAGIST_MIRROR=
RUN if [ -n "${COMPOSER_PACKAGIST_MIRROR}" ]; then \
        composer config -g repo.packagist composer "${COMPOSER_PACKAGIST_MIRROR}"; \
    fi

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-dev --no-scripts

COPY . .

# 仅刷新 autoload；不调 artisan / composer 脚本（镜像为 --no-dev，构建期无 Boost 等 dev 包）
RUN composer dump-autoload --optimize --no-interaction --no-scripts

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY docker/php/opcache-dev.ini /usr/local/etc/php/conf.d/zz-opcache-dev.ini
COPY docker/php/php-docker-overrides.ini /usr/local/etc/php/conf.d/99-overrides.ini

RUN cp docker/entrypoint.sh /usr/local/bin/geoflow-entrypoint \
    && chmod +x /usr/local/bin/geoflow-entrypoint

ENTRYPOINT ["/usr/local/bin/geoflow-entrypoint"]
CMD ["php", "artisan", "about"]
