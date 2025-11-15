-- =====================================================
-- Script para popular a tabela de usuários
-- Sistema de Votação CNT
-- SQL Server
-- =====================================================

USE [nome_do_banco];
GO

-- Verificar se a tabela existe
IF OBJECT_ID(N'[dbo].[usuarios]', N'U') IS NULL
BEGIN
    PRINT '❌ ERRO: Tabela [usuarios] não existe. Execute as migrations primeiro.';
    RETURN;
END
GO

-- Limpar dados existentes (CUIDADO: isso apaga todos os usuários!)
-- Comente as linhas abaixo se quiser manter os usuários existentes
-- DELETE FROM [dbo].[usuarios];
-- PRINT '🗑️  Usuários existentes removidos';
-- GO

-- Inserir usuários de exemplo
-- IMPORTANTE: As senhas estão criptografadas com bcrypt
-- Senha padrão para todos: 123456
-- Senha do admin: admin123

DECLARE @DataAtual DATETIME = GETDATE();

-- 1. Usuário Administrador
IF NOT EXISTS (SELECT 1 FROM [dbo].[usuarios] WHERE cpf = '00000000191')
BEGIN
    INSERT INTO [dbo].[usuarios]
    ([nome], [cpf], [senha], [is_admin], [remember_token], [created_at], [updated_at])
    VALUES
    (
        'Administrador do Sistema',
        '00000000191',
        '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewKRQf.S/8j8tYnW', -- senha: admin123
        1, -- is_admin = true
        NULL,
        @DataAtual,
        @DataAtual
    );
    PRINT '✓ Usuário Admin criado (CPF: 000.000.001-91, Senha: admin123)';
END
ELSE
BEGIN
    PRINT 'ℹ️  Usuário Admin já existe (CPF: 000.000.001-91)';
END

-- 2. João Silva - Usuário Normal
IF NOT EXISTS (SELECT 1 FROM [dbo].[usuarios] WHERE cpf = '12345678901')
BEGIN
    INSERT INTO [dbo].[usuarios]
    ([nome], [cpf], [senha], [is_admin], [remember_token], [created_at], [updated_at])
    VALUES
    (
        'João Silva',
        '12345678901',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: 123456
        0, -- is_admin = false
        NULL,
        @DataAtual,
        @DataAtual
    );
    PRINT '✓ Usuário João Silva criado (CPF: 123.456.789-01, Senha: 123456)';
END
ELSE
BEGIN
    PRINT 'ℹ️  Usuário João Silva já existe (CPF: 123.456.789-01)';
END

-- 3. Maria Santos - Usuário Normal
IF NOT EXISTS (SELECT 1 FROM [dbo].[usuarios] WHERE cpf = '98765432109')
BEGIN
    INSERT INTO [dbo].[usuarios]
    ([nome], [cpf], [senha], [is_admin], [remember_token], [created_at], [updated_at])
    VALUES
    (
        'Maria Santos',
        '98765432109',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: 123456
        0, -- is_admin = false
        NULL,
        @DataAtual,
        @DataAtual
    );
    PRINT '✓ Usuário Maria Santos criado (CPF: 987.654.321-09, Senha: 123456)';
END
ELSE
BEGIN
    PRINT 'ℹ️  Usuário Maria Santos já existe (CPF: 987.654.321-09)';
END

-- 4. Pedro Oliveira - Usuário Normal
IF NOT EXISTS (SELECT 1 FROM [dbo].[usuarios] WHERE cpf = '11122233344')
BEGIN
    INSERT INTO [dbo].[usuarios]
    ([nome], [cpf], [senha], [is_admin], [remember_token], [created_at], [updated_at])
    VALUES
    (
        'Pedro Oliveira',
        '11122233344',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: 123456
        0, -- is_admin = false
        NULL,
        @DataAtual,
        @DataAtual
    );
    PRINT '✓ Usuário Pedro Oliveira criado (CPF: 111.222.333-44, Senha: 123456)';
END
ELSE
BEGIN
    PRINT 'ℹ️  Usuário Pedro Oliveira já existe (CPF: 111.222.333-44)';
END

-- 5. Ana Costa - Usuário Normal
IF NOT EXISTS (SELECT 1 FROM [dbo].[usuarios] WHERE cpf = '55566677788')
BEGIN
    INSERT INTO [dbo].[usuarios]
    ([nome], [cpf], [senha], [is_admin], [remember_token], [created_at], [updated_at])
    VALUES
    (
        'Ana Costa',
        '55566677788',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: 123456
        0, -- is_admin = false
        NULL,
        @DataAtual,
        @DataAtual
    );
    PRINT '✓ Usuário Ana Costa criado (CPF: 555.666.777-88, Senha: 123456)';
END
ELSE
BEGIN
    PRINT 'ℹ️  Usuário Ana Costa já existe (CPF: 555.666.777-88)';
END

-- 6. Carlos Ferreira - Administrador Secundário
IF NOT EXISTS (SELECT 1 FROM [dbo].[usuarios] WHERE cpf = '99988877766')
BEGIN
    INSERT INTO [dbo].[usuarios]
    ([nome], [cpf], [senha], [is_admin], [remember_token], [created_at], [updated_at])
    VALUES
    (
        'Carlos Ferreira',
        '99988877766',
        '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewKRQf.S/8j8tYnW', -- senha: admin123
        1, -- is_admin = true
        NULL,
        @DataAtual,
        @DataAtual
    );
    PRINT '✓ Usuário Carlos Ferreira (Admin) criado (CPF: 999.888.777-66, Senha: admin123)';
END
ELSE
BEGIN
    PRINT 'ℹ️  Usuário Carlos Ferreira já existe (CPF: 999.888.777-66)';
END

GO

-- Mostrar estatísticas
DECLARE @TotalUsuarios INT;
DECLARE @TotalAdmins INT;

SELECT @TotalUsuarios = COUNT(*) FROM [dbo].[usuarios];
SELECT @TotalAdmins = COUNT(*) FROM [dbo].[usuarios] WHERE [is_admin] = 1;

PRINT '';
PRINT '==========================================';
PRINT '  RESUMO DA POPULAÇÃO DE USUÁRIOS';
PRINT '==========================================';
PRINT 'Total de usuários: ' + CAST(@TotalUsuarios AS VARCHAR(10));
PRINT 'Administradores: ' + CAST(@TotalAdmins AS VARCHAR(10));
PRINT 'Usuários normais: ' + CAST((@TotalUsuarios - @TotalAdmins) AS VARCHAR(10));
PRINT '';
PRINT 'CREDENCIAIS DE ACESSO:';
PRINT '----------------------------------------';
PRINT 'ADMIN: CPF 000.000.001-91 | Senha: admin123';
PRINT 'USER:  CPF 123.456.789-01 | Senha: 123456';
PRINT '==========================================';
PRINT '';

GO
