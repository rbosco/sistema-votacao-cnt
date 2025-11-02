# Sistema de Votação CNT

Sistema de votação de propostas pelos delegados dos sindicatos.

## Estrutura do Projeto

```
FORMULARIO-SRT-CNT/
├── codigo-fonte/
│   ├── cliente/          # Frontend Vue.js 3 + TypeScript
│   └── servico/          # Backend Laravel 11
├── docker-compose.yml
└── README.md
```

## Tecnologias

- **Frontend**: Vue.js 3 + TypeScript + Vite
- **Backend**: Laravel 11 + PHP 8.2
- **Banco de Dados**: SQL Server 2022
- **Containerização**: Docker + Docker Compose

## Instalação

### 1. Clone o repositório

```bash
git clone <url>
cd sistema-votacao-cnt
```

### 2. Adicione a logo

Coloque a logo em: `codigo-fonte/cliente/public/images/logo-cnt.png`

### 3. Inicie os containers

```bash
docker-compose up -d
```

### 4. Configure o backend

```bash
docker-compose exec servico bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### 5. Configure o frontend

```bash
docker-compose exec cliente sh
npm install
```

## Acesso

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api

## Credenciais Padrão

- **CPF**: 12345678901
- **Senha**: admin123

## Estrutura Traduzida

Todo o código está em **português**:
- Models: `Usuario`, `Proposta`, `Voto`
- Controllers: `AutenticacaoController`, `PropostaController`, `VotoController`
- Rotas: `/entrar`, `/votar`, `/propostas`, etc.
- Tabelas: `usuarios`, `propostas`, `votos`

## Licença

MIT
