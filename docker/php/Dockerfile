FROM php:8.4.4-cli-alpine3.21

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/

RUN \
    set -ex && \
    apk update && \
    apk add --no-cache libstdc++ libpq && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev linux-headers postgresql-dev openssl-dev pcre-dev pcre2-dev zlib-dev libzip-dev && \
    apk add --no-cache supervisor && \
    apk add --no-cache supercronic && \
    apk add --no-cache freetype-dev libjpeg-turbo-dev libpng-dev && \
    apk add --no-cache jpegoptim optipng pngquant gifsicle libavif && \
    apk add --no-cache libzip && \
    apk add --no-cache certbot && \
    pecl channel-update pecl.php.net && \
    docker-php-ext-configure zip && \
    docker-php-ext-install zip && \
    docker-php-ext-configure gd --with-freetype=shared --with-jpeg=shared && \
    docker-php-ext-install sockets exif && \
    docker-php-ext-enable exif && \
    pecl install redis &&  \
    docker-php-ext-enable redis && \
    docker-php-source extract && \
    mkdir /usr/src/php/ext/swoole && \
    curl -sfL https://github.com/swoole/swoole-src/archive/v6.0.0.tar.gz -o swoole.tar.gz && \
    tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole && \
    docker-php-ext-configure swoole \
        --enable-swoole-pgsql \
        --enable-openssl      \
        --enable-sockets --enable-swoole-curl && \
    docker-php-ext-install -j$(nproc) swoole && \
    docker-php-ext-configure pcntl --enable-pcntl && \
    docker-php-ext-install pcntl && \
    docker-php-ext-configure gd && \
    docker-php-ext-install gd && \
    rm -f swoole.tar.gz && \
    docker-php-source delete && \
    apk del .build-deps

RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/memory-limit.ini

RUN apk add --no-cache nodejs npm git
RUN npm install -g corepack
RUN corepack enable && corepack prepare yarn@4.6.0 --activate

# Copy application code
COPY . /var/www/

# Create an empty .env file if it doesn't exist
RUN test -f /var/www/.env || touch /var/www/.env

# Set proper permissions
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Install JS dependencies and build assets
RUN yarn install --frozen-lockfile && yarn build

# Generate SSL certificate
RUN mkdir -p /etc/letsencrypt/live/mentor-wizard.com
RUN certbot certonly --standalone --staging --non-interactive --agree-tos --email mentor.wizard.ua@gmail.com -d mentor-wizard.com || true

COPY docker/php/supervisord/supervisord.conf /etc/

EXPOSE 8000 80 443

WORKDIR "/var/www/"

ENTRYPOINT ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]
