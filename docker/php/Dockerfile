# Stage 1: Build PHP extensions
FROM dunglas/frankenphp:1.9.1-php8.4-alpine AS builder

RUN set -ex && \
    apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        icu-dev \
        linux-headers \
        build-base \
        autoconf \
        cmake \
        make && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure zip && \
    docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        zip \
        gd \
        intl \
        exif \
        sockets \
        pcntl && \
    pecl install redis && \
    docker-php-ext-enable redis

# Stage 2: Production image (no build tools)
FROM dunglas/frankenphp:1.9.1-php8.4-alpine

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/

RUN apk add --no-cache \
    libpq \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    libavif \
    nodejs \
    npm \
    supervisor \
    curl

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache-prod.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache-prod.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache-prod.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache-prod.ini

RUN npm install -g corepack && \
    corepack enable && corepack prepare yarn@4.10.3 --activate

COPY docker/php/supervisord/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www

EXPOSE 80 443 443/udp

ENTRYPOINT ["supervisord", "--nodaemon", "--configuration", "/etc/supervisor/conf.d/supervisord.conf"]
