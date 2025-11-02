FROM node:20-alpine

WORKDIR /app

# Copiar arquivos de configuração para cache de dependências
COPY package.json package-lock.json ./

# Instalar dependências
RUN npm ci --no-audit --no-fund

# Copiar código-fonte
COPY . .

# Expor porta do Vite dev server
EXPOSE 8088

# Comando para iniciar servidor de desenvolvimento
CMD ["npm", "run", "dev"]
