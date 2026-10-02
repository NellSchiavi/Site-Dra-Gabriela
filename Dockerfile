# Imagem de PRODUÇÃO da demo (Render). O ambiente local usa compose.yaml.
# Multi-stage: dependências PHP e build do front-end ficam em estágios
# separados; a imagem final leva só o necessário para rodar o site.

# --- 1) Dependências PHP (sem pacotes de desenvolvimento) -----------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts \
    --prefer-dist --optimize-autoloader --ignore-platform-reqs

# --- 2) Build do front-end (Vite + Tailwind + fontes empacotadas) ---------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# --- 3) Runtime: PHP 8.3 + Apache servindo apenas public/ ------------------
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql intl opcache zip \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    # Não anunciar versões do Apache/PHP
    && printf 'ServerName localhost\nServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-enabled/zz-seguranca.conf \
    && printf 'expose_php=Off\ndisplay_errors=Off\nopcache.enable=1\nopcache.validate_timestamps=0\n' > /usr/local/etc/php/conf.d/zz-producao.ini

# DocumentRoot = public/ — nada fora dele é servido pelo Apache.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN php artisan package:discover --ansi \
    && chmod +x docker/render/start.sh

EXPOSE 10000
CMD ["docker/render/start.sh"]
