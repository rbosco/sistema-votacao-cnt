-- =============================================
-- Script SQL Server: Alterações na Tabela VOTOS
-- Adicionar suporte a Bancadas
-- Sistema de Votação CNT
-- =============================================
-- Este script modifica a tabela VOTOS para usar
-- bancada_id (foreign key) ao invés de bancada (string)
-- =============================================

USE SistemaVotacaoCNT;
GO

PRINT '============================================='
PRINT 'ALTERAÇÕES NA TABELA VOTOS - BANCADAS'
PRINT '============================================='
PRINT ''

-- =============================================
-- VERIFICAÇÃO INICIAL
-- =============================================
PRINT 'VERIFICAÇÃO INICIAL:'
PRINT '--------------------------------------------'

-- Verificar se a tabela votos existe
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[votos]') AND type in (N'U'))
BEGIN
    PRINT '❌ ERRO: Tabela votos não existe!'
    PRINT 'Execute primeiro o script de criação do banco de dados.'
    RAISERROR('Tabela votos não encontrada', 16, 1)
    RETURN
END
ELSE
BEGIN
    PRINT '✓ Tabela votos existe'
END

-- Verificar se a tabela bancadas existe
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[bancadas]') AND type in (N'U'))
BEGIN
    PRINT '❌ ERRO: Tabela bancadas não existe!'
    PRINT 'Execute primeiro o script de criação da tabela bancadas.'
    RAISERROR('Tabela bancadas não encontrada', 16, 1)
    RETURN
END
ELSE
BEGIN
    PRINT '✓ Tabela bancadas existe'

    -- Mostrar bancadas disponíveis
    DECLARE @totalBancadas INT
    SELECT @totalBancadas = COUNT(*) FROM bancadas
    PRINT '  Total de bancadas: ' + CAST(@totalBancadas AS NVARCHAR(10))
END

-- Verificar se existe coluna bancada antiga (string)
DECLARE @temColunaBancadaAntiga BIT = 0
IF EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[votos]')
    AND name = 'bancada'
)
BEGIN
    SET @temColunaBancadaAntiga = 1
    PRINT '⚠ Coluna "bancada" (string) existe - será migrada'

    -- Contar votos com bancada
    DECLARE @votosComBancada INT
    SELECT @votosComBancada = COUNT(*) FROM votos WHERE bancada IS NOT NULL
    PRINT '  Votos com bancada: ' + CAST(@votosComBancada AS NVARCHAR(10))
END
ELSE
BEGIN
    PRINT '✓ Coluna "bancada" antiga não existe'
END

-- Verificar se já existe coluna bancada_id
IF EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[votos]')
    AND name = 'bancada_id'
)
BEGIN
    PRINT '⚠ Coluna "bancada_id" já existe'
    PRINT '  O script será executado de forma idempotente'
END

PRINT ''
GO

-- =============================================
-- PASSO 1: ADICIONAR COLUNA bancada_id
-- =============================================
PRINT 'PASSO 1: Adicionando coluna bancada_id'
PRINT '--------------------------------------------'

IF NOT EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[votos]')
    AND name = 'bancada_id'
)
BEGIN
    -- Adicionar coluna após cpf_votante
    ALTER TABLE [dbo].[votos]
    ADD [bancada_id] BIGINT NULL;

    PRINT '✓ Coluna bancada_id adicionada com sucesso'
    PRINT '  Tipo: BIGINT NULL'
    PRINT '  Posição: Após cpf_votante'
END
ELSE
BEGIN
    PRINT '⚠ Coluna bancada_id já existe, pulando...'
END

PRINT ''
GO

-- =============================================
-- PASSO 2: MIGRAR DADOS EXISTENTES
-- =============================================
PRINT 'PASSO 2: Migrando dados existentes'
PRINT '--------------------------------------------'

