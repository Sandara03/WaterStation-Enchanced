FROM node:22-bookworm-slim AS frontend
WORKDIR /build/frontend
COPY frontend/ ./
RUN npm ci --no-audit --no-fund && AHY_COMBINED_BUILD=1 npm run build

FROM php:8.3-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libonig-dev libzip-dev libsqlite3-dev unzip \
    && docker-php-ext-install pdo_pgsql pdo_mysql pdo_sqlite mbstring bcmath opcache zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY backend/ ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --from=frontend /build/frontend/dist/spa/ ./public/app/
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/start-render.sh /usr/local/bin/start-waterstation
RUN chmod +x /usr/local/bin/start-waterstation
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=warning
EXPOSE 10000
CMD ["/usr/local/bin/start-waterstation"]
