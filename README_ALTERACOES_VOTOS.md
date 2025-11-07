# Script de Alterações da Tabela VOTOS - Bancadas

Script SQL Server focado exclusivamente nas modificações da tabela `votos` para suportar bancadas através de foreign key.

## 📋 O que este script faz?

Este script modifica **apenas a tabela VOTOS** para adicionar suporte a bancadas:

1. ✅ **Adiciona coluna** `bancada_id` (BIGINT NULL)
2. ✅ **Migra dados** de `bancada` (string) para `bancada_id` (foreign key)
3. ✅ **Cria foreign key** entre `votos.bancada_id` → `bancadas.id`
4. ✅ **Cria índice** `IX_votos_bancada_id` para otimização
5. ✅ **Remove coluna antiga** `bancada` (se dados foram migrados)
6. ✅ **Exibe relatório completo** das alterações

## 🎯 Quando usar este script?

Use este script quando:
- ✅ A tabela `votos` já existe
- ✅ A tabela `bancadas` já foi criada e populada
- ✅ Você quer modificar apenas a tabela votos
- ✅ Você tem votos com a coluna `bancada` (string) que precisa migrar
- ✅ Você quer ver um relatório detalhado das mudanças

## 📋 Pré-requisitos

### Obrigatórios:

1. **Banco de dados existe**: `SistemaVotacaoCNT`
2. **Tabela votos existe** com estrutura básica
3. **Tabela bancadas existe** e está populada:
   ```sql
   SELECT * FROM bancadas;
   -- Deve retornar: Trabalhadores, Empregadores, Governo
   ```

### Verificação:

```sql
-- Verificar se votos existe
SELECT COUNT(*) FROM votos;

-- Verificar se bancadas existe
SELECT * FROM bancadas;
```

Se alguma tabela não existir, execute primeiro:
- `script-bancadas-sqlserver.sql` - Para criar tabela bancadas
- `script-banco-sqlserver.sql` - Para criar banco completo

## 🚀 Como usar

### Método 1: SQL Server Management Studio (SSMS)

1. Abra o SSMS e conecte ao servidor
2. Selecione o banco `SistemaVotacaoCNT`
3. Abra o arquivo `script-alteracoes-votos-bancadas-sqlserver.sql`
4. Execute o script (F5)
5. Revise as mensagens e o relatório final

### Método 2: Azure Data Studio

1. Abra o Azure Data Studio
2. Conecte ao servidor
3. Abra o arquivo `script-alteracoes-votos-bancadas-sqlserver.sql`
4. Execute (F5 ou botão Run)
5. Veja os resultados nas abas Messages e Results

### Método 3: Linha de comando (sqlcmd)

```powershell
# Com autenticação SQL
sqlcmd -S localhost -U seu_usuario -P sua_senha -d SistemaVotacaoCNT -i script-alteracoes-votos-bancadas-sqlserver.sql

# Com autenticação Windows
sqlcmd -S localhost -E -d SistemaVotacaoCNT -i script-alteracoes-votos-bancadas-sqlserver.sql
```

### Método 4: PowerShell

```powershell
$Server = "localhost"
$Database = "SistemaVotacaoCNT"
$ScriptPath = ".\script-alteracoes-votos-bancadas-sqlserver.sql"

Invoke-Sqlcmd -ServerInstance $Server -Database $Database -InputFile $ScriptPath
```

## 📊 Estrutura das Alterações

### ANTES (Estrutura Antiga)

```sql
CREATE TABLE votos (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    proposta_id BIGINT,
    cpf_votante NVARCHAR(11),
    bancada NVARCHAR(20),  -- ❌ String simples
    voto BIT,
    votado_em DATETIME2,
    ...
);
```

### DEPOIS (Estrutura Nova)

```sql
CREATE TABLE votos (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    proposta_id BIGINT,
    cpf_votante NVARCHAR(11),
    bancada_id BIGINT,  -- ✅ Foreign Key
    voto BIT,
    votado_em DATETIME2,
    ...
    CONSTRAINT FK_votos_bancada_id FOREIGN KEY (bancada_id)
        REFERENCES bancadas(id)
);

-- Índice para otimização
CREATE INDEX IX_votos_bancada_id ON votos(bancada_id);
```

## 📈 Exemplo de Saída

Ao executar o script, você verá um relatório detalhado:

