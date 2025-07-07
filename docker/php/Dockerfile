FROM dunglas/frankenphp:1.6-php8.4-alpine

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/

RUN \
    set -ex && \
    apk update && \
    apk add --no-cache libstdc++ libpq && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev postgresql-dev && \
    apk add --no-cache supervisor && \
    apk add --no-cache supercronic && \
    apk add --no-cache freetype-dev libjpeg-turbo-dev libpng-dev && \
    apk add --no-cache jpegoptim optipng pngquant gifsicle libavif && \
    apk add --no-cache certbot && \
    # Install required PHP extensions
    install-php-extensions \
        exif \
        pcntl \
        gd \
        redis \
        pgsql \
        pdo_pgsql \
        zip && \
    # Cleanup
    apk del .build-deps

RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/memory-limit.ini

RUN apk add --no-cache nodejs npm git
RUN npm install -g corepack
RUN corepack enable && corepack prepare yarn@4.6.0 --activate

# Set working directory
WORKDIR "/var/www/"

# Copy all application code
COPY . /var/www/

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader || true

# Create an empty .env file if it doesn't exist
RUN test -f /var/www/.env || touch /var/www/.env

# Set proper permissions
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Install JS dependencies and build assets
RUN yarn install --frozen-lockfile && yarn build

# SSL certificates are now generated during deployment
RUN mkdir -p /etc/letsencrypt

COPY docker/php/supervisord/supervisord.conf /etc/

# Prepare for FrankenPHP
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile

EXPOSE 8000 80 443

ENTRYPOINT ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]
