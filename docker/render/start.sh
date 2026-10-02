#!/bin/sh
# Inicialização no Render: porta dinâmica, caches de produção, migrations e
# seed idempotente (admin via variáveis de ambiente, tratamentos, horários
# provisórios só se ainda não existirem, dados de demonstração só uma vez).
set -e

PORT="${PORT:-10000}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ "${APP_DEBUG:-false}" = "true" ]; then
    echo "ERRO: APP_DEBUG=true em produção — abortando." >&2
    exit 1
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force
php artisan db:seed --force

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
