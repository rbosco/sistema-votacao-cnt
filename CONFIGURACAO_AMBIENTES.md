# Configuração de Ambientes - Sistema de Votação CNT

Este documento descreve como configurar e executar o sistema nos três ambientes oficiais.

## 🌍 Ambientes Disponíveis

O sistema está configurado para rodar em três ambientes:

1. **Desenvolvimento** - `http://localhost:8090`
2. **Homologação** - `https://conferencianacional-hml.trabalho.gov.br`
3. **Produção** - `https://conferencianacional.trabalho.gov.br`

## 📁 Estrutura de Arquivos

### Frontend (Vue.js + Vite)

```
codigo-fonte/cliente/
├── .env.development       # Variáveis para desenvolvimento
├── .env.homologation      # Variáveis para homologação
├── .env.production        # Variáveis para produção
├── .env.example           # Exemplo de configuração
├── Dockerfile             # Dockerfile para desenvolvimento
├── Dockerfile.production  # Dockerfile para produção/homologação
├── nginx.conf            # Configuração do Nginx
└── vite.config.ts        # Configuração do Vite (hosts permitidos)
```

### Backend (Laravel 11)

```
codigo-fonte/servico/
├── .env.development      # Variáveis para desenvolvimento
├── .env.homologation     # Variáveis para homologação
├── .env.production       # Variáveis para produção
├── .env.example          # Exemplo de configuração
└── config/cors.php       # Configuração CORS
```

### Docker Compose

```
docker-compose.development.yml   # Configuração para desenvolvimento
docker-compose.homologation.yml  # Configuração para homologação
docker-compose.production.yml    # Configuração para produção
```

## 🚀 Como Usar

### 1. Ambiente de Desenvolvimento

**Características:**
- Hot reload ativado
- Debug habilitado
- SQL Server em container local
- Ideal para desenvolvimento local

**Iniciar:**
```bash
# 1. Subir containers
docker-compose -f docker-compose.development.yml up -d

# 2. Acessar aplicação
Frontend: http://localhost:8088
Backend:  http://localhost:8090/api

# 3. Logs em tempo real
docker-compose -f docker-compose.development.yml logs -f

# 4. Parar containers
docker-compose -f docker-compose.development.yml down
```

**Scripts NPM disponíveis:**
```bash
cd codigo-fonte/cliente

npm run dev              # Inicia dev server (desenvolvimento)
npm run build            # Build para desenvolvimento
```

---

### 2. Ambiente de Homologação

**Características:**
- Build otimizado com Vite
- Servido com Nginx
- Debug desabilitado
- Conecta ao banco de homologação

**Configurar antes do primeiro deploy:**

1. **Editar `.env.homologation` do backend:**
   ```bash
   nano codigo-fonte/servico/.env.homologation
   ```

   Configure:
   - `APP_KEY` - Gere com: `php artisan key:generate --show`
   - `DB_HOST` - Host do SQL Server de homologação
   - `DB_DATABASE` - Nome do banco
   - `DB_USERNAME` - Usuário do banco
   - `DB_PASSWORD` - Senha do banco
   - `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` - Configurações de email

**Deploy:**
```bash
# 1. Build dos containers
docker-compose -f docker-compose.homologation.yml build

# 2. Subir aplicação
docker-compose -f docker-compose.homologation.yml up -d

# 3. Executar migrations
docker exec -it phpsrt-hml php artisan migrate --force

# 4. Verificar status
docker-compose -f docker-compose.homologation.yml ps

# 5. Ver logs
docker-compose -f docker-compose.homologation.yml logs -f

# 6. Parar
docker-compose -f docker-compose.homologation.yml down
```

**Scripts NPM:**
```bash
cd codigo-fonte/cliente

npm run dev:homolog      # Dev server com config de homologação
npm run build:homolog    # Build para homologação
```

---

### 3. Ambiente de Produção

**Características:**
- Build otimizado para produção
- Nginx com compressão gzip
- Cache agressivo de assets
- Debug desabilitado
- Logs apenas de erros
- Conecta ao banco de produção

**Configurar antes do primeiro deploy:**

