-- Script para corrigir a constraint CHECK da coluna status
-- Execute este script diretamente no SQL Server Management Studio ou similar

USE DBFSRTCNT;
GO

-- Passo 1: Encontrar e remover a constraint CHECK antiga
DECLARE @ConstraintName NVARCHAR(200)
SELECT @ConstraintName = CONSTRAINT_NAME
FROM INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE
WHERE TABLE_NAME = 'propostas'
AND COLUMN_NAME = 'status'

IF @ConstraintName IS NOT NULL
BEGIN
    DECLARE @SQL1 NVARCHAR(500) = 'ALTER TABLE propostas DROP CONSTRAINT [' + @ConstraintName + ']'
    EXEC sp_executesql @SQL1
    PRINT 'Constraint CHECK antiga removida: ' + @ConstraintName
END
GO

-- Passo 2: Remover o default constraint se existir
DECLARE @DefaultName NVARCHAR(200)
SELECT @DefaultName = dc.name
FROM sys.default_constraints dc
JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
WHERE OBJECT_NAME(dc.parent_object_id) = 'propostas'
AND c.name = 'status'

IF @DefaultName IS NOT NULL
BEGIN
    DECLARE @SQL2 NVARCHAR(500) = 'ALTER TABLE propostas DROP CONSTRAINT [' + @DefaultName + ']'
    EXEC sp_executesql @SQL2
    PRINT 'Default constraint removido: ' + @DefaultName
END
GO

-- Passo 3: Modificar a coluna para NVARCHAR se necessário
ALTER TABLE propostas ALTER COLUMN status NVARCHAR(20) NOT NULL;
PRINT 'Coluna status modificada para NVARCHAR(20)'
GO

-- Passo 4: Adicionar novo default
ALTER TABLE propostas ADD DEFAULT 'nao_iniciada' FOR status;
PRINT 'Default adicionado: nao_iniciada'
GO

-- Passo 5: Adicionar nova constraint CHECK com os 3 valores
ALTER TABLE propostas
ADD CONSTRAINT CK_propostas_status
CHECK (status IN ('nao_iniciada', 'em_votacao', 'encerrada'));
PRINT 'Nova constraint CHECK adicionada com 3 valores'
GO

PRINT 'Correção concluída com sucesso!'
