-- =============================================
-- Script de Criação do Banco de Dados
-- Sistema de Votação CNT
-- SQL Server
-- =============================================

-- IMPORTANTE: Execute este script em um banco de dados já criado
-- ou descomente a seção abaixo para criar um novo banco

/*
-- Criar o banco de dados (descomente se necessário)
CREATE DATABASE SistemaVotacaoCNT;
GO

USE SistemaVotacaoCNT;
GO
*/

-- =============================================
-- 1. TABELA: usuarios
-- =============================================
IF OBJECT_ID('usuarios', 'U') IS NOT NULL
    DROP TABLE usuarios;
GO

CREATE TABLE usuarios (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    nome NVARCHAR(255) NOT NULL,
    cpf NVARCHAR(11) NOT NULL UNIQUE,
    senha NVARCHAR(255) NOT NULL,
    is_admin BIT NOT NULL DEFAULT 0,
    remember_token NVARCHAR(100) NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL
);
GO

-- Índice na coluna CPF
CREATE INDEX IX_usuarios_cpf ON usuarios(cpf);
GO

-- =============================================
-- 2. TABELA: tokens_redefinicao_senha
-- =============================================
IF OBJECT_ID('tokens_redefinicao_senha', 'U') IS NOT NULL
    DROP TABLE tokens_redefinicao_senha;
GO

CREATE TABLE tokens_redefinicao_senha (
    cpf NVARCHAR(255) PRIMARY KEY,
    token NVARCHAR(255) NOT NULL,
    created_at DATETIME2 NULL
);
GO

-- =============================================
-- 3. TABELA: sessoes
-- =============================================
IF OBJECT_ID('sessoes', 'U') IS NOT NULL
    DROP TABLE sessoes;
GO

CREATE TABLE sessoes (
    id NVARCHAR(255) PRIMARY KEY,
    user_id BIGINT NULL,
    ip_address NVARCHAR(45) NULL,
    user_agent NVARCHAR(MAX) NULL,
    payload NVARCHAR(MAX) NOT NULL,
    last_activity INT NOT NULL
);
GO

-- Índices
CREATE INDEX IX_sessoes_user_id ON sessoes(user_id);
CREATE INDEX IX_sessoes_last_activity ON sessoes(last_activity);
GO

-- =============================================
-- 4. TABELA: propostas
-- =============================================
IF OBJECT_ID('propostas', 'U') IS NOT NULL
    DROP TABLE propostas;
GO

CREATE TABLE propostas (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    numero NVARCHAR(255) NULL,
    nome NVARCHAR(255) NOT NULL,
    esta_ativa BIT NOT NULL DEFAULT 0,
    status NVARCHAR(20) NOT NULL DEFAULT 'nao_iniciada',
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL,
    deleted_at DATETIME2 NULL,
    CONSTRAINT CK_propostas_status CHECK (status IN ('nao_iniciada', 'em_votacao', 'encerrada'))
);
GO

-- Índices
CREATE INDEX IX_propostas_esta_ativa ON propostas(esta_ativa);
CREATE INDEX IX_propostas_status ON propostas(status);
GO

-- =============================================
-- 5. TABELA: bancadas (NOVA - Tabela de Domínio)
-- =============================================
IF OBJECT_ID('bancadas', 'U') IS NOT NULL
    DROP TABLE bancadas;
GO

CREATE TABLE bancadas (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    nome NVARCHAR(50) NOT NULL UNIQUE,
    sigla NVARCHAR(10) NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL
);
GO

-- Inserir as 3 bancadas padrão
INSERT INTO bancadas (nome, sigla, created_at, updated_at) VALUES
    ('Trabalhadores', 'TRAB', GETDATE(), GETDATE()),
    ('Empregadores', 'EMPR', GETDATE(), GETDATE()),
    ('Governo', 'GOV', GETDATE(), GETDATE());
GO

-- =============================================
-- 6. TABELA: votos
-- =============================================
IF OBJECT_ID('votos', 'U') IS NOT NULL
    DROP TABLE votos;
GO

CREATE TABLE votos (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    proposta_id BIGINT NOT NULL,
    cpf_votante NVARCHAR(11) NOT NULL,
    bancada_id BIGINT NULL,
    nome_votante NVARCHAR(255) NOT NULL,
    nome_sindicato NVARCHAR(255) NOT NULL,
    voto BIT NOT NULL, -- 1 = Sim, 0 = Não
    votado_em DATETIME2 NOT NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL,

    -- Foreign Keys
    CONSTRAINT FK_votos_proposta_id FOREIGN KEY (proposta_id)
        REFERENCES propostas(id) ON DELETE CASCADE,
    CONSTRAINT FK_votos_bancada_id FOREIGN KEY (bancada_id)
        REFERENCES bancadas(id) ON DELETE NO ACTION,

    -- Constraint Unique: Um CPF só pode votar uma vez por proposta
    CONSTRAINT UQ_votos_proposta_cpf UNIQUE (proposta_id, cpf_votante)
);
GO

