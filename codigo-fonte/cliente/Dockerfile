FROM node:20-alpine

WORKDIR /app

# Copiar arquivos de configuração
COPY package*.json ./

# Copiar entrypoint
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copiar código-fonte
COPY . .

EXPOSE 3000

ENTRYPOINT ["entrypoint.sh"]
