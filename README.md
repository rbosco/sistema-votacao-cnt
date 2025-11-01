# Sistema de Votação CNT

Sistema de votação de propostas realizado pelos delegados dos sindicatos, desenvolvido com Laravel, Vue.js 3 e SQL Server.

## 📋 Sobre o Projeto

O Sistema de Votação CNT é uma aplicação web completa para gerenciamento de votações de propostas sindicais. O sistema possui duas partes principais:

1. **Página de Votação Pública**: Onde os delegados dos sindicatos registram seus votos
2. **Painel Administrativo**: Para gerenciamento de usuários, propostas e visualização de resultados

### Características Principais

- ✅ Autenticação segura com CPF e senha
- ✅ Cadastro e gerenciamento de propostas
- ✅ Votação Sim/Não com confirmação
- ✅ Apenas uma proposta ativa por vez
- ✅ Voto único e imutável
- ✅ Bloqueio automático após desativação da proposta
- ✅ Visualização de resultados em tempo real
- ✅ URLs de resultados com IDs criptografados
- ✅ Design System do Governo Federal
- ✅ Interface responsiva e moderna

## 🛠️ Tecnologias Utilizadas

- **Backend**: Laravel 11 (PHP 8.2)
- **Frontend**: Vue.js 3 + Vite
- **Banco de Dados**: SQL Server 2022
- **Containerização**: Docker + Docker Compose
- **Autenticação**: Laravel Sanctum
- **State Management**: Pinia
- **Roteamento**: Vue Router
- **CSS**: Design System Gov.br

## 📦 Pré-requisitos

- Docker (versão 20.10 ou superior)
- Docker Compose (versão 2.0 ou superior)
- Git

## 🚀 Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/seu-usuario/sistema-votacao-cnt.git
cd sistema-votacao-cnt
```

### 2. Configure o arquivo .env

```bash
cp .env.example .env
```

Edite o arquivo `.env` e configure as variáveis de ambiente conforme necessário. As configurações padrão já estão otimizadas para Docker.

### 3. Adicione a logo do projeto

Coloque a logo da CNT em `public/images/logo-cnt.png`

### 4. Inicie os containers Docker

```bash
docker-compose up -d
```

Este comando irá:
- Construir a imagem do PHP com todas as dependências necessárias
- Iniciar o SQL Server
- Iniciar o Nginx

### 5. Acesse o container da aplicação

```bash
docker-compose exec app bash
```

### 6. Instale as dependências do PHP

```bash
composer install
```

### 7. Gere a chave da aplicação

```bash
php artisan key:generate
```

### 8. Execute as migrations

```bash
php artisan migrate
```

### 9. Execute os seeders (opcional)

```bash
php artisan db:seed
```

Isso criará:
- 1 usuário administrador (CPF: 12345678901, Senha: admin123)
- 2 usuários de teste
- 4 propostas de exemplo

### 10. Instale as dependências do Node.js e compile os assets

```bash
npm install
npm run build
```

Para desenvolvimento com hot-reload:

```bash
npm run dev
```

### 11. Acesse a aplicação

- **Página de Votação**: http://localhost:8000
- **Painel Administrativo**: http://localhost:8000/login

## 👥 Credenciais de Acesso (após rodar seeders)

### Administrador
- **CPF**: 12345678901
- **Senha**: admin123

## 📖 Estrutura do Projeto

```
sistema-votacao-cnt/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php
│   │       ├── ProposalController.php
│   │       ├── UserController.php
│   │       └── VoteController.php
│   └── Models/
│       ├── User.php
│       ├── Proposal.php
│       └── Vote.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── css/
│   │   └── app.css (Design System Gov.br)
│   ├── js/
│   │   ├── components/
│   │   │   └── AdminHeader.vue
│   │   ├── views/
│   │   │   ├── admin/
│   │   │   │   ├── Dashboard.vue
│   │   │   │   ├── Proposals.vue
│   │   │   │   ├── Users.vue
│   │   │   │   └── Votes.vue
│   │   │   ├── Login.vue
│   │   │   ├── Results.vue
│   │   │   └── Vote.vue
│   │   ├── stores/
│   │   │   └── auth.js
│   │   ├── router/
│   │   │   └── index.js
│   │   └── app.js
│   └── views/
│       └── app.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── docker/
│   └── nginx/
│       └── default.conf
├── docker-compose.yml
├── Dockerfile
└── README.md
```

## 🎯 Funcionalidades

### Página de Votação Pública

- Exibição da proposta ativa
- Formulário de votação com CPF, Nome e Sindicato
- Votação Sim/Não com modal de confirmação
- Validação de voto único por CPF
- Mensagem de "Votação encerrada" quando a proposta é desativada
- Atualização automática da proposta ativa

### Painel Administrativo

#### Dashboard
- Estatísticas gerais (total de propostas, votos e usuários)
- Acesso rápido às principais funcionalidades

#### Gerenciamento de Usuários
- Listagem de usuários
- Criação de novos usuários
- Edição de usuários existentes
- Exclusão de usuários
- Definição de permissões de administrador

#### Gerenciamento de Propostas
- Listagem de propostas com contagem de votos
- Criação de propostas (número opcional, nome obrigatório)
- Edição de propostas
- Ativação/Desativação de propostas
- Exclusão de propostas
- Visualização de resultados
- Apenas uma proposta pode estar ativa por vez

#### Visualização de Votos
- Listagem de todos os votos
- Filtro por proposta
- Estatísticas de votação (total, sim, não, percentuais)
- Atualização em tempo real

#### Página de Resultados
- URL com ID criptografado
- Gráfico visual de distribuição de votos
- Estatísticas detalhadas
- Listagem completa de votos
- Acesso público (sem necessidade de login)

## 🔒 Regras de Negócio

1. **Proposta Ativa**: Apenas uma proposta pode estar ativa por vez
2. **Voto Único**: Cada CPF pode votar apenas uma vez por proposta
3. **Voto Imutável**: Após registrado, o voto não pode ser alterado
4. **Bloqueio Automático**: Quando uma proposta é desativada, não é possível votar nela
5. **Autenticação Admin**: Apenas usuários autenticados podem acessar o painel administrativo
6. **Validação de CPF**: CPF deve conter exatamente 11 dígitos

## 🎨 Design System

O sistema utiliza o Design System do Governo Federal, seguindo as diretrizes oficiais:

- **Cores**: Paleta oficial do Gov.br (azul #1351B4, amarelo #FFCD07, etc.)
- **Tipografia**: Rawline
- **Componentes**: Botões, formulários, cards, tabelas, modais e badges padronizados
- **Acessibilidade**: Seguindo as melhores práticas de acessibilidade web

## 🐳 Comandos Docker Úteis

```bash
# Iniciar os containers
docker-compose up -d

