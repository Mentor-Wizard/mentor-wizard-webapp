FROM serversideup/php:8.5-frankenphp

USER root

RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

RUN corepack enable && corepack prepare yarn@4.15.0 --activate

RUN install-php-extensions gd intl exif sockets

USER www-data

WORKDIR $APP_BASE_DIR

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN composer install --no-progress --no-scripts --no-interaction --prefer-dist --ansi

COPY --chown=www-data:www-data . .

RUN --mount=type=secret,id=VITE_REVERB_APP_KEY,uid=33 \
    --mount=type=secret,id=VITE_REVERB_SCHEME,uid=33 \
    --mount=type=secret,id=VITE_REVERB_HOST,uid=33 \
    --mount=type=secret,id=VITE_REVERB_PORT,uid=33 \
    export VITE_REVERB_APP_KEY=$(cat /run/secrets/VITE_REVERB_APP_KEY) \
    && export VITE_REVERB_SCHEME=$(cat /run/secrets/VITE_REVERB_SCHEME) \
    && export VITE_REVERB_HOST=$(cat /run/secrets/VITE_REVERB_HOST) \
    && export VITE_REVERB_PORT=$(cat /run/secrets/VITE_REVERB_PORT) \
    && yarn install --frozen-lockfile && yarn build

RUN APP_KEY=base64:placeholder php artisan vendor:publish \
    --tag=laravel-assets \
    --tag=log-viewer-assets \
    --tag=filament-assets \
    --force --no-interaction --ansi \
    && php artisan filament:upgrade --ansi
