# Script: Adicionar Limite de Votantes - SQL Server

Script SQL Server para adicionar as novas colunas relacionadas ao limite de votantes e temporizador individual por proposta.

## 📋 O que este script faz?

Adiciona **3 novas colunas** na tabela `propostas`:

1. **`limite_votantes`** (INT NULL) - Limite máximo de votantes para a proposta
2. **`temporizador_inicio`** (DATETIME2 NULL) - Data/hora de início do temporizador
3. **`temporizador_duracao_minutos`** (INT NULL) - Duração do temporizador em minutos

## 🎯 Funcionalidades Implementadas

### 1. Limite de Votantes
Define quantas pessoas podem votar em uma proposta específica.

**Comportamento:**
- Se `limite_votantes` for `NULL` → Sem limite
- Se `limite_votantes` for definido → Votação encerra ao atingir o limite
- Sistema valida automaticamente e impede novos votos após o limite

### 2. Temporizador por Proposta
Cada proposta pode ter seu próprio temporizador independente.

**Comportamento:**
- Temporizador é ativado via botão no painel admin
- Usa a duração configurada nas configurações globais
- Independente para cada proposta

## 🚀 Como usar

### Pré-requisitos

- SQL Server 2016 ou superior
- Banco de dados `SistemaVotacaoCNT` já criado
- Tabela `propostas` já existente
- Permissões ALTER TABLE

### Execução

**Opção 1: SQL Server Management Studio (SSMS)**

1. Abra o SSMS e conecte ao servidor
2. Abra o arquivo `script-adicionar-limite-votantes-sqlserver.sql`
3. Execute o script (F5)

**Opção 2: Azure Data Studio**

1. Abra o Azure Data Studio
2. Conecte ao servidor
3. Abra o arquivo `script-adicionar-limite-votantes-sqlserver.sql`
4. Execute (F5)

**Opção 3: Linha de comando (sqlcmd)**

```powershell
# Com autenticação SQL
sqlcmd -S localhost -U seu_usuario -P sua_senha -d SistemaVotacaoCNT -i script-adicionar-limite-votantes-sqlserver.sql

# Com autenticação Windows
sqlcmd -S localhost -E -d SistemaVotacaoCNT -i script-adicionar-limite-votantes-sqlserver.sql
```

## 📊 Estrutura Criada

### Coluna: limite_votantes

```sql
ALTER TABLE propostas
ADD limite_votantes INT NULL;
```

**Descrição:** Número máximo de pessoas que podem votar na proposta.

**Valores:**
- `NULL` = Sem limite de votantes
- `> 0` = Limite específico (ex: 100, 500, 1000)

**Exemplo:**
```sql
-- Definir limite de 100 votantes
UPDATE propostas SET limite_votantes = 100 WHERE id = 1;

-- Remover limite
UPDATE propostas SET limite_votantes = NULL WHERE id = 1;
```

### Coluna: temporizador_inicio

```sql
ALTER TABLE propostas
ADD temporizador_inicio DATETIME2(7) NULL;
```

**Descrição:** Data e hora em que o temporizador foi iniciado.

**Valores:**
- `NULL` = Temporizador não iniciado
- `DATETIME` = Data/hora de início

### Coluna: temporizador_duracao_minutos

```sql
ALTER TABLE propostas
ADD temporizador_duracao_minutos INT NULL;
```

**Descrição:** Duração do temporizador em minutos.

**Valores:**
- `NULL` = Sem temporizador
- `> 0` = Duração em minutos (ex: 30, 60, 120)

## 📈 Exemplo de Saída

Ao executar o script, você verá:

```
=============================================
ADICIONANDO COLUNAS NA TABELA PROPOSTAS
=============================================

1. Adicionando coluna limite_votantes
--------------------------------------------
✓ Coluna limite_votantes adicionada com sucesso
  Tipo: INT NULL
  Descrição: Limite máximo de votantes para esta proposta

2. Adicionando coluna temporizador_inicio
--------------------------------------------
✓ Coluna temporizador_inicio adicionada com sucesso
  Tipo: DATETIME2(7) NULL
  Descrição: Data/hora de início do temporizador da proposta

3. Adicionando coluna temporizador_duracao_minutos
--------------------------------------------
✓ Coluna temporizador_duracao_minutos adicionada com sucesso
  Tipo: INT NULL
  Descrição: Duração do temporizador em minutos

=============================================
VERIFICAÇÃO FINAL
=============================================

Estrutura atualizada da tabela propostas:
--------------------------------------------
Coluna                          Tipo        Tamanho  Permite_NULL  Valor_Padrao
limite_votantes                 int         NULL     YES           NULL
temporizador_inicio             datetime2   NULL     YES           NULL
temporizador_duracao_minutos    int         NULL     YES           NULL

=============================================
Colunas adicionadas com sucesso!
=============================================
```