-- Verificar se existe coluna bancada antiga
IF EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[votos]')
    AND name = 'bancada'
)
BEGIN
    PRINT 'Migrando dados de bancada (string) para bancada_id (FK)...'

    -- Migrar Trabalhadores
    DECLARE @idTrabalhadores BIGINT
    SELECT @idTrabalhadores = id FROM bancadas WHERE nome = 'Trabalhadores'

    UPDATE votos
    SET bancada_id = @idTrabalhadores
    WHERE bancada = 'Trabalhadores'
    AND bancada_id IS NULL

    DECLARE @migrTrab INT = @@ROWCOUNT
    IF @migrTrab > 0
        PRINT '✓ Migrados ' + CAST(@migrTrab AS NVARCHAR) + ' votos de Trabalhadores'

    -- Migrar Empregadores
    DECLARE @idEmpregadores BIGINT
    SELECT @idEmpregadores = id FROM bancadas WHERE nome = 'Empregadores'

    UPDATE votos
    SET bancada_id = @idEmpregadores
    WHERE bancada = 'Empregadores'
    AND bancada_id IS NULL

    DECLARE @migrEmpr INT = @@ROWCOUNT
    IF @migrEmpr > 0
        PRINT '✓ Migrados ' + CAST(@migrEmpr AS NVARCHAR) + ' votos de Empregadores'

    -- Migrar Governo
    DECLARE @idGoverno BIGINT
    SELECT @idGoverno = id FROM bancadas WHERE nome = 'Governo'

    UPDATE votos
    SET bancada_id = @idGoverno
    WHERE bancada = 'Governo'
    AND bancada_id IS NULL

    DECLARE @migrGov INT = @@ROWCOUNT
    IF @migrGov > 0
        PRINT '✓ Migrados ' + CAST(@migrGov AS NVARCHAR) + ' votos de Governo'

    -- Verificar se há dados não migrados
    DECLARE @naoMigrados INT
    SELECT @naoMigrados = COUNT(*)
    FROM votos
    WHERE bancada IS NOT NULL
    AND bancada_id IS NULL

    IF @naoMigrados > 0
    BEGIN
        PRINT '⚠ ATENÇÃO: ' + CAST(@naoMigrados AS NVARCHAR) + ' registros não foram migrados'
        PRINT '  Valores de bancada que não correspondem aos esperados:'

        SELECT DISTINCT bancada, COUNT(*) as quantidade
        FROM votos
        WHERE bancada IS NOT NULL
        AND bancada_id IS NULL
        GROUP BY bancada
    END
    ELSE
    BEGIN
        PRINT '✓ Todos os dados foram migrados com sucesso'
    END
END
ELSE
BEGIN
    PRINT '⚠ Coluna "bancada" antiga não existe, pulando migração...'
END

PRINT ''
GO

-- =============================================
-- PASSO 3: CRIAR FOREIGN KEY
-- =============================================
PRINT 'PASSO 3: Criando Foreign Key'
PRINT '--------------------------------------------'

IF NOT EXISTS (
    SELECT * FROM sys.foreign_keys
    WHERE object_id = OBJECT_ID(N'[dbo].[FK_votos_bancada_id]')
    AND parent_object_id = OBJECT_ID(N'[dbo].[votos]')
)
BEGIN
    -- Verificar se há valores inválidos antes de criar FK
    DECLARE @valoresInvalidos INT
    SELECT @valoresInvalidos = COUNT(*)
    FROM votos
    WHERE bancada_id IS NOT NULL
    AND bancada_id NOT IN (SELECT id FROM bancadas)

    IF @valoresInvalidos > 0
    BEGIN
        PRINT '❌ ERRO: Existem ' + CAST(@valoresInvalidos AS NVARCHAR) + ' votos com bancada_id inválido'
        PRINT '  Corrija os dados antes de criar a Foreign Key:'

        SELECT bancada_id, COUNT(*) as quantidade
        FROM votos
        WHERE bancada_id IS NOT NULL
        AND bancada_id NOT IN (SELECT id FROM bancadas)
        GROUP BY bancada_id

        RAISERROR('Valores inválidos em bancada_id', 16, 1)
    END
    ELSE
    BEGIN
        ALTER TABLE [dbo].[votos] WITH CHECK
        ADD CONSTRAINT [FK_votos_bancada_id] FOREIGN KEY([bancada_id])
        REFERENCES [dbo].[bancadas] ([id])

        ALTER TABLE [dbo].[votos] CHECK CONSTRAINT [FK_votos_bancada_id]

        PRINT '✓ Foreign Key FK_votos_bancada_id criada com sucesso'
        PRINT '  votos.bancada_id -> bancadas.id'
        PRINT '  ON DELETE: NO ACTION'
    END
