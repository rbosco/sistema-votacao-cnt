# ========== Build ==========
FROM node:20-alpine AS build
WORKDIR /app

# Copia só manifests para aproveitar cache
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Copia o restante do projeto
COPY . .

# Build de produção
RUN npm run build

# ========== Runtime ==========
FROM nginx:alpine

# Copia artefatos estáticos
COPY --from=build /app/dist /usr/share/nginx/html

# SPA history mode
RUN rm /etc/nginx/conf.d/default.conf
COPY infra/nginx/web.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