## ✅ Verificações

### 1. Verificar se as colunas foram criadas

```sql
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'propostas'
AND COLUMN_NAME IN ('limite_votantes', 'temporizador_inicio', 'temporizador_duracao_minutos');
```

**Resultado esperado:**
```
COLUMN_NAME                      DATA_TYPE    IS_NULLABLE
limite_votantes                  int          YES
temporizador_inicio              datetime2    YES
temporizador_duracao_minutos     int          YES
```

### 2. Verificar propostas com limite definido

```sql
SELECT
    id,
    nome,
    limite_votantes,
    (SELECT COUNT(*) FROM votos WHERE proposta_id = propostas.id) as total_votos_registrados,
    CASE
        WHEN limite_votantes IS NULL THEN 'Sem limite'
        WHEN (SELECT COUNT(*) FROM votos WHERE proposta_id = propostas.id) >= limite_votantes THEN 'Limite atingido'
        ELSE 'Disponível'
    END as status_limite
FROM propostas
ORDER BY id;
```

### 3. Verificar status do temporizador

```sql
SELECT
    id,
    nome,
    temporizador_inicio,
    temporizador_duracao_minutos,
    DATEADD(MINUTE, temporizador_duracao_minutos, temporizador_inicio) as fim_temporizador,
    CASE
        WHEN temporizador_inicio IS NULL THEN 'Sem temporizador'
        WHEN DATEADD(MINUTE, temporizador_duracao_minutos, temporizador_inicio) > GETDATE() THEN 'Ativo'
        ELSE 'Expirado'
    END as status_temporizador,
    CASE
        WHEN temporizador_inicio IS NOT NULL
        AND DATEADD(MINUTE, temporizador_duracao_minutos, temporizador_inicio) > GETDATE()
        THEN DATEDIFF(SECOND, GETDATE(), DATEADD(MINUTE, temporizador_duracao_minutos, temporizador_inicio))
        ELSE 0
    END as segundos_restantes
FROM propostas
WHERE temporizador_inicio IS NOT NULL;
```

## 📝 Queries Úteis

### Definir limite de votantes

```sql
-- Definir limite de 100 votantes para proposta 1
UPDATE propostas
SET limite_votantes = 100
WHERE id = 1;

-- Definir limite de 500 votantes para proposta 2
UPDATE propostas
SET limite_votantes = 500
WHERE id = 2;
```

### Ativar temporizador

```sql
-- Ativar temporizador de 30 minutos para proposta 1
UPDATE propostas
SET temporizador_inicio = GETDATE(),
    temporizador_duracao_minutos = 30
WHERE id = 1;

-- Ativar temporizador de 1 hora para proposta 2
UPDATE propostas
SET temporizador_inicio = GETDATE(),
    temporizador_duracao_minutos = 60
WHERE id = 2;
```

### Desativar temporizador

```sql
-- Desativar temporizador
UPDATE propostas
SET temporizador_inicio = NULL,
    temporizador_duracao_minutos = NULL
WHERE id = 1;
```

### Verificar propostas com limite atingido

```sql
SELECT
    p.id,
    p.nome,
    p.limite_votantes,
    COUNT(v.id) as total_votos,
    p.limite_votantes - COUNT(v.id) as vagas_restantes
FROM propostas p
LEFT JOIN votos v ON p.id = v.proposta_id
WHERE p.limite_votantes IS NOT NULL
GROUP BY p.id, p.nome, p.limite_votantes
HAVING COUNT(v.id) >= p.limite_votantes;
```

### Relatório completo de propostas

```sql
SELECT
    p.id,
    p.numero,
    p.nome,
    p.status,
    p.esta_ativa,
    p.limite_votantes,
    COUNT(v.id) as total_votos,
    CASE
        WHEN p.limite_votantes IS NULL THEN 'Ilimitado'
        ELSE CAST(p.limite_votantes - COUNT(v.id) AS NVARCHAR) + ' vagas'
    END as vagas_disponiveis,
    p.temporizador_inicio,
    p.temporizador_duracao_minutos,
    CASE
        WHEN p.temporizador_inicio IS NULL THEN 'Sem temporizador'
        WHEN DATEADD(MINUTE, p.temporizador_duracao_minutos, p.temporizador_inicio) > GETDATE() THEN 'Timer ativo'
        ELSE 'Timer expirado'
    END as status_temporizador
FROM propostas p
LEFT JOIN votos v ON p.id = v.proposta_id
GROUP BY
    p.id, p.numero, p.nome, p.status, p.esta_ativa,
    p.limite_votantes, p.temporizador_inicio, p.temporizador_duracao_minutos
ORDER BY p.id;
```

## 🛡️ Segurança

### O script é seguro?

