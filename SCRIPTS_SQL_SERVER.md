# Scripts SQL Server - Sistema de Votação CNT

Este documento explica como usar os scripts SQL Server para criar ou migrar o banco de dados do Sistema de Votação CNT.

## Arquivos Disponíveis

### 1. `script-banco-sqlserver.sql`
Script completo para criar o banco de dados do zero, incluindo:
- Todas as tabelas do sistema
- Tabela de bancadas (Trabalhadores, Empregadores, Governo)
- Relacionamentos e foreign keys
- Dados iniciais (configurações, bancadas, usuário admin)

**Use este script quando:** Você está criando o banco de dados pela primeira vez.

### 2. `script-migracao-bancadas-sqlserver.sql`
Script de migração para adicionar a funcionalidade de bancadas em um banco existente:
- Cria tabela bancadas
- Adiciona coluna bancada_id na tabela votos
- Migra dados existentes (bancada string → bancada_id)
- Remove coluna bancada antiga
- Adiciona foreign key constraints

**Use este script quando:** Você já tem o banco de dados instalado e precisa adicionar a funcionalidade de bancadas.

## Como Usar

### Opção 1: Criação do Banco de Dados do Zero

Se você está instalando o sistema pela primeira vez:

1. **Criar o banco de dados** (via SQL Server Management Studio ou Azure Data Studio):
   ```sql
   CREATE DATABASE SistemaVotacaoCNT;
   GO
   ```

2. **Selecionar o banco de dados**:
   ```sql
   USE SistemaVotacaoCNT;
   GO
   ```

3. **Executar o script completo**:
   ```powershell
   # Via sqlcmd (linha de comando)
   sqlcmd -S localhost -U seu_usuario -P sua_senha -d SistemaVotacaoCNT -i script-banco-sqlserver.sql

   # Ou copie e cole o conteúdo do arquivo no SQL Server Management Studio / Azure Data Studio
   ```

4. **Verificar a instalação**:
   ```sql
   -- Verificar tabelas criadas
   SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE';

   -- Verificar bancadas inseridas
   SELECT * FROM bancadas;

   -- Verificar usuário admin
   SELECT nome, cpf, is_admin FROM usuarios;
   ```

### Opção 2: Migração em Banco Existente

Se você já tem o banco de dados instalado e quer adicionar a funcionalidade de bancadas:

1. **Fazer backup do banco de dados** (IMPORTANTE!):
   ```sql
   BACKUP DATABASE SistemaVotacaoCNT
   TO DISK = 'C:\Backups\SistemaVotacaoCNT_Backup.bak'
   WITH FORMAT;
   ```

2. **Selecionar o banco de dados**:
   ```sql
   USE SistemaVotacaoCNT;
   GO
   ```

3. **Executar o script de migração**:
   ```powershell
   # Via sqlcmd
   sqlcmd -S localhost -U seu_usuario -P sua_senha -d SistemaVotacaoCNT -i script-migracao-bancadas-sqlserver.sql

   # Ou copie e cole o conteúdo no SQL Server Management Studio / Azure Data Studio
   ```

4. **Verificar a migração**:
   ```sql
   -- Verificar se a tabela bancadas foi criada
   SELECT * FROM bancadas;

   -- Verificar se a coluna bancada_id foi adicionada
   SELECT COLUMN_NAME, DATA_TYPE
   FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_NAME = 'votos' AND COLUMN_NAME = 'bancada_id';

   -- Verificar se os dados foram migrados
   SELECT COUNT(*) as total_votos, COUNT(bancada_id) as votos_com_bancada
   FROM votos;

   -- Verificar votos por bancada
   SELECT b.nome, COUNT(v.id) as total_votos
   FROM bancadas b
   LEFT JOIN votos v ON b.id = v.bancada_id
   GROUP BY b.nome;
   ```

## Estrutura do Banco de Dados

### Tabelas Principais

#### bancadas (NOVA)
Tabela de domínio para as bancadas do sistema.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | BIGINT | ID único (PK) |
| nome | NVARCHAR(50) | Nome da bancada (UNIQUE) |
| sigla | NVARCHAR(10) | Sigla da bancada |
| created_at | DATETIME2 | Data de criação |
| updated_at | DATETIME2 | Data de atualização |

**Registros padrão:**
- Trabalhadores (TRAB)
- Empregadores (EMPR)
- Governo (GOV)