```
=============================================
ALTERAÇÕES NA TABELA VOTOS - BANCADAS
=============================================

VERIFICAÇÃO INICIAL:
--------------------------------------------
✓ Tabela votos existe
✓ Tabela bancadas existe
  Total de bancadas: 3
⚠ Coluna "bancada" (string) existe - será migrada
  Votos com bancada: 150

PASSO 1: Adicionando coluna bancada_id
--------------------------------------------
✓ Coluna bancada_id adicionada com sucesso
  Tipo: BIGINT NULL
  Posição: Após cpf_votante

PASSO 2: Migrando dados existentes
--------------------------------------------
Migrando dados de bancada (string) para bancada_id (FK)...
✓ Migrados 50 votos de Trabalhadores
✓ Migrados 60 votos de Empregadores
✓ Migrados 40 votos de Governo
✓ Todos os dados foram migrados com sucesso

PASSO 3: Criando Foreign Key
--------------------------------------------
✓ Foreign Key FK_votos_bancada_id criada com sucesso
  votos.bancada_id -> bancadas.id
  ON DELETE: NO ACTION

PASSO 4: Criando Índice
--------------------------------------------
✓ Índice IX_votos_bancada_id criado com sucesso
  Tipo: NONCLUSTERED
  Coluna: bancada_id ASC

PASSO 5: Removendo coluna bancada antiga
--------------------------------------------
✓ Coluna "bancada" removida com sucesso
  Todos os dados foram migrados para bancada_id

=============================================
VERIFICAÇÃO FINAL
=============================================

1. ESTRUTURA DA COLUNA bancada_id:
--------------------------------------------
Coluna      Tipo    Tamanho  Permite_NULL
bancada_id  bigint  NULL     YES

2. FOREIGN KEY:
--------------------------------------------
ForeignKey            TabelaOrigem  ColunaOrigem  TabelaReferencia  ColunaReferencia
FK_votos_bancada_id   votos         bancada_id    bancadas          id

3. ÍNDICE:
--------------------------------------------
Nome_Indice           Tipo           Coluna      Descendente
IX_votos_bancada_id   NONCLUSTERED   bancada_id  0

4. DISTRIBUIÇÃO DE VOTOS POR BANCADA:
--------------------------------------------
id  Bancada        Sigla  Total_Votos  Votos_Sim  Votos_Nao
1   Trabalhadores  TRAB   50           35         15
2   Empregadores   EMPR   60           40         20
3   Governo        GOV    40           25         15

5. ESTATÍSTICAS GERAIS:
--------------------------------------------
Total de votos: 150
Votos com bancada: 150
Votos sem bancada: 0

=============================================
ALTERAÇÕES CONCLUÍDAS COM SUCESSO!
=============================================

Resumo das alterações:
  ✓ Coluna bancada_id adicionada
  ✓ Foreign Key configurada
  ✓ Índice criado
  ✓ Coluna bancada antiga removida

Próximos passos:
  1. Verificar a distribuição de votos acima
  2. Atualizar a aplicação Laravel (já configurada)
  3. Testar a votação no frontend
```

## 🔍 Verificações Incluídas

O script inclui **verificações automáticas** antes de cada operação:

### Verificações Pré-execução:
- ✅ Tabela `votos` existe?
- ✅ Tabela `bancadas` existe e tem registros?
- ✅ Coluna `bancada` antiga existe?
- ✅ Coluna `bancada_id` já existe?

### Verificações Durante Migração:
- ✅ Todos os valores de `bancada` são válidos?
- ✅ Existem dados não migrados?
- ✅ Existem valores de `bancada_id` inválidos?

### Verificações Pós-execução:
- ✅ Estrutura da coluna criada corretamente?
- ✅ Foreign key configurada?
- ✅ Índice criado?
- ✅ Distribuição de votos por bancada
- ✅ Estatísticas gerais

## 🛡️ Recursos de Segurança

### 1. Idempotência
O script pode ser executado **múltiplas vezes** sem causar erros:
```sql
-- Se executar 2x, na segunda vez verá:
⚠ Coluna bancada_id já existe, pulando...
⚠ Foreign Key FK_votos_bancada_id já existe
⚠ Índice IX_votos_bancada_id já existe
```

### 2. Validação de Dados
Antes de criar a foreign key, verifica se existem valores inválidos:
```sql
-- Se houver bancada_id inválido:
❌ ERRO: Existem 5 votos com bancada_id inválido
Corrija os dados antes de criar a Foreign Key:

bancada_id  quantidade
99          5
```

