# Script de Bancadas - SQL Server

Script SQL Server simplificado para adicionar a funcionalidade de bancadas ao Sistema de Votação CNT.

## 📋 O que este script faz?

1. **Cria a tabela `bancadas`** com estrutura completa
2. **Insere 3 bancadas padrão**: Trabalhadores, Empregadores e Governo
3. **Adiciona coluna `bancada_id`** na tabela `votos`
4. **Cria foreign key** entre votos e bancadas
5. **Cria índice** para otimizar consultas
6. **Verifica** a instalação ao final

## 🚀 Como usar

### Pré-requisitos

- SQL Server 2016 ou superior
- Banco de dados `SistemaVotacaoCNT` já criado
- Tabela `votos` já existente
- Permissões para criar tabelas e alterar estrutura

### Execução

**Opção 1: Via SQL Server Management Studio (SSMS)**

1. Abra o SQL Server Management Studio
2. Conecte ao servidor
3. Abra o arquivo `script-bancadas-sqlserver.sql`
4. Execute o script (F5 ou botão Execute)

**Opção 2: Via Azure Data Studio**

1. Abra o Azure Data Studio
2. Conecte ao servidor
3. Abra o arquivo `script-bancadas-sqlserver.sql`
4. Execute o script (F5 ou Run)

**Opção 3: Via linha de comando (sqlcmd)**

```powershell
sqlcmd -S localhost -U seu_usuario -P sua_senha -d SistemaVotacaoCNT -i script-bancadas-sqlserver.sql
```

**Opção 4: Via linha de comando (com autenticação Windows)**

```powershell
sqlcmd -S localhost -E -d SistemaVotacaoCNT -i script-bancadas-sqlserver.sql
```

## 📊 Estrutura Criada

### Tabela: bancadas

```sql
CREATE TABLE bancadas (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    nome NVARCHAR(50) NOT NULL UNIQUE,
    sigla NVARCHAR(10) NULL,
    created_at DATETIME2(7) NULL,
    updated_at DATETIME2(7) NULL
);
```

### Dados Inseridos

| ID | Nome | Sigla |
|----|------|-------|
| 1 | Trabalhadores | TRAB |
| 2 | Empregadores | EMPR |
| 3 | Governo | GOV |

### Alteração na Tabela votos

```sql
-- Nova coluna adicionada:
bancada_id BIGINT NULL

-- Foreign Key:
CONSTRAINT FK_votos_bancada_id
    FOREIGN KEY (bancada_id) REFERENCES bancadas(id)

-- Índice:
IX_votos_bancada_id
```

## ✅ Verificação

Após executar o script, verifique se tudo foi criado corretamente:

### 1. Verificar bancadas criadas

```sql
SELECT * FROM bancadas;
```

**Resultado esperado:**
```
id  nome           sigla  created_at            updated_at
1   Trabalhadores  TRAB   2025-11-05 14:30:04   2025-11-05 14:30:04
2   Empregadores   EMPR   2025-11-05 14:30:04   2025-11-05 14:30:04
3   Governo        GOV    2025-11-05 14:30:04   2025-11-05 14:30:04
```

### 2. Verificar estrutura da tabela votos

```sql
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'votos' AND COLUMN_NAME = 'bancada_id';
```

**Resultado esperado:**
```
COLUMN_NAME  DATA_TYPE  IS_NULLABLE
bancada_id   bigint     YES
```

### 3. Verificar foreign key

```sql
SELECT
    fk.name AS ForeignKey,
    tp.name AS ParentTable,
    cp.name AS ParentColumn,
    tr.name AS ReferencedTable,
    cr.name AS ReferencedColumn
FROM sys.foreign_keys AS fk
INNER JOIN sys.tables AS tp ON fk.parent_object_id = tp.object_id
INNER JOIN sys.tables AS tr ON fk.referenced_object_id = tr.object_id
INNER JOIN sys.foreign_key_columns AS fkc ON fk.object_id = fkc.constraint_object_id
INNER JOIN sys.columns AS cp ON fkc.parent_column_id = cp.column_id AND fkc.parent_object_id = cp.object_id
INNER JOIN sys.columns AS cr ON fkc.referenced_column_id = cr.column_id AND fkc.referenced_object_id = cr.object_id
WHERE fk.name = 'FK_votos_bancada_id';
```

**Resultado esperado:**
```
ForeignKey            ParentTable  ParentColumn  ReferencedTable  ReferencedColumn
FK_votos_bancada_id   votos        bancada_id    bancadas         id
```

### 4. Verificar índice

```sql
SELECT
    i.name AS IndexName,
    t.name AS TableName,
    c.name AS ColumnName
FROM sys.indexes AS i
INNER JOIN sys.index_columns AS ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns AS c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
INNER JOIN sys.tables AS t ON i.object_id = t.object_id
WHERE i.name = 'IX_votos_bancada_id';
```

**Resultado esperado:**
```
IndexName             TableName  ColumnName
IX_votos_bancada_id   votos      bancada_id
```

## 📝 Queries Úteis

### Listar todas as bancadas

```sql
SELECT id, nome, sigla FROM bancadas ORDER BY nome;
```

### Ver distribuição de votos por bancada

```sql
SELECT
    b.nome AS Bancada,
    b.sigla AS Sigla,
    COUNT(v.id) AS TotalVotos,
    SUM(CASE WHEN v.voto = 1 THEN 1 ELSE 0 END) AS VotosSim,
    SUM(CASE WHEN v.voto = 0 THEN 1 ELSE 0 END) AS VotosNao
FROM bancadas b
LEFT JOIN votos v ON b.id = v.bancada_id
GROUP BY b.nome, b.sigla
ORDER BY b.nome;
```