1. **Editar `.env.production` do backend:**
   ```bash
   nano codigo-fonte/servico/.env.production
   ```

   Configure:
   - `APP_KEY` - **GERE UMA CHAVE ÚNICA** com: `php artisan key:generate --show`
   - `DB_HOST` - Host do SQL Server de produção
   - `DB_DATABASE` - Nome do banco
   - `DB_USERNAME` - Usuário do banco
   - `DB_PASSWORD` - Senha do banco
   - `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` - Configurações de email
   - **Importante:** `APP_DEBUG=false` e `LOG_LEVEL=error`

**Deploy:**
```bash
# 1. Build dos containers
docker-compose -f docker-compose.production.yml build

# 2. Subir aplicação
docker-compose -f docker-compose.production.yml up -d

# 3. Executar migrations
docker exec -it phpsrt-prod php artisan migrate --force

# 4. Otimizar para produção
docker exec -it phpsrt-prod php artisan config:cache
docker exec -it phpsrt-prod php artisan route:cache
docker exec -it phpsrt-prod php artisan view:cache
docker exec -it phpsrt-prod composer dump-autoload -o

# 5. Verificar status
docker-compose -f docker-compose.production.yml ps

# 6. Monitorar logs
docker-compose -f docker-compose.production.yml logs -f --tail=100

# 7. Para parar (use com cuidado!)
docker-compose -f docker-compose.production.yml down
```

**Scripts NPM:**
```bash
cd codigo-fonte/cliente

npm run dev:prod         # Dev server com config de produção
npm run build:prod       # Build para produção
```

---

## 🔧 Variáveis de Ambiente

### Frontend (Vite)

| Variável | Desenvolvimento | Homologação | Produção |
|----------|----------------|-------------|----------|
| `VITE_APP_ENV` | development | homologation | production |
| `VITE_API_URL` | http://localhost:8090/api | https://conferencianacional-hml.trabalho.gov.br/api | https://conferencianacional.trabalho.gov.br/api |
| `VITE_APP_NAME` | Sistema CNT - Dev | Sistema CNT - Homologação | Sistema de Votação CNT |

### Backend (Laravel)

| Variável | Desenvolvimento | Homologação | Produção |
|----------|----------------|-------------|----------|
| `APP_ENV` | development | homologation | production |
| `APP_DEBUG` | true | false | false |
| `LOG_LEVEL` | debug | info | error |
| `APP_URL` | http://localhost:8090 | https://conferencianacional-hml.trabalho.gov.br | https://conferencianacional.trabalho.gov.br |
| `FRONTEND_URL` | http://localhost:8088 | https://conferencianacional-hml.trabalho.gov.br | https://conferencianacional.trabalho.gov.br |

---

## 🔐 Configurações de Segurança

### CORS (Cross-Origin Resource Sharing)

O arquivo `config/cors.php` está configurado para aceitar requisições dos seguintes domínios:

- `http://localhost:8088` (desenvolvimento)
- `https://conferencianacional.trabalho.gov.br` (produção)
- `https://conferencianacional-hml.trabalho.gov.br` (homologação)
- Qualquer subdomínio de `*.trabalho.gov.br`

### Vite Allowed Hosts

O arquivo `vite.config.ts` permite os seguintes hosts:

- `localhost`
- `conferencianacional.trabalho.gov.br`
- `conferencianacional-hml.trabalho.gov.br`
- `.trabalho.gov.br` (qualquer subdomínio)

---

## ✅ Checklist de Deploy

### Antes do Deploy em Homologação

- [ ] Configurar `.env.homologation` com credenciais corretas
- [ ] Gerar `APP_KEY` única para homologação
- [ ] Testar conexão com banco de dados de homologação
- [ ] Verificar configurações de email
- [ ] Confirmar `APP_DEBUG=false`
- [ ] Build da aplicação: `npm run build:homolog`
- [ ] Testar build localmente com `npm run preview`

### Antes do Deploy em Produção

- [ ] ⚠️ **BACKUP DO BANCO DE DADOS**
- [ ] Testar TODAS as funcionalidades em homologação
- [ ] Configurar `.env.production` com credenciais corretas
- [ ] Gerar `APP_KEY` **ÚNICA** para produção (diferente de homologação!)
- [ ] Confirmar `APP_DEBUG=false` e `LOG_LEVEL=error`
- [ ] Verificar certificado SSL está válido
- [ ] Build da aplicação: `npm run build:prod`
- [ ] Preparar plano de rollback

