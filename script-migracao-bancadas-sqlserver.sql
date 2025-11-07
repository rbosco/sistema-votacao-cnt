-- =============================================
-- Script de Migração: Adicionar Bancadas
-- Sistema de Votação CNT - SQL Server
-- =============================================
-- Este script adiciona a tabela de bancadas e modifica
-- a tabela votos para usar foreign key ao invés de string
-- =============================================

PRINT '============================================='
PRINT 'Iniciando migração para adicionar bancadas...'
PRINT '============================================='
GO

-- =============================================
-- PASSO 1: Criar tabela bancadas
-- =============================================
PRINT ''
PRINT 'PASSO 1: Criando tabela bancadas...'
GO

IF OBJECT_ID('bancadas', 'U') IS NULL
BEGIN
    CREATE TABLE bancadas (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        nome NVARCHAR(50) NOT NULL UNIQUE,
        sigla NVARCHAR(10) NULL,
        created_at DATETIME2 NULL,
        updated_at DATETIME2 NULL
    );

    PRINT '✓ Tabela bancadas criada com sucesso!'
END
ELSE
BEGIN
    PRINT '⚠ Tabela bancadas já existe, pulando...'
END
GO

-- =============================================
-- PASSO 2: Inserir bancadas padrão
-- =============================================
PRINT ''
PRINT 'PASSO 2: Inserindo bancadas padrão...'
GO

IF NOT EXISTS (SELECT 1 FROM bancadas WHERE nome = 'Trabalhadores')
BEGIN
    INSERT INTO bancadas (nome, sigla, created_at, updated_at)
    VALUES ('Trabalhadores', 'TRAB', GETDATE(), GETDATE());
    PRINT '✓ Bancada "Trabalhadores" inserida'
END

IF NOT EXISTS (SELECT 1 FROM bancadas WHERE nome = 'Empregadores')
BEGIN
    INSERT INTO bancadas (nome, sigla, created_at, updated_at)
    VALUES ('Empregadores', 'EMPR', GETDATE(), GETDATE());
    PRINT '✓ Bancada "Empregadores" inserida'
END

IF NOT EXISTS (SELECT 1 FROM bancadas WHERE nome = 'Governo')
BEGIN
    INSERT INTO bancadas (nome, sigla, created_at, updated_at)
    VALUES ('Governo', 'GOV', GETDATE(), GETDATE());
    PRINT '✓ Bancada "Governo" inserida'
END
GO

-- =============================================
-- PASSO 3: Adicionar coluna bancada_id na tabela votos
-- =============================================
PRINT ''
PRINT 'PASSO 3: Adicionando coluna bancada_id na tabela votos...'
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('votos')
    AND name = 'bancada_id'
)
BEGIN
    ALTER TABLE votos
    ADD bancada_id BIGINT NULL;

    PRINT '✓ Coluna bancada_id adicionada'
END
ELSE
BEGIN
    PRINT '⚠ Coluna bancada_id já existe'
END
GO

-- =============================================
-- PASSO 4: Migrar dados existentes (bancada string -> bancada_id)
-- =============================================
PRINT ''
PRINT 'PASSO 4: Migrando dados existentes...'
GO

IF EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('votos')
    AND name = 'bancada'
)
BEGIN
    -- Migrar Trabalhadores
    UPDATE votos
    SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Trabalhadores')
    WHERE bancada = 'Trabalhadores';

    -- Migrar Empregadores
    UPDATE votos
    SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Empregadores')
    WHERE bancada = 'Empregadores';

    -- Migrar Governo
    UPDATE votos
    SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Governo')
    WHERE bancada = 'Governo';

    DECLARE @rowsUpdated INT = @@ROWCOUNT;
    PRINT '✓ ' + CAST(@rowsUpdated AS NVARCHAR) + ' registros migrados'
END
ELSE
BEGIN
    PRINT '⚠ Coluna bancada não existe, pulando migração de dados...'
END
GO

-- =============================================
-- PASSO 5: Adicionar foreign key constraint
-- =============================================
PRINT ''
PRINT 'PASSO 5: Adicionando foreign key constraint...'
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys
    WHERE name = 'FK_votos_bancada_id'
    AND parent_object_id = OBJECT_ID('votos')
)
BEGIN
    ALTER TABLE votos
    ADD CONSTRAINT FK_votos_bancada_id
    FOREIGN KEY (bancada_id) REFERENCES bancadas(id)
    ON DELETE NO ACTION;

    PRINT '✓ Foreign key constraint adicionada'