### 3. Proteção na Remoção
A coluna `bancada` antiga **só é removida** se todos os dados foram migrados:
```sql
-- Se houver dados não migrados:
⚠ ATENÇÃO: 10 votos não foram migrados
⚠ A coluna "bancada" NÃO será removida por segurança

Execute a migração manual dos dados restantes e depois remova:
ALTER TABLE votos DROP COLUMN bancada;
```

### 4. Tratamento de Erros
O script **para a execução** se encontrar problemas críticos:
```sql
-- Se tabela votos não existe:
❌ ERRO: Tabela votos não existe!
Execute primeiro o script de criação do banco de dados.
```

## 📊 Mapeamento de Dados

### Migração Automática:

| bancada (STRING) | → | bancada_id (FK) | bancadas.nome |
|------------------|---|-----------------|---------------|
| 'Trabalhadores'  | → | 1               | Trabalhadores |
| 'Empregadores'   | → | 2               | Empregadores  |
| 'Governo'        | → | 3               | Governo       |
| NULL             | → | NULL            | -             |

### Queries de Migração:

```sql
-- Trabalhadores
UPDATE votos
SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Trabalhadores')
WHERE bancada = 'Trabalhadores';

-- Empregadores
UPDATE votos
SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Empregadores')
WHERE bancada = 'Empregadores';

-- Governo
UPDATE votos
SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Governo')
WHERE bancada = 'Governo';
```

## 🔧 Queries Úteis

### Verificar estrutura da coluna bancada_id

```sql
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'votos'
AND COLUMN_NAME = 'bancada_id';
```

### Ver votos com bancada

```sql
SELECT
    v.id,
    v.cpf_votante,
    v.bancada_id,
    b.nome AS bancada_nome,
    b.sigla AS bancada_sigla,
    v.voto,
    v.votado_em
FROM votos v
LEFT JOIN bancadas b ON v.bancada_id = b.id
ORDER BY v.votado_em DESC;
```

### Estatísticas por bancada

```sql
SELECT
    b.nome AS Bancada,
    COUNT(v.id) AS TotalVotos,
    SUM(CASE WHEN v.voto = 1 THEN 1 ELSE 0 END) AS VotosSim,
    SUM(CASE WHEN v.voto = 0 THEN 1 ELSE 0 END) AS VotosNao,
    CAST(SUM(CASE WHEN v.voto = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(v.id) AS DECIMAL(5,2)) AS PercentualSim
FROM bancadas b
LEFT JOIN votos v ON b.id = v.bancada_id
GROUP BY b.nome
ORDER BY TotalVotos DESC;
```

### Votos sem bancada

```sql
SELECT
    v.id,
    v.cpf_votante,
    v.voto,
    v.votado_em
FROM votos v
WHERE v.bancada_id IS NULL
ORDER BY v.votado_em DESC;
```

### Verificar foreign key

```sql
SELECT
    fk.name AS ForeignKey,
    OBJECT_NAME(fk.parent_object_id) AS TabelaPai,
    COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS ColunaPai,
    OBJECT_NAME(fk.referenced_object_id) AS TabelaReferenciada,
    COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS ColunaReferenciada,
    fk.delete_referential_action_desc AS AcaoDelete,
    fk.update_referential_action_desc AS AcaoUpdate
FROM sys.foreign_keys AS fk
INNER JOIN sys.foreign_key_columns AS fkc
    ON fk.object_id = fkc.constraint_object_id
WHERE fk.name = 'FK_votos_bancada_id';
```

## 🐛 Troubleshooting

### Erro: "Tabela votos não existe"

**Problema**: Tabela votos não foi criada.

**Solução**: Execute primeiro o script de criação do banco:
```powershell
sqlcmd -S localhost -E -d SistemaVotacaoCNT -i script-banco-sqlserver.sql
```

### Erro: "Tabela bancadas não existe"

**Problema**: Tabela bancadas não foi criada.

**Solução**: Execute o script de bancadas primeiro:
```powershell
sqlcmd -S localhost -E -d SistemaVotacaoCNT -i script-bancadas-sqlserver.sql
```

### Erro: "Valores inválidos em bancada_id"

**Problema**: Existem valores em `bancada_id` que não correspondem a IDs válidos na tabela bancadas.

**Solução**: Identifique e corrija os valores:
```sql
-- Ver valores inválidos
SELECT DISTINCT bancada_id, COUNT(*) as total
FROM votos
WHERE bancada_id NOT IN (SELECT id FROM bancadas)
AND bancada_id IS NOT NULL
GROUP BY bancada_id;

-- Corrigir ou limpar
UPDATE votos SET bancada_id = NULL
WHERE bancada_id NOT IN (SELECT id FROM bancadas);
```

