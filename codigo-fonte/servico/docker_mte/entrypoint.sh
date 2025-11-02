#!/bin/sh
# echo "DB Migration"
# php artisan migrate --force

echo "Configurando o cache da aplicação"
# php artisan key:generate
php artisan config:cache

echo "Limpando o cache, configurações e view"
php artisan view:clear
php artisan cache:clear
php artisan config:clear

echo "Verificar se existe laravel.log na pasta storage"
if [ -e storage/logs/laravel.log ]
then
    echo "Existe volume com laravel.log"
else
    echo "Não existe arquivo laravel.log"
    touch storage/logs/laravel.log
    echo "Criado laravel.log"
fi

echo "Permissões de acesso a pasta storage"
chmod -R 777 storage

echo "Back - Finzalizando o Endpoint da Application"
