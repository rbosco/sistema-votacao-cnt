# Guia de Build e Deploy - Resolução do Erro 405

## 🔴 Problema

Erro 405 (Method Not Allowed) ao acessar `/entrar`:
```
POST https://conferencianacional-hml.trabalho.gov.br/entrar 405
```

**Causa:** O build foi feito sem carregar as variáveis de ambiente corretas, fazendo com que a aplicação tente acessar `/entrar` ao invés de `/api/entrar`.

## ✅ Solução

### Para Homologação

1. **Fazer rebuild do frontend com o ambiente correto:**

   ```bash
   cd codigo-fonte/cliente

   # IMPORTANTE: Use o comando específico para homologação
   npm run build:homolog
   ```

2. **Reconstruir o container Docker:**

   ```bash
   # Voltar para raiz do projeto
   cd ../..

   # Rebuild forçado do container
   docker-compose -f docker-compose.homologation.yml build --no-cache websrt

   # Reiniciar o container
   docker-compose -f docker-compose.homologation.yml up -d websrt
   ```

3. **Verificar se as variáveis estão corretas:**

   Acesse o console do navegador e execute:
   ```javascript
   console.log(import.meta.env.VITE_API_URL)
   ```

   **Deve retornar:**
   ```
   https://conferencianacional-hml.trabalho.gov.br/api
   ```

### Para Produção

```bash
cd codigo-fonte/cliente

# Build para produção
npm run build:prod

# Voltar e rebuild do container
cd ../..
docker-compose -f docker-compose.production.yml build --no-cache websrt
docker-compose -f docker-compose.production.yml up -d websrt
```

### Para Desenvolvimento

```bash
cd codigo-fonte/cliente

# Apenas iniciar o dev server
npm run dev

# Ou via Docker
cd ../..
docker-compose -f docker-compose.development.yml up -d
```

---

## 📋 Comandos NPM Corretos

**IMPORTANTE:** Sempre use os comandos específicos para cada ambiente!

### Ambiente de Desenvolvimento
```bash
npm run dev              # Dev server
npm run build            # Build
```

### Ambiente de Homologação
```bash
npm run dev:homolog      # Dev server com config de homologação
npm run build:homolog    # Build para homologação ⚠️ USE ESTE!
```

### Ambiente de Produção
```bash
npm run dev:prod         # Dev server com config de produção
npm run build:prod       # Build para produção ⚠️ USE ESTE!
```

---

## 🔍 Como Verificar se o Build Está Correto

### 1. Verificar o arquivo gerado

Após o build, verifique o arquivo `dist/index.html`:

```bash
cd codigo-fonte/cliente
cat dist/index.html | grep -i "api"
```

Você deve ver referências para a URL correta da API.

### 2. Verificar no navegador

Após deploy, abra o DevTools (F12) → Network e veja para onde as requisições estão indo:

**❌ Errado (sem /api):**
```
POST https://conferencianacional-hml.trabalho.gov.br/entrar
```

**✅ Correto (com /api):**
```
POST https://conferencianacional-hml.trabalho.gov.br/api/entrar
```

### 3. Inspecionar variáveis de ambiente

No console do navegador:
```javascript
// Verificar a URL da API
console.log(import.meta.env.VITE_API_URL)

// Verificar o ambiente
console.log(import.meta.env.VITE_APP_ENV)
```

**Para Homologação deve mostrar:**
```javascript
VITE_API_URL: "https://conferencianacional-hml.trabalho.gov.br/api"
VITE_APP_ENV: "homologation"
```

**Para Produção deve mostrar:**
```javascript
VITE_API_URL: "https://conferencianacional.trabalho.gov.br/api"
VITE_APP_ENV: "production"
```

---

## 🐛 Troubleshooting

### Problema: Ainda aparece erro 405 após rebuild

**Soluções:**

1. **Limpar cache do navegador:**
   - Ctrl+Shift+Delete
   - Ou usar Ctrl+Shift+R (hard refresh)

2. **Limpar completamente o build:**
   ```bash
   cd codigo-fonte/cliente
   rm -rf dist node_modules .vite
   npm install
   npm run build:homolog
   ```

3. **Verificar se o Dockerfile.production está usando o ARG corretamente:**
   ```bash
   # Rebuild forçado sem cache
   docker-compose -f docker-compose.homologation.yml build --no-cache
   docker-compose -f docker-compose.homologation.yml up -d
   ```

### Problema: import.meta.env.VITE_API_URL está undefined

**Causa:** As variáveis de ambiente não foram injetadas durante o build.

**Solução:**
1. Verifique se o arquivo `.env.homologation` existe
2. Use o comando correto: `npm run build:homolog`
3. Reconstrua o container Docker

### Problema: Variáveis apontam para localhost

**Causa:** O build foi feito com `npm run build` ao invés de `npm run build:homolog`

**Solução:**
```bash
# Limpar build anterior
rm -rf dist

# Build com ambiente correto
npm run build:homolog

# Rebuild do Docker
docker-compose -f docker-compose.homologation.yml build --no-cache websrt
docker-compose -f docker-compose.homologation.yml up -d websrt
```

---

## ✅ Checklist de Deploy

### Antes do Deploy

- [ ] Confirmar que existe o arquivo `.env.homologation` ou `.env.production`
- [ ] Verificar se as URLs estão corretas nos arquivos .env
- [ ] Confirmar que o backend está acessível

### Durante o Deploy

- [ ] Usar o comando correto: `npm run build:homolog` ou `npm run build:prod`
- [ ] Verificar saída do build - não deve ter erros
- [ ] Rebuild do container Docker com `--no-cache`
- [ ] Restart do container

### Após o Deploy

- [ ] Abrir DevTools → Network
- [ ] Tentar fazer login
- [ ] Verificar se a requisição vai para `/api/entrar` (não `/entrar`)
- [ ] Verificar no console: `import.meta.env.VITE_API_URL`
- [ ] Limpar cache do navegador se necessário

---

## 🚀 Resumo - Solução Rápida

Se você está com o erro 405:

```bash
# 1. Ir para pasta do frontend
cd codigo-fonte/cliente

# 2. Limpar build anterior
rm -rf dist .vite

# 3. Build com ambiente correto
npm run build:homolog

# 4. Voltar para raiz
cd ../..

# 5. Rebuild do Docker
docker-compose -f docker-compose.homologation.yml build --no-cache websrt
docker-compose -f docker-compose.homologation.yml up -d websrt

# 6. Limpar cache do navegador (Ctrl+Shift+R)
```

Pronto! O erro 405 deve ser resolvido.

---

## 📞 Suporte

Se o problema persistir:
1. Verifique os logs do container: `docker logs websrt-hml`
2. Verifique os logs do backend: `docker logs phpsrt-hml`
3. Abra issue no repositório com os logs completos