### Aviso: "Registros não foram migrados"

**Problema**: Existem valores na coluna `bancada` que não correspondem aos esperados.

**Solução**: Verifique os valores e migre manualmente:
```sql
-- Ver valores não migrados
SELECT DISTINCT bancada, COUNT(*) as total
FROM votos
WHERE bancada IS NOT NULL
AND bancada_id IS NULL
GROUP BY bancada;

-- Exemplo de migração manual
UPDATE votos
SET bancada_id = 1  -- ID correto
WHERE bancada = 'Valor não padrão'
AND bancada_id IS NULL;
```

### Aviso: "Coluna bancada não removida"

**Problema**: Há dados não migrados, então a coluna antiga foi mantida por segurança.

**Solução**:
1. Migre os dados restantes manualmente
2. Verifique se tudo está OK:
   ```sql
   SELECT COUNT(*) FROM votos WHERE bancada IS NOT NULL AND bancada_id IS NULL;
   ```
3. Se retornar 0, remova manualmente:
   ```sql
   ALTER TABLE votos DROP COLUMN bancada;
   ```

## 🔄 Rollback (Reverter Alterações)

Se precisar reverter as alterações:

```sql
-- 1. Adicionar coluna bancada de volta
ALTER TABLE votos ADD bancada NVARCHAR(20) NULL;

-- 2. Restaurar dados (se você ainda tem a coluna bancada)
UPDATE votos
SET bancada = CASE bancada_id
    WHEN 1 THEN 'Trabalhadores'
    WHEN 2 THEN 'Empregadores'
    WHEN 3 THEN 'Governo'
    ELSE NULL
END;

-- 3. Remover foreign key
ALTER TABLE votos DROP CONSTRAINT FK_votos_bancada_id;

-- 4. Remover índice
DROP INDEX IX_votos_bancada_id ON votos;

-- 5. Remover coluna bancada_id
ALTER TABLE votos DROP COLUMN bancada_id;
```

**⚠️ ATENÇÃO**: Só faça rollback se realmente necessário. Faça backup antes!

## 📚 Comparação de Scripts

| Script | Foco | Quando Usar |
|--------|------|-------------|
| **script-alteracoes-votos-bancadas-sqlserver.sql** ⭐ | Alterações na tabela VOTOS | Quando quer modificar apenas votos com relatório detalhado |
| `script-bancadas-sqlserver.sql` | Criar bancadas + alterar votos | Quando quer tudo em um script simples |
| `script-migracao-bancadas-sqlserver.sql` | Migração completa | Quando tem dados existentes complexos |
| `script-banco-sqlserver.sql` | Criar banco completo | Instalação inicial do zero |

## 📋 Checklist Pós-execução

Após executar o script, verifique:

- [ ] Coluna `bancada_id` existe e é BIGINT NULL
- [ ] Foreign key `FK_votos_bancada_id` está ativa
- [ ] Índice `IX_votos_bancada_id` foi criado
- [ ] Distribuição de votos por bancada está correta
- [ ] Não há votos com `bancada_id` inválido
- [ ] Coluna `bancada` antiga foi removida (ou mantida por segurança)
- [ ] Estatísticas batem com o esperado

## 🚀 Próximos Passos

1. **Verificar dados**:
   ```sql
   SELECT b.nome, COUNT(v.id) FROM bancadas b
   LEFT JOIN votos v ON b.id = v.bancada_id
   GROUP BY b.nome;
   ```

2. **Testar API Laravel**:
   ```bash
   php artisan tinker
   >>> App\Models\Voto::with('bancada')->first();
   ```

3. **Testar frontend**:
   - Acessar página de votação
   - Verificar se bancadas aparecem no select
   - Criar um voto de teste

4. **Monitorar performance**:
   ```sql
   -- Verificar uso do índice
   SELECT * FROM sys.dm_db_index_usage_stats
   WHERE object_id = OBJECT_ID('votos')
   AND index_id = (SELECT index_id FROM sys.indexes WHERE name = 'IX_votos_bancada_id');
   ```

## 📖 Documentação Relacionada

- **README_BANCADAS.md** - Guia do script simples de bancadas
- **SCRIPTS_SQL_SERVER.md** - Documentação completa de todos os scripts
- **Migrations Laravel** - `database/migrations/2025_11_05_143004_modificar_bancada_para_bancada_id_tabela_votos.php`

---

**Versão**: 1.0
**Data**: 2025-11-05
**Compatibilidade**: SQL Server 2016+
**Status**: Testado e validado
