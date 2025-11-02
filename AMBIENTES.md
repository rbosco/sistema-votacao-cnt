# Configuração de Ambientes

Este documento descreve como configurar e utilizar os diferentes ambientes do Sistema de Votação CNT.

## 📋 Ambientes Disponíveis

O sistema suporta três ambientes distintos:

1. **LOCAL/DOCKER** - Desenvolvimento local com Docker
2. **DESENVOLVIMENTO (DEV)** - Servidor de desenvolvimento CNT
3. **PRODUÇÃO (PROD)** - Servidor de produção CNT

## 🗂️ Arquivos de Configuração

### Localização
`codigo-fonte/servico/`

### Arquivos

| Arquivo | Ambiente | Descrição |
|---------|----------|-----------|
| `.env.example` | Local/Docker | SQL Server em container local |
| `.env.dev.example` | Desenvolvimento | Servidor DEV CNT (10.244.62.136) |
| `.env.prod.example` | Produção | Servidor PROD CNT (10.246.62.183) |

## 🔧 Configuração por Ambiente

### 🏠 LOCAL/DOCKER

**Arquivo:** `.env.example`

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlsrv
DB_HOST=sqlserver
DB_PORT=1433
DB_DATABASE=sistema_votacao_cnt
DB_USERNAME=sa
DB_PASSWORD=SistemaVotacao@2024
DB_TRUST_SERVER_CERTIFICATE=true
```

**Uso:**
```bash
# Já configurado no docker-compose.yml
docker-compose up -d
```

**Características:**
- ✅ SQL Server em container
- ✅ Banco criado automaticamente
- ✅ Migrations executadas automaticamente
- ✅ Seeders executados automaticamente
- ✅ Cache desabilitado
- ✅ Debug habilitado

---

### 🔨 DESENVOLVIMENTO (DEV)

**Arquivo:** `.env.dev.example`

```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://desenvolvimento.cnt.org.br

# Banco de Dados - DESENVOLVIMENTO
DB_CONNECTION=sqlsrv
DB_HOST=10.244.62.136
DB_PORT=1433
DB_DATABASE=DBFSRTCNT
DB_USERNAME=sistema.srtcnt
DB_PASSWORD=KPrGr3czKsqxznpYBwnKhmv5
DB_TRUST_SERVER_CERTIFICATE=true
```

**Uso:**
```bash
# 1. Copiar configuração de desenvolvimento
cp codigo-fonte/servico/.env.dev.example codigo-fonte/servico/.env

# 2. Definir variável de ambiente
export APP_ENV=development

# 3. Iniciar container
docker-compose up -d

# OU usando variável inline no docker-compose
APP_ENV=development docker-compose up -d
```

**Características:**
- ✅ Conecta ao servidor DEV (10.244.62.136)
- ✅ Banco já existente (DBFSRTCNT)
- ✅ Migrations executadas automaticamente
- ✅ Seeders executados se tabela vazia
- ⚠️ Não cria banco (usa existente)
- ✅ Debug habilitado
- ✅ Cache em arquivos

---

### 🚀 PRODUÇÃO (PROD)

**Arquivo:** `.env.prod.example`

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votacao.cnt.org.br

# Banco de Dados - PRODUÇÃO
DB_CONNECTION=sqlsrv
DB_HOST=10.246.62.183
DB_PORT=1433
DB_DATABASE=DBFSRTCNT
DB_USERNAME=sistema.srtcnt
DB_PASSWORD=Uqs4GPBWwzpcAQLIN4IWRFgI
DB_TRUST_SERVER_CERTIFICATE=true
```

**Uso:**
```bash
# 1. Copiar configuração de produção
cp codigo-fonte/servico/.env.prod.example codigo-fonte/servico/.env

# 2. Definir variável de ambiente
export APP_ENV=production

# 3. Iniciar container
docker-compose up -d

# OU usando variável inline
APP_ENV=production docker-compose up -d
```

**Características:**
- ✅ Conecta ao servidor PROD (10.246.62.183)
- ✅ Banco já existente (DBFSRTCNT)
- ✅ Migrations executadas automaticamente
- ❌ Seeders NÃO executados automaticamente
- ⚠️ Não cria banco (usa existente)
- ❌ Debug desabilitado
- ✅ Cache em Redis
- ✅ Otimizações de produção ativadas

---

## 🔄 Fluxo de Detecção de Ambiente

O entrypoint detecta automaticamente o ambiente:

```bash
1. Lê variável APP_ENV
2. Seleciona arquivo .env apropriado:
   - local → .env.example
   - development/dev → .env.dev.example
   - production/prod → .env.prod.example
3. Copia para .env (se não existir)
4. Configura serviços baseado no ambiente
```

## 🛠️ Configuração Manual

### Opção 1: Variável de Ambiente

