#!/bin/sh
set -e

echo "🚀 Iniciando container do serviço..."

APP_ENV="${APP_ENV:-local}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_DIR="/var/www/html"

echo "🌍 Ambiente: ${APP_ENV}"
cd "$APP_DIR"

# 1. Composer
if [ ! -d "vendor" ]; then
    echo "📦 Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "✅ Dependências do Composer já instaladas"
fi

# 2. .env por ambiente
if [ ! -f ".env" ]; then
    echo "📝 Configurando arquivo .env para ambiente: ${APP_ENV}"

    case "$APP_ENV" in
        development|dev)
            if [ -f ".env.dev.example" ]; then
                cp .env.dev.example .env
                echo "✅ Usando .env.dev.example"
            else
                cp .env.example .env
                echo "⚠️  .env.dev.example não encontrado, usando .env.example"
            fi
            ;;
        production|prod)
            if [ -f ".env.prod.example" ]; then
                cp .env.prod.example .env
                echo "✅ Usando .env.prod.example"
            else
                cp .env.example .env
                echo "⚠️  .env.prod.example não encontrado, usando .env.example"
            fi
            ;;
        *)
            cp .env.example .env
            echo "✅ Usando configurações LOCAL/DOCKER (.env.example)"
            ;;
    esac
fi

# 3. APP_KEY
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate --force
else
    echo "✅ Chave da aplicação já existe"
fi

# 4. Esperar DB (simples) – evita o "Login failed..." logo na subida
if [ -n "$DB_HOST" ]; then
    echo "⏳ Aguardando banco ($DB_HOST:$DB_PORT) ficar pronto..."
    DB_PORT="${DB_PORT:-1433}"
    for i in 1 2 3 4 5 6 7 8 9 10; do
        nc -z "$DB_HOST" "$DB_PORT" >/dev/null 2>&1 && break
        echo "  ...tentando conectar ($i/10)"
        sleep 3
    done
fi

# 5. Migrations (mas sem derrubar o container se falhar)
echo "🔄 Executando migrations..."
if ! php artisan migrate --force; then
    echo "⚠️  Não foi possível rodar migrations agora. Container vai subir mesmo assim."
fi

# 6. NÃO usar tinker no entrypoint – muito pesado
# Se quiser semear sempre em dev:
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ] || [ "$APP_ENV" = "dev" ]; then
    echo "🌱 Executando seeders (ambiente dev)..."
    php artisan db:seed --force || echo "⚠️  Seeder falhou, seguindo..."
else
    echo "⏭️  Ambiente de produção - seeders não serão executados automaticamente"
fi

# 7. Limpa caches
echo "🧹 Limpando cache..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 8. Otimiza se não estiver em debug
if [ "$APP_DEBUG" != "true" ] && [ "$APP_DEBUG" != "1" ]; then
    echo "⚡ Otimizando aplicação para produção..."
    php artisan config:cache || true
    php artisan route:cache || true
fi

echo "✅ Container do serviço configurado com sucesso!"
echo "🛠  Iniciando Apache (imagem php:8.3-apache-*)..."

# 9. IMPORTANTE: imagem apache -> apache2-foreground
exec apache2-foreground