✅ **SIM**. O script foi desenvolvido com práticas de segurança:

- **Idempotente**: Pode ser executado múltiplas vezes sem causar erros
- **Verifica existência**: Não cria colunas duplicadas
- **Não remove dados**: Apenas adiciona estruturas
- **Não modifica dados**: Não altera registros existentes
- **Apenas ALTER TABLE**: Operações seguras

### Recomendações

1. **Faça backup antes de executar**:
   ```sql
   BACKUP DATABASE SistemaVotacaoCNT
   TO DISK = 'C:\Backups\SistemaVotacaoCNT_Backup.bak'
   WITH FORMAT, NAME = 'Backup antes de adicionar limite_votantes';
   ```

2. **Execute primeiro em ambiente de teste** se disponível

3. **Verifique permissões** necessárias:
   - ALTER TABLE

## 🔄 Rollback (Reverter)

Se precisar remover as colunas:

```sql
-- Remover as 3 colunas
ALTER TABLE propostas DROP COLUMN limite_votantes;
ALTER TABLE propostas DROP COLUMN temporizador_inicio;
ALTER TABLE propostas DROP COLUMN temporizador_duracao_minutos;
```

**⚠️ ATENÇÃO**: Isso vai remover todos os dados dessas colunas. Use com cuidado!

## 🐛 Troubleshooting

### Erro: "Table 'propostas' does not exist"

**Problema**: Tabela propostas não existe.

**Solução**: Execute primeiro o script de criação do banco completo:
```powershell
sqlcmd -S localhost -E -d SistemaVotacaoCNT -i script-banco-sqlserver.sql
```

### Aviso: "Column already exists"

**Isso é normal!** O script verifica se as colunas já existem antes de criar. Se você vir:

```
⚠ Coluna limite_votantes já existe
⚠ Coluna temporizador_inicio já existe
⚠ Coluna temporizador_duracao_minutos já existe
```

Significa que o script já foi executado anteriormente. Isso não é um erro.

### Erro: "Invalid column name"

**Problema**: Código ainda está tentando usar colunas antigas.

**Solução**: Certifique-se de atualizar a aplicação Laravel com o código mais recente:
```bash
cd codigo-fonte/servico
git pull
php artisan migrate
```

## 📚 Integração com Laravel

Após executar este script, as migrations Laravel devem funcionar normalmente:

```bash
cd codigo-fonte/servico
php artisan migrate
```

A migration correspondente é:
- `2025_11_11_142050_adicionar_limite_votantes_propostas.php`

## 🔗 Arquivos Relacionados

- **Migration Laravel**: `database/migrations/2025_11_11_142050_adicionar_limite_votantes_propostas.php`
- **Model**: `app/Models/Proposta.php`
- **Controller**: `app/Http/Controllers/PropostaController.php`
- **API Route**: `POST /api/propostas/{id}/ativar-temporizador`

## 💡 Casos de Uso

### Caso 1: Votação com Limite

```sql
-- Proposta para 100 delegados
UPDATE propostas
SET limite_votantes = 100,
    nome = 'Votação da Delegação Regional'
WHERE id = 1;

-- Verificar progresso
SELECT
    (SELECT COUNT(*) FROM votos WHERE proposta_id = 1) as votos_registrados,
    limite_votantes,
    limite_votantes - (SELECT COUNT(*) FROM votos WHERE proposta_id = 1) as vagas_restantes
FROM propostas WHERE id = 1;
```

### Caso 2: Votação com Tempo e Limite

```sql
-- Proposta com 50 votantes e 30 minutos
UPDATE propostas
SET limite_votantes = 50,
    temporizador_inicio = GETDATE(),
    temporizador_duracao_minutos = 30
WHERE id = 2;
```

### Caso 3: Votação Ilimitada com Tempo

```sql
-- Proposta sem limite mas com 1 hora de duração
UPDATE propostas
SET limite_votantes = NULL,
    temporizador_inicio = GETDATE(),
    temporizador_duracao_minutos = 60
WHERE id = 3;
```

## 📞 Próximos Passos

1. **Executar o script** no banco de dados
2. **Verificar as colunas** criadas
3. **Atualizar aplicação Laravel** (já implementado no commit `135a921`)
4. **Testar no painel admin**:
   - Definir limite de votantes em uma proposta
   - Ativar temporizador
   - Testar votação com limite
5. **Monitorar logs** durante testes

## 📖 Documentação Relacionada

- **Script completo do banco**: `script-banco-sqlserver.sql`
- **Scripts de bancadas**: `script-bancadas-sqlserver.sql`
- **Documentação geral**: `SCRIPTS_SQL_SERVER.md`

---

**Versão**: 1.0
**Data**: 2025-11-11
**Compatibilidade**: SQL Server 2016+
**Commit Laravel**: `135a921`
