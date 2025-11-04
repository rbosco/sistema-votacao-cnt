#!/bin/bash

# Script para corrigir o erro "PhpSpreadsheet not found" no container Docker

echo "======================================"
echo "Corrigindo PhpSpreadsheet no Docker"
echo "======================================"
echo ""

echo "1. Entrando no container phpsrt..."
docker exec -it phpsrt bash -c "cd /var/www/html && composer dump-autoload -o"

echo ""
echo "2. Limpando caches do Laravel..."
docker exec -it phpsrt bash -c "cd /var/www/html && php artisan config:clear && php artisan cache:clear && php artisan route:clear"

echo ""
echo "3. Reiniciando container..."
docker-compose restart phpsrt

echo ""
echo "======================================"
echo "✅ Correção concluída!"
echo "======================================"
echo ""
echo "Teste agora:"
echo "1. Acesse o painel administrativo"
echo "2. Vá em Propostas"
echo "3. Clique em 'Baixar Modelo'"
echo ""
echo "O arquivo Excel deve ser baixado sem erros."
echo ""
