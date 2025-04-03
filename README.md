<h1 align="center">Olá 👋, Eu sou Ramon Mendes</h1>
<h3 align="center">Um desenvolvedor back-end apaixonado por tecnologia</h3>

- 🔭 Atualmente estou trabalhando no projeto [Travel-Project](https://github.com/RamonSouzaDev/Travel-Project)
- 🌱 Atualmente estou aprendendo **Laravel, MySQL e boas práticas de microsserviços**
- 📫 Como chegar até mim: **dwmom@hotmail.com**

<h3 align="left">Vamos fazer networking:</h3>
<p align="left">
  <a href="https://www.linkedin.com/in/ramon-mendes-b44456164/" target="blank">
    <img align="center" src="https://raw.githubusercontent.com/rahuldkjain/github-profile-readme-generator/master/src/images/icons/Social/linked-in-alt.svg" alt="ramon-linkedin" height="30" width="40" />
  </a>
</p>

<h3 align="left">Linguagens e ferramentas:</h3>
<p align="left">
  <a href="https://laravel.com/" target="_blank" rel="noreferrer">
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/laravel/laravel-plain-wordmark.svg" alt="laravel" width="40" height="40"/>
  </a>
  <a href="https://www.mysql.com/" target="_blank" rel="noreferrer">
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/mysql/mysql-original-wordmark.svg" alt="mysql" width="40" height="40"/>
  </a>
  <a href="https://www.php.net" target="_blank" rel="noreferrer">
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/php/php-original.svg" alt="php" width="40" height="40"/>
  </a>
  <a href="https://www.docker.com/" target="_blank" rel="noreferrer">
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/docker/docker-original-wordmark.svg" alt="docker" width="40" height="40"/>
  </a>
  <a href="https://www.linux.org/" target="_blank" rel="noreferrer">
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/linux/linux-original.svg" alt="linux" width="40" height="40"/>
  </a>
</p>

---

## 🚀 Como instalar e rodar o projeto Laravel

| Etapa | Comando |
|-------|---------|
| **1. Clonar o repositório** | `git clone https://github.com/RamonSouzaDev/Travel-Project.git` |
| **2. Acessar a pasta do projeto** | `cd Travel-Project` |
| **3. Instalar dependências** | `composer install` |
| **4. Copiar o arquivo `.env`** | `cp .env.example .env` |
| **5. Gerar chave da aplicação** | `php artisan key:generate` |
| **6. Gerar chave JWT** | `php artisan jwt:secret` |
| **7. Rodar as migrations** | `php artisan migrate` |
| **8. (Opcional) Corrigir permissões** | `chmod -R 775 storage bootstrap/cache` |
| **9. Rodar o servidor local** | `php artisan serve`<br>📍 Disponível em: `http://localhost:8000` |

---

⚙️ Arquivo .env - Configurações de ambiente
Para garantir que tudo funcione corretamente, seu arquivo .env deve conter:

APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:YFjhby94oBU9976uowWNabYxYVqJTr6bUYI2DY/CqsM=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=viagens_corporativas
DB_USERNAME=viagens_user
DB_PASSWORD=viagens_password


SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=file
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
JWT_SECRET=t6nDcWHEfnR56QlFXQZGOgc55E6l8cv0UK4JRwi7p5IsyoK3YZ66SUU7STLhPboD
JWT_TTL=60

JWT_ALGO=HS256

--- 

## ✅ Testes Automatizados

| Etapa | Comando |
|-------|---------|
| **Rodar todos os testes** | `php artisan test` |

---

## 🐳 Executando com Docker

| Etapa | Comando |
|-------|---------|
| **1. Subir containers** | `docker-compose up -d --build` |
| **2. Acessar o container** | `docker exec -it travel_app bash` |
| **3. Rodar as migrations** | `php artisan migrate` |

---

## 📌 Rotas da API

> ⚠️ As rotas protegidas exigem **Bearer Token** do usuário logado.

### 🔐 Autenticação

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/register` | Cadastro de novo usuário |
| `POST` | `/api/login` | Login de usuário |
| `POST` | `/api/logout` | Logout do usuário |

---

### ✈️ Pedidos de Viagem

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/travel-requests` | Criar novo pedido de viagem |
| `GET` | `/api/travel-requests` | Listar pedidos do usuário logado |
| `GET` | `/api/travel-requests/{id}` | Visualizar um pedido específico |
| `POST` | `/api/travel-requests/{id}/cancel` | Cancelar pedido do próprio usuário |
| `PATCH` | `/api/travel-requests/{id}/status` | Atualizar status do pedido (admin) |

---

## 🗓️ Última atualização

Projeto atualizado em **03/04/2025**

---



