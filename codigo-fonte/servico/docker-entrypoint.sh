#!/bin/bash
set -e

echo "=========================================="
echo "Sistema de Votacao CNT - Iniciando..."
echo "=========================================="

# Criar diretorios necessarios
echo "[1/7] Criando diretorios..."
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Configurar .env
echo "[2/7] Configurando .env..."
if [ ! -f ".env" ]; then
    cp .env.example .env

    # Aplicar variaveis de ambiente do docker-compose
    if [ -n "${DB_HOST}" ]; then
        sed -i "s|DB_HOST=.*|DB_HOST=${DB_HOST}|" .env
    fi
    if [ -n "${DB_PORT}" ]; then
        sed -i "s|DB_PORT=.*|DB_PORT=${DB_PORT}|" .env
    fi
    if [ -n "${DB_DATABASE}" ]; then
        sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|" .env
    fi
    if [ -n "${DB_USERNAME}" ]; then
        sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME}|" .env
    fi
    if [ -n "${DB_PASSWORD}" ]; then
        sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
    fi
    if [ -n "${APP_ENV}" ]; then
        sed -i "s|APP_ENV=.*|APP_ENV=${APP_ENV}|" .env
    fi
fi

# Gerar APP_KEY
echo "[3/7] Gerando APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force
fi

# Migrations (tentar, mas nao falhar se der erro)
echo "[4/7] Executando migrations..."
php artisan migrate --force 2>&1 || echo "   AVISO: Migrations falharam (banco pode nao estar acessivel)"

# Limpar cache
echo "[5/7] Limpando cache..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Otimizar para producao
echo "[6/7] Otimizando aplicacao..."
if [ "${APP_DEBUG}" != "true" ]; then
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
fi

# Criar log file
touch storage/logs/laravel.log 2>/dev/null || true
chmod 666 storage/logs/laravel.log 2>/dev/null || true

echo "[7/7] Iniciando Apache..."
echo "=========================================="
echo "Sistema pronto! Escutando na porta 80"
echo "=========================================="

# Iniciar Apache
exec apache2-foreground
