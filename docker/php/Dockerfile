FROM dunglas/frankenphp:1.9.1-php8.4-alpine

# Copy composer from official image
COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/

# Install system dependencies
RUN \
    set -ex && \
    apk update && \
    apk add --no-cache \
        git \
        nodejs \
        npm \
        postgresql-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        icu-dev \
        jpegoptim \
        optipng \
        pngquant \
        gifsicle \
        libavif \
        supervisor \
        dcron \
        linux-headers \
        build-base \
        autoconf \
        cmake \
        make

# Install and configure PHP extensions
RUN \
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

# Configure PHP settings
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/memory-limit.ini

# Install Node.js tools
RUN npm install -g corepack
RUN corepack enable && corepack prepare yarn@4.10.3 --activate

# Copy supervisor configuration
COPY docker/php/supervisord/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www

# Expose ports for HTTP, HTTPS, and HTTP/3
EXPOSE 80 443 443/udp

# Use supervisor as entrypoint to manage multiple processes
ENTRYPOINT ["supervisord", "--nodaemon", "--configuration", "/etc/supervisor/conf.d/supervisord.conf"]
