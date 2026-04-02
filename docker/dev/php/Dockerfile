FROM serversideup/php:8.5-frankenphp

USER root

# Install Node.js 24
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Yarn 4.10.3
RUN corepack enable && corepack prepare yarn@4.10.3 --activate

# Install PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    gd \
    intl \
    zip \
    exif \
    sockets \
    pcntl \
    redis

USER www-data

WORKDIR $APP_BASE_DIR

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN composer install --no-progress --no-scripts --no-interaction --prefer-dist

COPY --chown=www-data:www-data . .

ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_SCHEME
ARG VITE_REVERB_PORT
ARG VITE_REVERB_HOST

RUN yarn install --frozen-lockfile && yarn build

# Publish vendor assets (log-viewer, filament)
RUN APP_KEY=base64:placeholder php artisan vendor:publish \
    --tag=laravel-assets \
    --tag=log-viewer-assets \
    --tag=filament-assets \
    --ansi --force --no-interaction \
    && php artisan filament:upgrade