-- Índices
CREATE INDEX IX_votos_proposta_id ON votos(proposta_id);
CREATE INDEX IX_votos_bancada_id ON votos(bancada_id);
CREATE INDEX IX_votos_cpf_votante ON votos(cpf_votante);
GO

-- =============================================
-- 7. TABELA: tokens_acesso_pessoal (Laravel Sanctum)
-- =============================================
IF OBJECT_ID('tokens_acesso_pessoal', 'U') IS NOT NULL
    DROP TABLE tokens_acesso_pessoal;
GO

CREATE TABLE tokens_acesso_pessoal (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    tokenable_type NVARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name NVARCHAR(255) NOT NULL,
    token NVARCHAR(64) NOT NULL UNIQUE,
    abilities NVARCHAR(MAX) NULL,
    last_used_at DATETIME2 NULL,
    expires_at DATETIME2 NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL
);
GO

-- Índices
CREATE INDEX IX_tokens_acesso_pessoal_tokenable ON tokens_acesso_pessoal(tokenable_type, tokenable_id);
GO

-- =============================================
-- 8. TABELA: configuracoes
-- =============================================
IF OBJECT_ID('configuracoes', 'U') IS NOT NULL
    DROP TABLE configuracoes;
GO

CREATE TABLE configuracoes (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    chave NVARCHAR(255) NOT NULL UNIQUE,
    valor NVARCHAR(MAX) NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL
);
GO

-- Inserir configurações padrão
INSERT INTO configuracoes (chave, valor, created_at, updated_at) VALUES
    ('banner_votacao', '/images/banner-cnt.png', GETDATE(), GETDATE()),
    ('temporizador_ativo', '0', GETDATE(), GETDATE()),
    ('temporizador_inicio', NULL, GETDATE(), GETDATE()),
    ('temporizador_duracao_minutos', '30', GETDATE(), GETDATE());
GO

-- =============================================
-- 9. TABELA: migrations (Laravel)
-- =============================================
IF OBJECT_ID('migrations', 'U') IS NOT NULL
    DROP TABLE migrations;
GO

CREATE TABLE migrations (
    id INT IDENTITY(1,1) PRIMARY KEY,
    migration NVARCHAR(255) NOT NULL,
    batch INT NOT NULL
);
GO

-- Registrar as migrations executadas
INSERT INTO migrations (migration, batch) VALUES
    ('2024_01_01_000000_criar_tabela_usuarios', 1),
    ('2024_01_01_000001_criar_tabela_propostas', 1),
    ('2024_01_01_000002_criar_tabela_votos', 1),
    ('2024_01_01_000003_criar_tabela_tokens_acesso_pessoal', 1),
    ('2024_01_01_000004_criar_tabela_configuracoes', 1),
    ('2024_01_01_000005_adicionar_status_propostas', 1),
    ('2025_11_04_084743_atualizar_enum_status_propostas', 1),
    ('2025_11_05_142628_criar_tabela_bancadas', 1),
    ('2025_11_05_143004_modificar_bancada_para_bancada_id_tabela_votos', 1);
GO

-- =============================================
-- DADOS INICIAIS
-- =============================================

-- Criar usuário administrador padrão
-- Senha: admin123 (Hash bcrypt - você deve alterar isso em produção)
-- IMPORTANTE: Altere a senha após o primeiro login!
INSERT INTO usuarios (nome, cpf, senha, is_admin, created_at, updated_at)
VALUES (
    'Administrador',
    '00000000000',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANh6/DG7qEu', -- senha: admin123
    1,
    GETDATE(),
    GETDATE()
);
GO

-- =============================================
-- FIM DO SCRIPT
-- =============================================

PRINT '============================================='
PRINT 'Banco de dados criado com sucesso!'
PRINT '============================================='
PRINT ''
PRINT 'Tabelas criadas:'
PRINT '  - usuarios'
PRINT '  - tokens_redefinicao_senha'
PRINT '  - sessoes'
PRINT '  - propostas'
PRINT '  - bancadas (NOVA - Tabela de Domínio)'
PRINT '  - votos (com bancada_id como foreign key)'
PRINT '  - tokens_acesso_pessoal'
PRINT '  - configuracoes'
PRINT '  - migrations'
PRINT ''
PRINT 'Dados iniciais inseridos:'
PRINT '  - 3 bancadas: Trabalhadores, Empregadores, Governo'
PRINT '  - 4 configurações do sistema'
PRINT '  - 1 usuário administrador (CPF: 00000000000, Senha: admin123)'
PRINT ''
PRINT 'IMPORTANTE:'
PRINT '  1. Altere a senha do administrador após o primeiro login'
PRINT '  2. Configure as variáveis de ambiente no arquivo .env'
PRINT '  3. Configure a conexão com o banco de dados no Laravel'
PRINT ''
PRINT '============================================='
GO