```bash
# Definir no docker-compose.yml
services:
  servico:
    environment:
      - APP_ENV=development  # ou production
```

### Opção 2: Arquivo .env Direto

```bash
# Copiar manualmente o arquivo desejado
cp codigo-fonte/servico/.env.dev.example codigo-fonte/servico/.env

# Editar se necessário
nano codigo-fonte/servico/.env
```

## 📊 Comparação de Ambientes

| Característica | Local | Dev | Prod |
|----------------|-------|-----|------|
| **Servidor** | Container | 10.244.62.136 | 10.246.62.183 |
| **Banco** | sistema_votacao_cnt | DBFSRTCNT | DBFSRTCNT |
| **Criação DB** | ✅ Automática | ❌ Manual | ❌ Manual |
| **Migrations** | ✅ Automática | ✅ Automática | ✅ Automática |
| **Seeders** | ✅ Automático | ✅ Se vazio | ❌ Manual |
| **Debug** | ✅ Ativo | ✅ Ativo | ❌ Desativado |
| **Cache** | Arquivo | Arquivo | Redis |
| **Otimizações** | ❌ | ❌ | ✅ |
| **SSL/TLS** | ❌ | ❌ | ✅ |

## 🔐 Segurança

### ⚠️ IMPORTANTE

Os arquivos `.env.*.example` contêm **senhas reais** e devem ser tratados com cuidado:

1. **NÃO** fazer commit de arquivos `.env` (apenas `.env.*.example`)
2. **Usar** variáveis de ambiente em CI/CD
3. **Rotacionar** senhas periodicamente
4. **Limitar** acesso aos arquivos de configuração

### Boas Práticas

```bash
# Em produção, use secrets do Docker/Kubernetes
docker secret create db_password senha_segura

# Ou variáveis de ambiente
export DB_PASSWORD="senha_muito_segura"
```

## 🚨 Troubleshooting

### Problema: Ambiente errado detectado

**Solução:**
```bash
# Verificar variável APP_ENV
echo $APP_ENV

# Forçar ambiente
export APP_ENV=production
docker-compose restart servico
```

### Problema: Não conecta ao banco remoto

**Soluções:**
1. Verificar firewall/VPN
2. Testar conectividade:
```bash
docker-compose exec servico bash
/opt/mssql-tools18/bin/sqlcmd -S 10.244.62.136 -U sistema.srtcnt -P KPrGr3czKsqxznpYBwnKhmv5 -C
```

### Problema: Migrations falham

**Solução:**
```bash
# Executar manualmente
docker-compose exec servico bash
php artisan migrate --force

# Ver status
php artisan migrate:status
```

### Problema: Seeders não executam em produção

**Isso é intencional!** Em produção, execute manualmente:
```bash
docker-compose exec servico bash
php artisan db:seed --force
```

## 📝 Checklist de Deploy

### Desenvolvimento

- [ ] Copiar `.env.dev.example` para `.env`
- [ ] Definir `APP_ENV=development`
- [ ] Verificar conectividade com 10.244.62.136
- [ ] Executar `docker-compose up -d`
- [ ] Verificar migrations: `docker-compose exec servico php artisan migrate:status`
- [ ] Testar acesso ao sistema

### Produção

- [ ] Copiar `.env.prod.example` para `.env`
- [ ] Definir `APP_ENV=production`
- [ ] **Verificar senhas** estão corretas
- [ ] Verificar conectividade com 10.246.62.183
- [ ] **Fazer backup** do banco antes
- [ ] Executar `docker-compose up -d`
- [ ] Verificar migrations: `docker-compose exec servico php artisan migrate:status`
- [ ] **NÃO** executar seeders automaticamente
- [ ] Testar todas as funcionalidades
- [ ] Monitorar logs: `docker-compose logs -f servico`

## 🔄 Migração entre Ambientes

### De Local para Dev

```bash
# 1. Exportar dados se necessário
docker-compose exec servico php artisan db:backup

# 2. Mudar para dev
cp codigo-fonte/servico/.env.dev.example codigo-fonte/servico/.env
export APP_ENV=development

# 3. Reiniciar
docker-compose restart servico
```

### De Dev para Prod

```bash
# 1. Testar TUDO em dev
# 2. Fazer backup do banco de produção
# 3. Copiar configuração
cp codigo-fonte/servico/.env.prod.example codigo-fonte/servico/.env

# 4. Atualizar APP_ENV
export APP_ENV=production

# 5. Deploy
docker-compose up -d

# 6. Monitorar
docker-compose logs -f servico
```

## 📞 Suporte

Para dúvidas sobre configuração de ambientes:
- **Email:** suporte@cnt.org.br
- **Documentação:** Ver README.md e ENTRYPOINTS.md

---

**Última atualização:** 2024-11-02
