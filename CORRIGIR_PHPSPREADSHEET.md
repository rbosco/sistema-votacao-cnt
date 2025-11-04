# Corrigir Erro "PhpSpreadsheet not found"

## Problema

O erro `Class "PhpOffice\PhpSpreadsheet\Spreadsheet" not found` está acontecendo porque o container Docker não tem o autoload do Composer atualizado.

## Solução

Execute os seguintes comandos:

### Opção 1: Reiniciar Container e Regenerar Autoload (RECOMENDADO)

```bash
# Entre no container do Laravel
docker-compose exec app bash

# Dentro do container, regenere o autoload
composer dump-autoload -o

# Limpe os caches do Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Saia do container
exit

# Reinicie os containers
docker-compose restart
```

### Opção 2: Reinstalar Dependências no Container

Se a Opção 1 não funcionar:

```bash
# Entre no container
docker-compose exec app bash

# Reinstale as dependências
composer install --optimize-autoloader

# Limpe os caches
php artisan optimize:clear

# Saia e reinicie
exit
docker-compose restart
```

### Opção 3: Rebuild do Container

Se nenhuma das opções acima funcionar:

```bash
# Pare os containers
docker-compose down

# Rebuild da imagem
docker-compose build --no-cache

# Suba novamente
docker-compose up -d

# Entre no container e instale dependências
docker-compose exec app bash
composer install --optimize-autoload
exit
```

## Verificação

Após executar uma das opções acima, teste:

1. Acesse o painel administrativo
2. Vá em **Propostas**
3. Clique em **"📥 Baixar Modelo"**
4. O arquivo Excel deve ser baixado sem erros

## Causa do Problema

O PhpSpreadsheet foi instalado localmente (`composer.json` está correto), mas o container Docker não tinha o autoload regenerado. Isso acontece quando:

- Dependências são instaladas fora do container
- O volume do Docker não sincroniza corretamente os arquivos do vendor
- O autoload não foi regenerado após instalação

## Arquivos Verificados

✅ PhpSpreadsheet está em `composer.json`
✅ Biblioteca instalada em `vendor/phpoffice/phpspreadsheet`
✅ Arquivo `Spreadsheet.php` existe
✅ Controllers usam os imports corretos

O problema é apenas de autoload no ambiente Docker.
