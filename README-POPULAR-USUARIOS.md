# Script de População de Usuários - SQL Server

Este documento explica como usar o script para popular a tabela de usuários no SQL Server.

## 📋 Pré-requisitos

- Banco de dados SQL Server criado
- Tabela `usuarios` criada (executar migrations Laravel primeiro)
- Acesso ao SQL Server Management Studio (SSMS), Azure Data Studio ou sqlcmd

## 🚀 Como Executar

### Opção 1: SQL Server Management Studio (SSMS)

1. Abra o SSMS e conecte ao servidor
2. Abra o arquivo `script-popular-usuarios-sqlserver.sql`
3. **IMPORTANTE**: Na linha 8, altere `[nome_do_banco]` para o nome real do seu banco
4. Execute o script (F5 ou clique em "Execute")

### Opção 2: Azure Data Studio

1. Abra o Azure Data Studio e conecte ao servidor
2. Abra o arquivo `script-popular-usuarios-sqlserver.sql`
3. **IMPORTANTE**: Na linha 8, altere `[nome_do_banco]` para o nome real do seu banco
4. Execute o script (F5 ou clique em "Run")

### Opção 3: sqlcmd (linha de comando)

```bash
# Edite o arquivo e substitua [nome_do_banco] pelo nome real
sqlcmd -S servidor -U usuario -P senha -i script-popular-usuarios-sqlserver.sql
```

## 👥 Usuários Criados

O script cria 6 usuários de exemplo:

### Administradores

| Nome | CPF | Senha | Tipo |
|------|-----|-------|------|
| Administrador do Sistema | 000.000.001-91 | admin123 | Admin |
| Carlos Ferreira | 999.888.777-66 | admin123 | Admin |

### Usuários Normais

| Nome | CPF | Senha | Tipo |
|------|-----|-------|------|
| João Silva | 123.456.789-01 | 123456 | Usuário |
| Maria Santos | 987.654.321-09 | 123456 | Usuário |
| Pedro Oliveira | 111.222.333-44 | 123456 | Usuário |
| Ana Costa | 555.666.777-88 | 123456 | Usuário |

## 🔐 Senhas

**IMPORTANTE**: As senhas estão criptografadas com bcrypt (padrão Laravel).

- **Senha dos administradores**: `admin123`
- **Senha dos usuários normais**: `123456`

## ⚠️ Avisos Importantes

1. **Não execute em produção sem revisar**: Este script é para desenvolvimento/testes
2. **Altere as senhas padrão**: Em produção, use senhas fortes e únicas
3. **CPFs são fictícios**: Use CPFs reais válidos em produção
4. **Backup primeiro**: Sempre faça backup antes de executar scripts no banco

## 🔄 Executar Múltiplas Vezes

O script é **idempotente**, ou seja:
- Pode ser executado várias vezes sem problemas
- Verifica se cada usuário já existe antes de inserir
- Se o usuário já existir, pula a inserção

## 🗑️ Limpar Usuários Existentes

Se você quiser **apagar todos os usuários** antes de popular:

1. Descomente as linhas 21-23 do script:
   ```sql
   DELETE FROM [dbo].[usuarios];
   PRINT '🗑️  Usuários existentes removidos';
   ```
2. Execute o script

**ATENÇÃO**: Isso apagará TODOS os usuários do banco!

## 📊 Saída Esperada

Ao executar o script, você verá mensagens como:

```
✓ Usuário Admin criado (CPF: 000.000.001-91, Senha: admin123)
✓ Usuário João Silva criado (CPF: 123.456.789-01, Senha: 123456)
...

==========================================
  RESUMO DA POPULAÇÃO DE USUÁRIOS
==========================================
Total de usuários: 6
Administradores: 2
Usuários normais: 4

CREDENCIAIS DE ACESSO:
----------------------------------------
ADMIN: CPF 000.000.001-91 | Senha: admin123
USER:  CPF 123.456.789-01 | Senha: 123456
==========================================
```

## 🔧 Personalização

Para adicionar mais usuários, copie e cole um dos blocos de INSERT, alterando:
- `nome`: Nome completo do usuário
- `cpf`: CPF sem formatação (apenas números)
- `senha`: Hash bcrypt da senha desejada
- `is_admin`: 1 para admin, 0 para usuário normal

### Gerando Hash de Senha

Para gerar um hash bcrypt de uma senha customizada, use o tinker do Laravel:

```bash
php artisan tinker
```

Depois execute:
```php
echo Hash::make('sua_senha_aqui');
```

Copie o hash gerado e use no script SQL.

## 📝 Estrutura da Tabela

```sql
CREATE TABLE [usuarios] (
    [id] BIGINT IDENTITY(1,1) PRIMARY KEY,
    [nome] NVARCHAR(255) NOT NULL,
    [cpf] NVARCHAR(11) UNIQUE NOT NULL,
    [senha] NVARCHAR(255) NOT NULL,
    [is_admin] BIT DEFAULT 0,
    [remember_token] NVARCHAR(100) NULL,
    [created_at] DATETIME NULL,
    [updated_at] DATETIME NULL
);
```

## 📞 Suporte

Em caso de problemas:
1. Verifique se a tabela `usuarios` existe
2. Verifique as permissões do usuário SQL
3. Verifique se o nome do banco está correto
4. Consulte os logs de erro do SQL Server
