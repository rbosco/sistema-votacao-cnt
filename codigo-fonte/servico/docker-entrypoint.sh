#!/bin/bash
set -e

echo "🚀 Iniciando Sistema de Votação CNT..."

# -----------------------------------------------------
# Criar diretórios se não existirem (volumes podem sobrescrever)
# -----------------------------------------------------
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# -----------------------------------------------------
# Configurar .env usando variáveis de ambiente
# -----------------------------------------------------
if [ ! -f ".env" ] || [ "${FORCE_ENV_RESET}" = "true" ]; then
    echo "📝 Configurando arquivo .env..."
    cp .env.example .env

    # Substituir variáveis com valores do docker-compose
    if [ -n "${APP_ENV}" ]; then
        sed -i "s/APP_ENV=.*/APP_ENV=${APP_ENV}/" .env
    fi
    if [ -n "${DB_HOST}" ]; then
        sed -i "s/DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
    fi
    if [ -n "${DB_PORT}" ]; then
        sed -i "s/DB_PORT=.*/DB_PORT=${DB_PORT}/" .env
    fi
    if [ -n "${DB_DATABASE}" ]; then
        sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
    fi
    if [ -n "${DB_USERNAME}" ]; then
        sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
    fi
    if [ -n "${DB_PASSWORD}" ]; then
        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
    fi
fi

# -----------------------------------------------------
# Gerar APP_KEY se necessário
# -----------------------------------------------------
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Gerando APP_KEY..."
    php artisan key:generate --force
fi

# -----------------------------------------------------
# Aguardar banco de dados (apenas em ambiente local)
# -----------------------------------------------------
if [ "${APP_ENV}" = "local" ] && [ "${DB_HOST}" = "sqlserver" ]; then
    echo "⏳ Aguardando SQL Server local..."
    until /opt/mssql-tools18/bin/sqlcmd -S "${DB_HOST}" -U "${DB_USERNAME}" -P "${DB_PASSWORD}" -C -Q "SELECT 1" > /dev/null 2>&1; do
        echo "   SQL Server não disponível ainda - aguardando..."
        sleep 3
    done
    echo "✅ SQL Server disponível!"
fi

# -----------------------------------------------------
# Executar migrations
# -----------------------------------------------------
echo "🔄 Executando migrations..."
php artisan migrate --force || echo "⚠️  Migrations falharam (banco pode não estar acessível)"

# -----------------------------------------------------
# Limpar e otimizar cache
# -----------------------------------------------------
echo "🧹 Limpando cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

if [ "${APP_DEBUG}" != "true" ]; then
    echo "⚡ Otimizando para produção..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# -----------------------------------------------------
# Criar arquivo de log se não existir
# -----------------------------------------------------
touch storage/logs/laravel.log
chmod 666 storage/logs/laravel.log

echo "✅ Sistema configurado e pronto!"
echo "🌐 Iniciando Apache..."

# -----------------------------------------------------
# Iniciar Apache em foreground
# -----------------------------------------------------
exec apache2-foreground