# Parar os containers
docker-compose down

# Ver logs
docker-compose logs -f

# Acessar o container da aplicação
docker-compose exec app bash

# Acessar o SQL Server
docker-compose exec sqlserver /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "SistemaVotacao@2024" -C
```

## 🔧 Comandos Laravel Úteis

```bash
# Criar migration
php artisan make:migration create_table_name

# Executar migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Executar seeders
php artisan db:seed

# Criar controller
php artisan make:controller ControllerName

# Criar model
php artisan make:model ModelName

# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## 🔧 Comandos Node.js Úteis

```bash
# Instalar dependências
npm install

# Desenvolvimento com hot-reload
npm run dev

# Build para produção
npm run build

# Pré-visualização do build
npm run preview
```

## 🧪 Testes

Para executar os testes:

```bash
php artisan test
```

## 📝 API Endpoints

### Públicos
- `POST /api/login` - Login de usuário
- `POST /api/vote` - Registrar voto
- `GET /api/proposals/active` - Obter proposta ativa
- `GET /api/proposals/{encryptedId}/results` - Obter resultados

### Protegidos (requerem autenticação)
- `POST /api/logout` - Logout
- `GET /api/me` - Dados do usuário autenticado
- `GET /api/users` - Listar usuários
- `POST /api/users` - Criar usuário
- `PUT /api/users/{id}` - Atualizar usuário
- `DELETE /api/users/{id}` - Excluir usuário
- `GET /api/proposals` - Listar propostas
- `POST /api/proposals` - Criar proposta
- `PUT /api/proposals/{id}` - Atualizar proposta
- `DELETE /api/proposals/{id}` - Excluir proposta
- `POST /api/proposals/{id}/activate` - Ativar proposta
- `POST /api/proposals/{id}/deactivate` - Desativar proposta
- `GET /api/votes` - Listar votos
- `GET /api/proposals/{id}/votes` - Votos de uma proposta

## 🤝 Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT.

## 👨‍💻 Desenvolvedor

Desenvolvido com 💙 para a CNT - Confederação Nacional dos Trabalhadores

## 📞 Suporte

Para suporte, entre em contato através do e-mail: suporte@cnt.org.br

---

**Nota**: Lembre-se de adicionar a logo da CNT em `public/images/logo-cnt.png` antes de iniciar o sistema.
