#!/bin/sh
set -e

echo "🚀 Iniciando container do serviço..."

APP_ENV="${APP_ENV:-local}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_DIR="/var/www/html"

echo "🌍 Ambiente: ${APP_ENV}"
cd "$APP_DIR"

# 0) Pastas obrigatórias do Laravel + permissões (ANTES de composer/artisan!)
echo "📁 Garantindo pastas e permissões de storage/ e bootstrap/cache..."
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# Dono do Apache/PHP no Debian é www-data
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 1) .env por ambiente (cria antes do composer para evitar scripts que rodem artisan)
if [ ! -f ".env" ]; then
  echo "📝 Configurando arquivo .env para ambiente: ${APP_ENV}"
  case "$APP_ENV" in
    development|dev)
      if [ -f ".env.dev.example" ]; then
        cp .env.dev.example .env && echo "✅ Usando .env.dev.example"
      else
        cp .env.example .env && echo "⚠️  .env.dev.example não encontrado, usando .env.example"
      fi
      ;;
    production|prod)
      if [ -f ".env.prod.example" ]; then
        cp .env.prod.example .env && echo "✅ Usando .env.prod.example"
      else
        cp .env.example .env && echo "⚠️  .env.prod.example não encontrado, usando .env.example"
      fi
      ;;
    *)
      cp .env.example .env && echo "✅ Usando configurações LOCAL/DOCKER (.env.example)"
      ;;
  esac
fi

# 2) APP_KEY
if ! grep -q "^APP_KEY=base64:" .env; then
  echo "🔑 Gerando chave da aplicação..."
  php artisan key:generate --force || true
else
  echo "✅ Chave da aplicação já existe"
fi

# 3) Composer (agora que pastas e .env existem)
if [ ! -d "vendor" ]; then
  echo "📦 Instalando dependências do Composer..."
  # Evita problemas rodando como root em container oficial
  export COMPOSER_ALLOW_SUPERUSER=1
  composer install --no-interaction --prefer-dist --optimize-autoloader
else
  echo "✅ Dependências do Composer já instaladas"
fi

# 4) Espera DB (ajuste de ordem do DB_PORT)
if [ -n "${DB_HOST:-}" ]; then
  DB_PORT="${DB_PORT:-1433}"
  echo "⏳ Aguardando banco (${DB_HOST}:${DB_PORT}) ficar pronto..."
  # 'nc' pode não existir: trate de instalar no Dockerfile (netcat-openbsd)
  i=1
  while [ $i -le 10 ]; do
    if nc -z "$DB_HOST" "$DB_PORT" >/dev/null 2>&1; then
      break
    fi
    echo "  ...tentando conectar ($i/10)"
    i=$((i+1))
    sleep 3
  done
fi

# 5) Migrations (não derruba se falhar)
echo "🔄 Executando migrations..."
php artisan migrate --force || echo "⚠️  Não foi possível rodar migrations agora. Container vai subir mesmo assim."

# 6) Seeders somente em dev
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ] || [ "$APP_ENV" = "dev" ]; then
  echo "🌱 Executando seeders (ambiente dev)..."
  php artisan db:seed --force || echo "⚠️  Seeder falhou, seguindo..."
else
  echo "⏭️  Ambiente de produção - seeders não serão executados automaticamente"
fi

# 7) Limpa caches
echo "🧹 Limpando cache..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 8) Reotimiza se não estiver em debug
if [ "$APP_DEBUG" != "true" ] && [ "$APP_DEBUG" != "1" ]; then
  echo "⚡ Otimizando aplicação para produção..."
  php artisan config:cache || true
  php artisan route:cache || true
fi

echo "✅ Container do serviço configurado com sucesso!"
echo "🛠  Iniciando Apache (imagem php:8.3-apache-*)..."
exec apache2-foreground