END
ELSE
BEGIN
    PRINT '⚠ Foreign Key FK_votos_bancada_id já existe'
END

PRINT ''
GO

-- =============================================
-- PASSO 4: CRIAR ÍNDICE
-- =============================================
PRINT 'PASSO 4: Criando Índice'
PRINT '--------------------------------------------'

IF NOT EXISTS (
    SELECT * FROM sys.indexes
    WHERE name = 'IX_votos_bancada_id'
    AND object_id = OBJECT_ID(N'[dbo].[votos]')
)
BEGIN
    CREATE NONCLUSTERED INDEX [IX_votos_bancada_id] ON [dbo].[votos]
    (
        [bancada_id] ASC
    )
    WITH (
        PAD_INDEX = OFF,
        STATISTICS_NORECOMPUTE = OFF,
        SORT_IN_TEMPDB = OFF,
        DROP_EXISTING = OFF,
        ONLINE = OFF,
        ALLOW_ROW_LOCKS = ON,
        ALLOW_PAGE_LOCKS = ON
    );

    PRINT '✓ Índice IX_votos_bancada_id criado com sucesso'
    PRINT '  Tipo: NONCLUSTERED'
    PRINT '  Coluna: bancada_id ASC'
END
ELSE
BEGIN
    PRINT '⚠ Índice IX_votos_bancada_id já existe'
END

PRINT ''
GO

-- =============================================
-- PASSO 5: REMOVER COLUNA BANCADA ANTIGA (OPCIONAL)
-- =============================================
PRINT 'PASSO 5: Removendo coluna bancada antiga'
PRINT '--------------------------------------------'

IF EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[votos]')
    AND name = 'bancada'
)
BEGIN
    -- Verificar se há dados não migrados
    DECLARE @dadosNaoMigrados INT
    SELECT @dadosNaoMigrados = COUNT(*)
    FROM votos
    WHERE bancada IS NOT NULL
    AND bancada_id IS NULL

    IF @dadosNaoMigrados > 0
    BEGIN
        PRINT '⚠ ATENÇÃO: ' + CAST(@dadosNaoMigrados AS NVARCHAR) + ' votos não foram migrados'
        PRINT '⚠ A coluna "bancada" NÃO será removida por segurança'
        PRINT ''
        PRINT '  Execute a migração manual dos dados restantes e depois remova a coluna com:'
        PRINT '  ALTER TABLE votos DROP COLUMN bancada;'
    END
    ELSE
    BEGIN
        -- Remover coluna bancada antiga
        ALTER TABLE [dbo].[votos]
        DROP COLUMN [bancada];

        PRINT '✓ Coluna "bancada" removida com sucesso'
        PRINT '  Todos os dados foram migrados para bancada_id'
    END
END
ELSE
BEGIN
    PRINT '✓ Coluna "bancada" antiga não existe'
END

PRINT ''
GO

-- =============================================
-- VERIFICAÇÃO FINAL
-- =============================================
PRINT '============================================='
PRINT 'VERIFICAÇÃO FINAL'
PRINT '============================================='
PRINT ''

-- 1. Estrutura da coluna bancada_id
PRINT '1. ESTRUTURA DA COLUNA bancada_id:'
PRINT '--------------------------------------------'
SELECT
    COLUMN_NAME as Coluna,
    DATA_TYPE as Tipo,
    CHARACTER_MAXIMUM_LENGTH as Tamanho,
    IS_NULLABLE as Permite_NULL
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'votos'
AND COLUMN_NAME = 'bancada_id'