### Após Deploy

- [ ] Executar migrations: `php artisan migrate --force`
- [ ] Cachear configurações: `php artisan config:cache`
- [ ] Cachear rotas: `php artisan route:cache`
- [ ] Cachear views: `php artisan view:cache`
- [ ] Otimizar autoload: `composer dump-autoload -o`
- [ ] Verificar logs de erro
- [ ] Testar login
- [ ] Testar criação de proposta
- [ ] Testar votação
- [ ] Testar visualização de resultados
- [ ] Monitorar performance

---

## 🐛 Troubleshooting

### Erro: "Blocked request. This host is not allowed"

**Causa:** O Vite está bloqueando o host por segurança.

**Solução:**
1. Verifique se o host está em `allowedHosts` no `vite.config.ts`
2. Reinicie o container do frontend
3. Limpe o cache do navegador

### Erro de CORS

**Causa:** Frontend e backend em domínios diferentes sem configuração CORS adequada.

**Solução:**
1. Verifique `FRONTEND_URL` no `.env` do backend
2. Confirme domínio em `config/cors.php`
3. Reinicie o container do backend
4. Limpe cache: `docker exec -it phpsrt-[dev|hml|prod] php artisan config:clear`

### Build falha no frontend

**Causa:** Dependências desatualizadas ou cache corrompido.

**Solução:**
```bash
cd codigo-fonte/cliente
rm -rf node_modules package-lock.json dist .vite
npm install
npm run build:[dev|homolog|prod]
```

### Erro de conexão com banco de dados

**Causa:** Credenciais incorretas ou firewall bloqueando.

**Solução:**
1. Teste conexão manualmente:
   ```bash
   docker exec -it phpsrt-[dev|hml|prod] php artisan tinker
   >>> DB::connection()->getPdo();
   ```
2. Verifique credenciais no `.env`
3. Confirme firewall/VPN permite conexão
4. Teste com `sqlcmd` ou similar

### Container não inicia

**Causa:** Porta já em uso ou erro de configuração.

**Solução:**
```bash
# Ver logs completos
docker-compose -f docker-compose.[env].yml logs

# Verificar portas em uso
netstat -tulpn | grep :8088
netstat -tulpn | grep :8090

# Limpar tudo e reconstruir
docker-compose -f docker-compose.[env].yml down -v
docker-compose -f docker-compose.[env].yml build --no-cache
docker-compose -f docker-compose.[env].yml up -d
```

---

## 📊 Monitoramento

### Ver logs em tempo real

```bash
# Frontend
docker logs -f websrt-[dev|hml|prod]

# Backend
docker logs -f phpsrt-[dev|hml|prod]

# Ambos
docker-compose -f docker-compose.[env].yml logs -f
```

### Ver status dos containers

```bash
docker-compose -f docker-compose.[env].yml ps
```

### Estatísticas de uso

```bash
docker stats websrt-[dev|hml|prod] phpsrt-[dev|hml|prod]
```

---

## 🔄 Comandos Úteis

### Reiniciar apenas um serviço

```bash
docker-compose -f docker-compose.[env].yml restart phpsrt
docker-compose -f docker-compose.[env].yml restart websrt
```

### Executar comando dentro do container

```bash
# Backend
docker exec -it phpsrt-[dev|hml|prod] bash
docker exec -it phpsrt-[dev|hml|prod] php artisan migrate:status
docker exec -it phpsrt-[dev|hml|prod] php artisan cache:clear

# Frontend
docker exec -it websrt-[dev|hml|prod] sh
docker exec -it websrt-[dev|hml|prod] npm run build
```

### Limpar volumes e reconstruir

```bash
docker-compose -f docker-compose.[env].yml down -v
docker volume prune -f
docker-compose -f docker-compose.[env].yml build --no-cache
docker-compose -f docker-compose.[env].yml up -d
```

---

## 📞 Suporte

Para dúvidas ou problemas:

- 📧 Email: suporte@trabalho.gov.br
- 📖 Documentação Laravel: https://laravel.com/docs/11.x
- 📖 Documentação Vue.js: https://vuejs.org/guide
- 📖 Documentação Vite: https://vitejs.dev/guide

---

**Última atualização:** 2025-01-05
**Versão:** 2.0
