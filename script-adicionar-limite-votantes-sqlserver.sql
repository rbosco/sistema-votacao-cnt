-- =============================================
-- Script SQL Server: Adicionar limite_votantes
-- Sistema de Votação CNT
-- =============================================
-- Este script adiciona a coluna limite_votantes
-- e os campos de temporizador na tabela propostas
-- =============================================

USE SistemaVotacaoCNT;
GO

PRINT '============================================='
PRINT 'ADICIONANDO COLUNAS NA TABELA PROPOSTAS'
PRINT '============================================='
PRINT ''

-- =============================================
-- 1. ADICIONAR COLUNA limite_votantes
-- =============================================
PRINT '1. Adicionando coluna limite_votantes'
PRINT '--------------------------------------------'

IF NOT EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[propostas]')
    AND name = 'limite_votantes'
)
BEGIN
    ALTER TABLE [dbo].[propostas]
    ADD [limite_votantes] INT NULL;

    PRINT '✓ Coluna limite_votantes adicionada com sucesso'
    PRINT '  Tipo: INT NULL'
    PRINT '  Descrição: Limite máximo de votantes para esta proposta'
END
ELSE
BEGIN
    PRINT '⚠ Coluna limite_votantes já existe'
END

PRINT ''
GO

-- =============================================
-- 2. ADICIONAR COLUNA temporizador_inicio
-- =============================================
PRINT '2. Adicionando coluna temporizador_inicio'
PRINT '--------------------------------------------'

IF NOT EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[propostas]')
    AND name = 'temporizador_inicio'
)
BEGIN
    ALTER TABLE [dbo].[propostas]
    ADD [temporizador_inicio] DATETIME2(7) NULL;

    PRINT '✓ Coluna temporizador_inicio adicionada com sucesso'
    PRINT '  Tipo: DATETIME2(7) NULL'
    PRINT '  Descrição: Data/hora de início do temporizador da proposta'
END
ELSE
BEGIN
    PRINT '⚠ Coluna temporizador_inicio já existe'
END

PRINT ''
GO

-- =============================================
-- 3. ADICIONAR COLUNA temporizador_duracao_minutos
-- =============================================
PRINT '3. Adicionando coluna temporizador_duracao_minutos'
PRINT '--------------------------------------------'

IF NOT EXISTS (
    SELECT * FROM sys.columns
    WHERE object_id = OBJECT_ID(N'[dbo].[propostas]')
    AND name = 'temporizador_duracao_minutos'
)
BEGIN
    ALTER TABLE [dbo].[propostas]
    ADD [temporizador_duracao_minutos] INT NULL;

    PRINT '✓ Coluna temporizador_duracao_minutos adicionada com sucesso'
    PRINT '  Tipo: INT NULL'
    PRINT '  Descrição: Duração do temporizador em minutos'
END
ELSE
BEGIN
    PRINT '⚠ Coluna temporizador_duracao_minutos já existe'
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

-- Verificar estrutura da tabela propostas
PRINT 'Estrutura atualizada da tabela propostas:'
PRINT '--------------------------------------------'

SELECT
    COLUMN_NAME AS Coluna,
    DATA_TYPE AS Tipo,
    CHARACTER_MAXIMUM_LENGTH AS Tamanho,
    IS_NULLABLE AS Permite_NULL,
    COLUMN_DEFAULT AS Valor_Padrao
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'propostas'
AND COLUMN_NAME IN ('limite_votantes', 'temporizador_inicio', 'temporizador_duracao_minutos')
ORDER BY ORDINAL_POSITION;

PRINT ''
PRINT '============================================='
PRINT 'Colunas adicionadas com sucesso!'
PRINT '============================================='
PRINT ''
PRINT 'Resumo das alterações:'
PRINT '  ✓ limite_votantes (INT NULL)'
PRINT '  ✓ temporizador_inicio (DATETIME2 NULL)'
PRINT '  ✓ temporizador_duracao_minutos (INT NULL)'
PRINT ''
PRINT 'Próximos passos:'
PRINT '  1. Atualizar propostas existentes com limite_votantes se necessário'
PRINT '  2. Configurar temporizador nas propostas via painel admin'
PRINT '  3. Testar votação com limite de votantes'
PRINT ''
PRINT 'Exemplo de UPDATE (opcional):'
PRINT '  UPDATE propostas SET limite_votantes = 100 WHERE id = 1;'
PRINT ''
PRINT '============================================='
GO

-- =============================================
-- EXEMPLOS DE QUERIES ÚTEIS
-- =============================================

-- Para definir limite de votantes em uma proposta:
-- UPDATE propostas SET limite_votantes = 100 WHERE id = 1;

-- Para verificar propostas com limite definido:
-- SELECT id, nome, limite_votantes,
--        (SELECT COUNT(*) FROM votos WHERE proposta_id = propostas.id) as total_votos
-- FROM propostas
-- WHERE limite_votantes IS NOT NULL;

-- Para ativar temporizador em uma proposta:
-- UPDATE propostas
-- SET temporizador_inicio = GETDATE(),
--     temporizador_duracao_minutos = 30
-- WHERE id = 1;

-- Para verificar se temporizador está ativo:
-- SELECT
--     id,
--     nome,
--     temporizador_inicio,
--     temporizador_duracao_minutos,
--     DATEADD(MINUTE, temporizador_duracao_minutos, temporizador_inicio) as temporizador_fim,
--     CASE
--         WHEN DATEADD(MINUTE, temporizador_duracao_minutos, temporizador_inicio) > GETDATE()
--         THEN 'Ativo'
--         ELSE 'Expirado'
--     END as status_temporizador
-- FROM propostas
-- WHERE temporizador_inicio IS NOT NULL;
