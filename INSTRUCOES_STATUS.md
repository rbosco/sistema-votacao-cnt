# Instruções para Corrigir o Status das Propostas

## Problema

O erro ocorre porque o SQL Server mantém uma constraint CHECK antiga na coluna `status` que só permite os valores `em_votacao` e `encerrada`. Precisamos atualizar essa constraint para aceitar o novo valor `nao_iniciada`.

## Solução - Opção 1: Executar Script SQL Manualmente (RECOMENDADO)

Esta é a forma mais rápida e segura:

1. Abra o **SQL Server Management Studio** ou qualquer ferramenta de gestão do SQL Server
2. Conecte-se ao banco de dados `DBFSRTCNT`
3. Execute o script localizado em:
   ```
   codigo-fonte/servico/database/fix_status_constraint.sql
   ```
4. O script irá:
   - Remover a constraint CHECK antiga
   - Remover o default constraint antigo
   - Modificar a coluna para NVARCHAR(20)
   - Adicionar novo default 'nao_iniciada'
   - Criar nova constraint CHECK com os 3 valores

## Solução - Opção 2: Executar Migration do Laravel

Se preferir usar o Laravel:

1. **IMPORTANTE**: Faça backup do banco de dados primeiro!

2. Execute a migration:
   ```bash
   cd codigo-fonte/servico
   php artisan migrate
   ```

## Verificação

Após executar qualquer uma das opções acima, teste se a atualização funciona:

1. Acesse o painel administrativo
2. Vá em "Propostas"
3. Tente alterar o status de uma proposta usando o select
4. O erro não deve mais aparecer

## Valores Aceitos Agora

- `nao_iniciada` - Votação ainda não começou
- `em_votacao` - Votação ativa
- `encerrada` - Votação finalizada

## Em Caso de Dúvida

Se o erro persistir, verifique:

1. Se a constraint antiga foi realmente removida:
   ```sql
   SELECT CONSTRAINT_NAME
   FROM INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE
   WHERE TABLE_NAME = 'propostas' AND COLUMN_NAME = 'status'
   ```

2. Se a nova constraint foi adicionada:
   ```sql
   SELECT name FROM sys.check_constraints
   WHERE parent_object_id = OBJECT_ID('propostas')
   ```