-- 2. Foreign Key
PRINT ''
PRINT '2. FOREIGN KEY:'
PRINT '--------------------------------------------'
IF EXISTS (
    SELECT * FROM sys.foreign_keys
    WHERE name = 'FK_votos_bancada_id'
)
BEGIN
    SELECT
        fk.name AS ForeignKey,
        OBJECT_NAME(fk.parent_object_id) AS TabelaOrigem,
        COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS ColunaOrigem,
        OBJECT_NAME(fk.referenced_object_id) AS TabelaReferencia,
        COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS ColunaReferencia
    FROM sys.foreign_keys AS fk
    INNER JOIN sys.foreign_key_columns AS fkc
        ON fk.object_id = fkc.constraint_object_id
    WHERE fk.name = 'FK_votos_bancada_id'
END
ELSE
BEGIN
    PRINT '⚠ Foreign Key não encontrada'
END

-- 3. Índice
PRINT ''
PRINT '3. ÍNDICE:'
PRINT '--------------------------------------------'
IF EXISTS (
    SELECT * FROM sys.indexes
    WHERE name = 'IX_votos_bancada_id'
)
BEGIN
    SELECT
        i.name AS Nome_Indice,
        i.type_desc AS Tipo,
        COL_NAME(ic.object_id, ic.column_id) AS Coluna,
        ic.is_descending_key AS Descendente
    FROM sys.indexes AS i
    INNER JOIN sys.index_columns AS ic
        ON i.object_id = ic.object_id
        AND i.index_id = ic.index_id
    WHERE i.name = 'IX_votos_bancada_id'
END
ELSE
BEGIN
    PRINT '⚠ Índice não encontrado'
END

-- 4. Distribuição de votos por bancada
PRINT ''
PRINT '4. DISTRIBUIÇÃO DE VOTOS POR BANCADA:'
PRINT '--------------------------------------------'
SELECT
    b.id,
    b.nome AS Bancada,
    b.sigla AS Sigla,
    COUNT(v.id) AS Total_Votos,
    SUM(CASE WHEN v.voto = 1 THEN 1 ELSE 0 END) AS Votos_Sim,
    SUM(CASE WHEN v.voto = 0 THEN 1 ELSE 0 END) AS Votos_Nao
FROM bancadas b
LEFT JOIN votos v ON b.id = v.bancada_id
GROUP BY b.id, b.nome, b.sigla
ORDER BY b.id

-- 5. Estatísticas gerais
PRINT ''
PRINT '5. ESTATÍSTICAS GERAIS:'
PRINT '--------------------------------------------'
DECLARE @totalVotos INT, @votosComBancadaFinal INT, @votosSemBancada INT

SELECT @totalVotos = COUNT(*) FROM votos
SELECT @votosComBancadaFinal = COUNT(*) FROM votos WHERE bancada_id IS NOT NULL
SELECT @votosSemBancada = COUNT(*) FROM votos WHERE bancada_id IS NULL

PRINT 'Total de votos: ' + CAST(@totalVotos AS NVARCHAR(10))
PRINT 'Votos com bancada: ' + CAST(@votosComBancadaFinal AS NVARCHAR(10))
PRINT 'Votos sem bancada: ' + CAST(@votosSemBancada AS NVARCHAR(10))

IF @votosSemBancada > 0
BEGIN
    PRINT ''
    PRINT '⚠ ATENÇÃO: Existem votos sem bancada definida'
END

PRINT ''
PRINT '============================================='
PRINT 'ALTERAÇÕES CONCLUÍDAS COM SUCESSO!'
PRINT '============================================='
PRINT ''
PRINT 'Resumo das alterações:'
PRINT '  ✓ Coluna bancada_id adicionada'
PRINT '  ✓ Foreign Key configurada'
PRINT '  ✓ Índice criado'
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[votos]') AND name = 'bancada')
    PRINT '  ⚠ Coluna bancada antiga ainda existe'
ELSE
    PRINT '  ✓ Coluna bancada antiga removida'
PRINT ''
PRINT 'Próximos passos:'
PRINT '  1. Verificar a distribuição de votos acima'
PRINT '  2. Atualizar a aplicação Laravel (já configurada)'
PRINT '  3. Testar a votação no frontend'
PRINT ''
PRINT '============================================='
GO
