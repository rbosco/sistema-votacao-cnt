#!/bin/sh
set -e

echo "🚀 Iniciando container do cliente..."

# Verificar se node_modules existe
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependências do NPM..."
    npm install
else
    echo "✅ Dependências já instaladas"
fi

echo "🎨 Iniciando servidor de desenvolvimento Vite..."
exec npm run dev -- --host