END
ELSE
BEGIN
    PRINT '⚠ Foreign key constraint já existe'
END
GO

-- =============================================
-- PASSO 6: Adicionar índice na coluna bancada_id
-- =============================================
PRINT ''
PRINT 'PASSO 6: Adicionando índice...'
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_votos_bancada_id'
    AND object_id = OBJECT_ID('votos')
)
BEGIN
    CREATE INDEX IX_votos_bancada_id ON votos(bancada_id);
    PRINT '✓ Índice IX_votos_bancada_id criado'
END
ELSE
BEGIN
    PRINT '⚠ Índice IX_votos_bancada_id já existe'
END
GO

-- =============================================
-- PASSO 7: Remover coluna bancada antiga (string)
-- =============================================
PRINT ''
PRINT 'PASSO 7: Removendo coluna bancada antiga...'
GO

IF EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('votos')
    AND name = 'bancada'
)
BEGIN
    -- Verificar se há dados não migrados
    DECLARE @unmigrated INT;
    SELECT @unmigrated = COUNT(*)
    FROM votos
    WHERE bancada IS NOT NULL
    AND bancada_id IS NULL;

    IF @unmigrated > 0
    BEGIN
        PRINT '⚠ ATENÇÃO: ' + CAST(@unmigrated AS NVARCHAR) + ' registros não foram migrados!'
        PRINT '⚠ Verifique os dados antes de remover a coluna bancada'
        PRINT '⚠ Cancelando remoção da coluna bancada para segurança...'
    END
    ELSE
    BEGIN
        ALTER TABLE votos
        DROP COLUMN bancada;

        PRINT '✓ Coluna bancada removida com sucesso'
    END
END
ELSE
BEGIN
    PRINT '⚠ Coluna bancada não existe, pulando...'
END
GO

-- =============================================
-- PASSO 8: Registrar migração
-- =============================================
PRINT ''
PRINT 'PASSO 8: Registrando migração...'
GO

IF NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2025_11_05_142628_criar_tabela_bancadas'
)
BEGIN
    INSERT INTO migrations (migration, batch)
    SELECT '2025_11_05_142628_criar_tabela_bancadas', ISNULL(MAX(batch), 0) + 1
    FROM migrations;

    PRINT '✓ Migração criar_tabela_bancadas registrada'
END

IF NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2025_11_05_143004_modificar_bancada_para_bancada_id_tabela_votos'
)
BEGIN
    INSERT INTO migrations (migration, batch)
    SELECT '2025_11_05_143004_modificar_bancada_para_bancada_id_tabela_votos', ISNULL(MAX(batch), 0)
    FROM migrations;

    PRINT '✓ Migração modificar_bancada_para_bancada_id registrada'
END
GO

-- =============================================
-- VERIFICAÇÃO FINAL
-- =============================================
PRINT ''
PRINT '============================================='
PRINT 'VERIFICAÇÃO FINAL'
PRINT '============================================='
GO

-- Verificar bancadas
DECLARE @bancadasCount INT;
SELECT @bancadasCount = COUNT(*) FROM bancadas;
PRINT 'Total de bancadas: ' + CAST(@bancadasCount AS NVARCHAR)

-- Verificar votos com bancada
DECLARE @votosComBancada INT;
SELECT @votosComBancada = COUNT(*) FROM votos WHERE bancada_id IS NOT NULL;
PRINT 'Votos com bancada: ' + CAST(@votosComBancada AS NVARCHAR)

-- Verificar votos sem bancada
DECLARE @votosSemBancada INT;
SELECT @votosSemBancada = COUNT(*) FROM votos WHERE bancada_id IS NULL;
IF @votosSemBancada > 0
BEGIN
    PRINT '⚠ ATENÇÃO: ' + CAST(@votosSemBancada AS NVARCHAR) + ' votos sem bancada'
END

PRINT ''
PRINT '============================================='
PRINT 'Migração concluída com sucesso!'
PRINT '============================================='
PRINT ''
PRINT 'Próximos passos:'
PRINT '  1. Verifique se os dados foram migrados corretamente'
PRINT '  2. Atualize o código da aplicação Laravel'
PRINT '  3. Atualize o frontend Vue.js para usar bancada_id'
PRINT '  4. Teste a funcionalidade de votação'
PRINT ''
PRINT '============================================='
GO