#### votos (MODIFICADA)
Tabela de votos com novo campo bancada_id.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | BIGINT | ID único (PK) |
| proposta_id | BIGINT | ID da proposta (FK) |
| cpf_votante | NVARCHAR(11) | CPF do votante |
| **bancada_id** | **BIGINT** | **ID da bancada (FK)** ⭐ NOVO |
| nome_votante | NVARCHAR(255) | Nome do votante |
| nome_sindicato | NVARCHAR(255) | Nome do sindicato |
| voto | BIT | Voto (1=Sim, 0=Não) |
| votado_em | DATETIME2 | Data/hora do voto |
| created_at | DATETIME2 | Data de criação |
| updated_at | DATETIME2 | Data de atualização |

**Foreign Keys:**
- `FK_votos_proposta_id`: votos.proposta_id → propostas.id (ON DELETE CASCADE)
- `FK_votos_bancada_id`: votos.bancada_id → bancadas.id (ON DELETE NO ACTION) ⭐ NOVO

**Constraints:**
- `UQ_votos_proposta_cpf`: UNIQUE(proposta_id, cpf_votante)

### Outras Tabelas

- **usuarios**: Usuários do sistema (administradores)
- **propostas**: Propostas em votação
- **configuracoes**: Configurações do sistema
- **tokens_acesso_pessoal**: Tokens de autenticação (Laravel Sanctum)
- **sessoes**: Sessões de usuários
- **tokens_redefinicao_senha**: Tokens para redefinição de senha
- **migrations**: Registro de migrations executadas

## Dados Iniciais

### Usuário Administrador Padrão

Após executar o script completo, será criado um usuário administrador:

- **CPF**: `00000000000`
- **Senha**: `admin123`
- **Permissão**: Administrador

**⚠️ IMPORTANTE:** Altere a senha após o primeiro login!

### Bancadas

Três bancadas são criadas automaticamente:

1. **Trabalhadores** (Sigla: TRAB)
2. **Empregadores** (Sigla: EMPR)
3. **Governo** (Sigla: GOV)

### Configurações do Sistema

Quatro configurações são inseridas por padrão:

- `banner_votacao`: `/images/banner-cnt.png`
- `temporizador_ativo`: `0` (desativado)
- `temporizador_inicio`: `NULL`
- `temporizador_duracao_minutos`: `30`

## Troubleshooting

### Erro: "Object already exists"

Se você receber erros de objetos já existentes ao executar o script completo, você tem duas opções:

1. **Criar em um banco novo** (recomendado):
   ```sql
   CREATE DATABASE SistemaVotacaoCNT_Novo;
   GO
   USE SistemaVotacaoCNT_Novo;
   GO
   -- Executar script-banco-sqlserver.sql
   ```

2. **Usar o script de migração** ao invés do script completo:
   ```sql
   -- Executar script-migracao-bancadas-sqlserver.sql
   ```

### Erro: "Foreign key constraint failed"

Se houver erro ao adicionar a foreign key, verifique se:

1. A tabela bancadas existe e tem os registros:
   ```sql
   SELECT * FROM bancadas;
   ```

2. Todos os valores de bancada foram migrados corretamente:
   ```sql
   SELECT bancada_id, COUNT(*) FROM votos GROUP BY bancada_id;
   ```

3. Não há valores NULL inesperados:
   ```sql
   SELECT * FROM votos WHERE bancada_id IS NULL;
   ```

### Erro: "Dados não migrados"

Se o script de migração alertar sobre dados não migrados:

```sql
-- Verificar registros não migrados
SELECT * FROM votos
WHERE bancada IS NOT NULL
AND bancada_id IS NULL;

-- Migrar manualmente se necessário
UPDATE votos
SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Trabalhadores')
WHERE bancada = 'Trabalhadores' AND bancada_id IS NULL;
```

## Próximos Passos

Após executar os scripts:

1. **Configurar o Laravel**:
   - Atualizar arquivo `.env` com credenciais do banco
   - Executar `php artisan migrate:status` para verificar migrations

2. **Testar a conexão**:
   ```bash
   php artisan tinker
   >>> \DB::connection()->getPdo();
   >>> App\Models\Bancada::all();
   ```

3. **Atualizar o código**:
   - Backend já está configurado para usar `bancada_id`
   - Frontend já carrega bancadas da API
   - Commit: `631c8f0`

4. **Testar a aplicação**:
   - Acessar painel administrativo
   - Criar uma proposta
   - Testar votação com seleção de bancada

## Referências

- Migrations Laravel em: `codigo-fonte/servico/database/migrations/`
- Models Laravel em: `codigo-fonte/servico/app/Models/`
- Documentação: `CONFIGURACAO_AMBIENTES.md`

## Suporte

Em caso de dúvidas ou problemas:

1. Verifique os logs do Laravel: `storage/logs/laravel.log`
2. Verifique os logs do SQL Server
3. Consulte a documentação do Laravel 11
4. Revise o código do commit `631c8f0`
