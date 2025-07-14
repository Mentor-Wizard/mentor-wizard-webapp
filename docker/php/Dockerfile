FROM php:8.4.5-cli-alpine3.21

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/

RUN \
    set -ex && \
    apk update && \
    apk add --no-cache libstdc++ libpq && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev linux-headers postgresql-dev openssl-dev pcre-dev pcre2-dev zlib-dev icu-dev && \
    apk add --no-cache supervisor && \
    apk add --no-cache supercronic && \
    apk add --no-cache freetype-dev libjpeg-turbo-dev libpng-dev && \
    apk add --no-cache jpegoptim optipng pngquant gifsicle libavif && \
#    apk add --no-cache certbot && \
    pecl channel-update pecl.php.net && \
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
    docker-php-ext-install intl && \
    docker-php-ext-install gd && \
    rm -f swoole.tar.gz && \
    docker-php-source delete && \
    apk del .build-deps

RUN apk add --no-cache git nodejs=22.15.1-r0 npm
RUN npm install -g corepack
RUN corepack enable && corepack prepare yarn@4.6.0 --activate

#RUN certbot --staging certonly --standalone -d mentor-wizard.com --email mentor.wizard.ua@gmail.com --agree-tos --non-interactive

COPY docker/php/supervisord/supervisord.conf /etc/

EXPOSE 8000

WORKDIR "/var/www/"

ENTRYPOINT ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]
