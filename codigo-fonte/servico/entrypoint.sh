#!/bin/bash
set -e

echo "🚀 Iniciando container do serviço..."

# Aguardar o SQL Server estar pronto
echo "⏳ Aguardando SQL Server estar disponível..."
until /opt/mssql-tools18/bin/sqlcmd -S sqlserver -U sa -P "${DB_PASSWORD}" -C -Q "SELECT 1" > /dev/null 2>&1; do
    echo "   SQL Server não disponível ainda - aguardando..."
    sleep 3
done
echo "✅ SQL Server disponível!"

# Verificar se vendor existe
if [ ! -d "vendor" ]; then
    echo "📦 Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "✅ Dependências do Composer já instaladas"
fi

# Gerar chave da aplicação se não existir
if [ ! -f ".env" ]; then
    echo "📝 Copiando arquivo .env..."
    cp .env.example .env
fi

if grep -q "APP_KEY=$" .env || ! grep -q "APP_KEY=" .env; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate --force
else
    echo "✅ Chave da aplicação já existe"
fi

# Criar banco de dados se não existir
echo "🗄️  Verificando banco de dados..."
DB_NAME="${DB_DATABASE:-sistema_votacao_cnt}"
DB_EXISTS=$(/opt/mssql-tools18/bin/sqlcmd -S sqlserver -U sa -P "${DB_PASSWORD}" -C -Q "SELECT name FROM sys.databases WHERE name = N'${DB_NAME}'" -h -1 | grep -c "${DB_NAME}" || true)

if [ "$DB_EXISTS" -eq "0" ]; then
    echo "   Criando banco de dados ${DB_NAME}..."
    /opt/mssql-tools18/bin/sqlcmd -S sqlserver -U sa -P "${DB_PASSWORD}" -C -Q "CREATE DATABASE ${DB_NAME}"
    echo "✅ Banco de dados criado!"
else
    echo "✅ Banco de dados já existe"
fi

# Executar migrations
echo "🔄 Executando migrations..."
php artisan migrate --force

# Executar seeders apenas se a tabela usuarios estiver vazia
USERS_COUNT=$(php artisan tinker --execute="echo App\\Models\\Usuario::count();" 2>/dev/null || echo "0")
if [ "$USERS_COUNT" -eq "0" ]; then
    echo "🌱 Executando seeders..."
    php artisan db:seed --force
else
    echo "⏭️  Seeders já foram executados (usuários existem)"
fi

# Limpar cache
echo "🧹 Limpando cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção (se não estiver em modo debug)
if [ "${APP_DEBUG}" != "true" ]; then
    echo "⚡ Otimizando aplicação..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "✅ Container do serviço configurado com sucesso!"
echo "🎯 Iniciando PHP-FPM..."

# Iniciar PHP-FPM
exec php-fpm
