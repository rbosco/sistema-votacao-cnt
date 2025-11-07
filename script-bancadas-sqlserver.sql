-- =============================================
-- Script SQL Server: Bancadas
-- Sistema de Votação CNT
-- =============================================
-- Este script adiciona a funcionalidade de bancadas
-- ao banco de dados do Sistema de Votação CNT
-- =============================================

USE SistemaVotacaoCNT;
GO

-- =============================================
-- 1. CRIAR TABELA BANCADAS
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[bancadas]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[bancadas] (
        [id] BIGINT IDENTITY(1,1) NOT NULL,
        [nome] NVARCHAR(50) NOT NULL,
        [sigla] NVARCHAR(10) NULL,
        [created_at] DATETIME2(7) NULL,
        [updated_at] DATETIME2(7) NULL,
        CONSTRAINT [PK_bancadas] PRIMARY KEY CLUSTERED ([id] ASC)
    );

    -- Criar índice único no nome
    CREATE UNIQUE NONCLUSTERED INDEX [IX_bancadas_nome] ON [dbo].[bancadas]
    (
        [nome] ASC
    );

    PRINT '✓ Tabela bancadas criada com sucesso';
END
ELSE
BEGIN
    PRINT '⚠ Tabela bancadas já existe';
END
GO

-- =============================================
-- 2. INSERIR BANCADAS PADRÃO
-- =============================================

-- Trabalhadores
IF NOT EXISTS (SELECT 1 FROM bancadas WHERE nome = 'Trabalhadores')
BEGIN
    INSERT INTO bancadas (nome, sigla, created_at, updated_at)
    VALUES ('Trabalhadores', 'TRAB', GETDATE(), GETDATE());
    PRINT '✓ Bancada Trabalhadores inserida';
END

-- Empregadores
IF NOT EXISTS (SELECT 1 FROM bancadas WHERE nome = 'Empregadores')
BEGIN
    INSERT INTO bancadas (nome, sigla, created_at, updated_at)
    VALUES ('Empregadores', 'EMPR', GETDATE(), GETDATE());
    PRINT '✓ Bancada Empregadores inserida';
END

-- Governo
IF NOT EXISTS (SELECT 1 FROM bancadas WHERE nome = 'Governo')
BEGIN
    INSERT INTO bancadas (nome, sigla, created_at, updated_at)
    VALUES ('Governo', 'GOV', GETDATE(), GETDATE());
    PRINT '✓ Bancada Governo inserida';
END
GO

-- =============================================
-- 3. ADICIONAR COLUNA bancada_id NA TABELA votos
-- =============================================
IF NOT EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[votos]')
    AND name = 'bancada_id'
)
BEGIN
    ALTER TABLE [dbo].[votos]
    ADD [bancada_id] BIGINT NULL;

    PRINT '✓ Coluna bancada_id adicionada na tabela votos';
END
ELSE
BEGIN
    PRINT '⚠ Coluna bancada_id já existe na tabela votos';
END
GO

-- =============================================
-- 4. CRIAR FOREIGN KEY
-- =============================================
IF NOT EXISTS (
    SELECT * FROM sys.foreign_keys
    WHERE object_id = OBJECT_ID(N'[dbo].[FK_votos_bancada_id]')
    AND parent_object_id = OBJECT_ID(N'[dbo].[votos]')
)
BEGIN
    ALTER TABLE [dbo].[votos] WITH CHECK
    ADD CONSTRAINT [FK_votos_bancada_id] FOREIGN KEY([bancada_id])
    REFERENCES [dbo].[bancadas] ([id]);

    ALTER TABLE [dbo].[votos] CHECK CONSTRAINT [FK_votos_bancada_id];

    PRINT '✓ Foreign key FK_votos_bancada_id criada';
END
ELSE
BEGIN
    PRINT '⚠ Foreign key FK_votos_bancada_id já existe';
END
GO

-- =============================================
-- 5. CRIAR ÍNDICE
-- =============================================
IF NOT EXISTS (
    SELECT * FROM sys.indexes
    WHERE name = 'IX_votos_bancada_id'
    AND object_id = OBJECT_ID(N'[dbo].[votos]')
)
BEGIN
    CREATE NONCLUSTERED INDEX [IX_votos_bancada_id] ON [dbo].[votos]
    (
        [bancada_id] ASC
    );

    PRINT '✓ Índice IX_votos_bancada_id criado';
END
ELSE
BEGIN
    PRINT '⚠ Índice IX_votos_bancada_id já existe';
END
GO

-- =============================================
-- 6. VERIFICAÇÃO
-- =============================================
PRINT '';
PRINT '============================================='
PRINT 'VERIFICAÇÃO FINAL'
PRINT '============================================='

-- Listar bancadas
SELECT
    id,
    nome,
    sigla,
    created_at
FROM bancadas
ORDER BY id;

-- Contar bancadas
DECLARE @totalBancadas INT;
SELECT @totalBancadas = COUNT(*) FROM bancadas;
PRINT 'Total de bancadas cadastradas: ' + CAST(@totalBancadas AS NVARCHAR(10));

-- Verificar estrutura da tabela votos
IF EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[votos]')
    AND name = 'bancada_id'
)
BEGIN
    PRINT '✓ Coluna bancada_id está presente na tabela votos';
END

-- Verificar foreign key
IF EXISTS (
    SELECT * FROM sys.foreign_keys
    WHERE object_id = OBJECT_ID(N'[dbo].[FK_votos_bancada_id]')
)
BEGIN
    PRINT '✓ Foreign key está configurada corretamente';
END

PRINT '';
PRINT '============================================='
PRINT 'Estrutura de bancadas instalada com sucesso!'
PRINT '============================================='
GO

-- =============================================
-- QUERIES ÚTEIS PARA CONSULTA
-- =============================================

-- Para listar todas as bancadas:
-- SELECT * FROM bancadas;

-- Para ver votos agrupados por bancada:
-- SELECT
--     b.nome as bancada,
--     COUNT(v.id) as total_votos
-- FROM bancadas b
-- LEFT JOIN votos v ON b.id = v.bancada_id
-- GROUP BY b.nome;

-- Para atualizar bancada de um voto específico:
-- UPDATE votos
-- SET bancada_id = (SELECT id FROM bancadas WHERE nome = 'Trabalhadores')
-- WHERE id = 1;