### Atualizar bancada de um voto

```sql
UPDATE votos
SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Trabalhadores')
WHERE id = 123; -- ID do voto
```

### Ver votos com informações da bancada

```sql
SELECT
    v.id,
    v.cpf_votante,
    b.nome AS bancada,
    v.voto,
    v.votado_em
FROM votos v
LEFT JOIN bancadas b ON v.bancada_id = b.id
ORDER BY v.votado_em DESC;
```

### Contar votos sem bancada

```sql
SELECT COUNT(*) AS VotosSemBancada
FROM votos
WHERE bancada_id IS NULL;
```

## ⚠️ Segurança

### O script é seguro?

✅ **SIM**. O script foi desenvolvido com práticas de segurança:

- **Idempotente**: Pode ser executado múltiplas vezes sem causar erros
- **Verifica existência**: Não cria objetos duplicados
- **Não remove dados**: Apenas adiciona estruturas
- **Não modifica dados**: Não altera votos existentes
- **Usa transações implícitas**: SQL Server gerencia automaticamente

### Recomendações

1. **Faça backup antes de executar** (sempre recomendado):
   ```sql
   BACKUP DATABASE SistemaVotacaoCNT
   TO DISK = 'C:\Backups\SistemaVotacaoCNT_Backup.bak'
   WITH FORMAT, NAME = 'Backup antes de adicionar bancadas';
   ```

2. **Execute primeiro em ambiente de teste** se disponível

3. **Verifique permissões** necessárias:
   - CREATE TABLE
   - ALTER TABLE
   - CREATE INDEX
   - INSERT

## 🔄 Rollback (Reverter)

Se precisar remover as bancadas:

```sql
-- 1. Remover foreign key
ALTER TABLE votos DROP CONSTRAINT FK_votos_bancada_id;

-- 2. Remover índice
DROP INDEX IX_votos_bancada_id ON votos;

-- 3. Remover coluna bancada_id
ALTER TABLE votos DROP COLUMN bancada_id;

-- 4. Remover tabela bancadas
DROP TABLE bancadas;
```

**⚠️ ATENÇÃO**: Isso vai remover todos os dados de bancada. Use com cuidado!

## 🐛 Troubleshooting

### Erro: "Database does not exist"

**Problema**: Banco de dados não encontrado.

**Solução**: Certifique-se de que está usando o banco correto:
```sql
USE SistemaVotacaoCNT;
GO
```

Ou altere o nome no script se seu banco tiver nome diferente.

### Erro: "Table 'votos' does not exist"

**Problema**: Tabela votos não existe.

**Solução**: Execute primeiro o script completo de criação do banco (`script-banco-sqlserver.sql`).

### Erro: "Foreign key constraint conflict"

**Problema**: Existem valores em `bancada_id` que não correspondem a IDs na tabela bancadas.

**Solução**: Verifique e corrija os dados:
```sql
-- Ver valores inválidos
SELECT DISTINCT bancada_id
FROM votos
WHERE bancada_id NOT IN (SELECT id FROM bancadas)
AND bancada_id IS NOT NULL;

-- Limpar valores inválidos (ou corrigi-los)
UPDATE votos SET bancada_id = NULL
WHERE bancada_id NOT IN (SELECT id FROM bancadas);
```

### Aviso: "Object already exists"

**Isso é normal!** O script verifica se os objetos já existem antes de criar. Se você vir mensagens como:

```
⚠ Tabela bancadas já existe
⚠ Coluna bancada_id já existe na tabela votos
```

Significa que o script já foi executado anteriormente. Isso não é um erro.

## 📚 Próximos Passos

Após executar este script:

1. **Verificar a instalação** usando as queries de verificação acima

2. **Atualizar a aplicação Laravel**:
   - O código Laravel já está preparado para usar bancadas
   - Verifique o commit `631c8f0`

3. **Testar a API**:
   ```bash
   # Listar bancadas
   curl http://localhost:8000/api/bancadas

   # Criar voto com bancada
   curl -X POST http://localhost:8000/api/votar \
     -H "Content-Type: application/json" \
     -d '{
       "cpf_votante": "12345678901",
       "bancada_id": 1,
       "voto": 1,
       "proposta_id": 1
     }'
   ```

4. **Testar o frontend**:
   - Acesse a página de votação
   - Verifique se o campo "Bancada" aparece
   - Teste uma votação completa

## 📖 Documentação Relacionada

- **Script completo**: `script-banco-sqlserver.sql` - Para criar banco do zero
- **Script de migração**: `script-migracao-bancadas-sqlserver.sql` - Migração com dados existentes
- **Documentação geral**: `SCRIPTS_SQL_SERVER.md` - Guia completo
- **Migrations Laravel**: `database/migrations/` - Estrutura no Laravel

## 💡 Diferenças entre os Scripts

| Script | Quando usar | O que faz |
|--------|-------------|-----------|
| `script-bancadas-sqlserver.sql` | Adicionar bancadas em banco existente (simples) | Cria bancadas e adiciona coluna bancada_id |
| `script-migracao-bancadas-sqlserver.sql` | Migrar dados de bancada string para ID | Inclui migração de dados existentes |
| `script-banco-sqlserver.sql` | Criar banco do zero | Cria todas as tabelas do sistema |

## 📞 Suporte

Em caso de dúvidas:

1. Verifique a documentação completa em `SCRIPTS_SQL_SERVER.md`
2. Revise os logs de erro do SQL Server
3. Consulte o código Laravel no commit `631c8f0`
4. Verifique as migrations em `database/migrations/`

---

**Versão**: 1.0
**Última atualização**: 2025-11-05
**Compatibilidade**: SQL Server 2016+
