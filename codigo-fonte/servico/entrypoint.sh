#!/bin/bash
set -e

echo "🚀 Iniciando container do serviço..."

# Detectar ambiente
APP_ENV="${APP_ENV:-local}"
echo "🌍 Ambiente: ${APP_ENV}"

# Verificar se vendor existe
if [ ! -d "vendor" ]; then
    echo "📦 Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "✅ Dependências do Composer já instaladas"
fi

# Copiar arquivo .env baseado no ambiente se não existir
if [ ! -f ".env" ]; then
    echo "📝 Configurando arquivo .env para ambiente: ${APP_ENV}"

    case "${APP_ENV}" in
        development|dev)
            if [ -f ".env.dev.example" ]; then
                cp .env.dev.example .env
                echo "✅ Usando configurações de DESENVOLVIMENTO"
            else
                cp .env.example .env
                echo "⚠️  .env.dev.example não encontrado, usando .env.example"
            fi
            ;;
        production|prod)
            if [ -f ".env.prod.example" ]; then
                cp .env.prod.example .env
                echo "✅ Usando configurações de PRODUÇÃO"
            else
                cp .env.example .env
                echo "⚠️  .env.prod.example não encontrado, usando .env.example"
            fi
            ;;
        *)
            cp .env.example .env
            echo "✅ Usando configurações LOCAL/DOCKER"
            ;;
    esac
fi

# Carregar variáveis do .env
if [ -f ".env" ]; then
    export $(cat .env | grep -v '^#' | grep -v '^$' | xargs)
fi

# Gerar chave da aplicação se não existir
if grep -q "APP_KEY=$" .env || ! grep -q "APP_KEY=" .env; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate --force
else
    echo "✅ Chave da aplicação já existe"
fi

# Aguardar o banco de dados estar pronto (apenas para ambiente local)
if [ "${APP_ENV}" = "local" ]; then
    echo "⏳ Aguardando SQL Server local estar disponível..."
    DB_HOST="${DB_HOST:-sqlserver}"
    DB_USER="${DB_USERNAME:-sa}"
    DB_PASS="${DB_PASSWORD:-SistemaVotacao@2024}"

    until /opt/mssql-tools18/bin/sqlcmd -S "${DB_HOST}" -U "${DB_USER}" -P "${DB_PASS}" -C -Q "SELECT 1" > /dev/null 2>&1; do
        echo "   SQL Server não disponível ainda - aguardando..."
        sleep 3
    done
    echo "✅ SQL Server disponível!"

    # Criar banco de dados se não existir (apenas local)
    echo "🗄️  Verificando banco de dados..."
    DB_NAME="${DB_DATABASE:-sistema_votacao_cnt}"
    DB_EXISTS=$(/opt/mssql-tools18/bin/sqlcmd -S "${DB_HOST}" -U "${DB_USER}" -P "${DB_PASS}" -C -Q "SELECT name FROM sys.databases WHERE name = N'${DB_NAME}'" -h -1 | grep -c "${DB_NAME}" || true)

    if [ "$DB_EXISTS" -eq "0" ]; then
        echo "   Criando banco de dados ${DB_NAME}..."
        /opt/mssql-tools18/bin/sqlcmd -S "${DB_HOST}" -U "${DB_USER}" -P "${DB_PASS}" -C -Q "CREATE DATABASE ${DB_NAME}"
        echo "✅ Banco de dados criado!"
    else
        echo "✅ Banco de dados já existe"
    fi
else
    echo "🌐 Ambiente remoto detectado - pulando criação de banco"
    echo "   Host: ${DB_HOST:-não definido}"
    echo "   Database: ${DB_DATABASE:-não definido}"
fi

# Executar migrations
echo "🔄 Executando migrations..."
php artisan migrate --force

# Executar seeders apenas se a tabela usuarios estiver vazia (apenas em dev/local)
if [ "${APP_ENV}" = "local" ] || [ "${APP_ENV}" = "development" ] || [ "${APP_ENV}" = "dev" ]; then
    USERS_COUNT=$(php artisan tinker --execute="echo App\\Models\\Usuario::count();" 2>/dev/null || echo "0")
    if [ "$USERS_COUNT" -eq "0" ]; then
        echo "🌱 Executando seeders..."
        php artisan db:seed --force
    else
        echo "⏭️  Seeders já foram executados (usuários existem)"
    fi
else
    echo "⏭️  Ambiente de produção - seeders não serão executados automaticamente"
fi

# Limpar cache
echo "🧹 Limpando cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção (se não estiver em modo debug)
if [ "${APP_DEBUG}" != "true" ]; then
    echo "⚡ Otimizando aplicação para produção..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "✅ Container do serviço configurado com sucesso!"
echo "🎯 Iniciando PHP-FPM..."

# Iniciar PHP-FPM
exec php-fpm
