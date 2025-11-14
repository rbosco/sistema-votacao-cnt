-- =============================================
-- Script: Adicionar coluna temporizador_ativo
-- Descrição: Adiciona coluna para ativar/desativar temporizador via switch
-- Banco: SQL Server
-- Data: 2025-11-14
-- =============================================

USE [nome_do_banco];
GO

-- Adicionar coluna temporizador_ativo
IF NOT EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[propostas]')
    AND name = 'temporizador_ativo'
)
BEGIN
    ALTER TABLE [dbo].[propostas]
    ADD [temporizador_ativo] BIT NOT NULL DEFAULT 0;

    PRINT '✓ Coluna temporizador_ativo adicionada com sucesso';
END
ELSE
BEGIN
    PRINT 'ℹ️ Coluna temporizador_ativo já existe';
END
GO

-- Verificar a estrutura da tabela
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'propostas'
AND COLUMN_NAME = 'temporizador_ativo';
GO

PRINT '';
PRINT '========================================';
PRINT 'Script executado com sucesso!';
PRINT '========================================';
GO
