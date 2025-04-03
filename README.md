# Microsserviço de Pedidos de Viagem Corporativa

API REST em Laravel para gerenciamento de pedidos de viagem corporativa. Esta API permite criar, consultar, atualizar e cancelar pedidos de viagem, com autenticação JWT e notificações automáticas.

## Tecnologias Utilizadas

- Laravel 10
- MySQL 8.0
- Docker & Docker Compose
- JWT para autenticação
- PHPUnit para testes

## Requisitos

- Docker e Docker Compose
- Git

## Configuração e Instalação

### 1. Clonar o repositório

```bash
git clone https://github.com/seu-usuario/travel-requests-api.git
cd travel-requests-api
```

### 2. Configurar as variáveis de ambiente

Copie o arquivo `.env.example` para `.env`:

```bash
cp .env.example .env
```

Edite o arquivo `.env` com as seguintes configurações mínimas:

```
APP_NAME="Travel Requests API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=travel_api
DB_USERNAME=laravel
DB_PASSWORD=password

MAIL_MAILER=log
QUEUE_CONNECTION=database

JWT_SECRET=
JWT_TTL=60
```

### 3. Iniciar os contêineres Docker

```bash
cd docker
docker-compose up -d
```

### 4. Instalar as dependências

```bash
docker-compose exec app composer install
```

### 5. Gerar chave da aplicação e JWT secret

```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
```

### 6. Executar as migrações

```bash
docker-compose exec app php artisan migrate
```

### 7. (Opcional) Executar o seeder para criar dados de exemplo

```bash
docker-compose exec app php artisan db:seed
```

## Documentação da API

A API está disponível em `http://localhost:8000/api` e possui as seguintes rotas:

### Autenticação

- `POST /api/auth/register` - Registrar um novo usuário
- `POST /api/auth/login` - Obter token JWT
- `POST /api/auth/logout` - Logout (invalidar token)
- `POST /api/auth/refresh` - Renovar token JWT
- `GET /api/auth/me` - Obter informações do usuário autenticado

### Pedidos de Viagem

- `GET /api/travel-requests` - Listar todos os pedidos de viagem do usuário
- `POST /api/travel-requests` - Criar um novo pedido de viagem
- `GET /api/travel-requests/{id}` - Obter detalhes de um pedido de viagem
- `PATCH /api/travel-requests/{id}/status` - Atualizar o status de um pedido (apenas para outros usuários)
- `POST /api/travel-requests/{id}/cancel` - Cancelar um pedido aprovado (apenas pelo solicitante)

### Filtros disponíveis

Para a rota `GET /api/travel-requests`, você pode usar os seguintes parâmetros de query para filtrar os resultados:

- `status` - Filtrar por status (requested, approved, canceled)
- `destination` - Filtrar por destino (busca parcial)
- `from_date` - Filtrar viagens a partir desta data de partida
- `to_date` - Filtrar viagens até esta data de partida
- `created_from` - Filtrar pedidos criados a partir desta data
- `created_to` - Filtrar pedidos criados até esta data
- `sort_by` - Ordenar por coluna (created_at, departure_date, return_date, destination, status)
- `sort_direction` - Direção da ordenação (asc, desc)
- `per_page` - Número de resultados por página

## Executando os Testes

```bash
docker-compose exec app php artisan test
```

## Regras de Negócio

- Um usuário só pode ver, editar e cancelar seus próprios pedidos de viagem
- Um usuário não pode aprovar ou cancelar seus próprios pedidos (apenas outros usuários podem)
- Um pedido aprovado só pode ser cancelado se a data de partida for pelo menos 2 dias no futuro
- Notificações são enviadas automaticamente quando um pedido é aprovado ou cancelado

## Estrutura do Projeto

```
app
├── Console
├── Exceptions
├── Http
│   ├── Controllers        # Controladores da API
│   ├── Middleware         # Middleware de autenticação JWT
│   ├── Requests           # Form Requests para validação
│   └── Resources          # Transformadores de resposta da API
├── Models                 # Modelos Eloquent
├── Notifications          # Notificações
└── Services               # Camada de serviço com lógica de negócio

database
└── migrations             # Migrações do banco de dados

routes
└── api.php                # Rotas da API

tests                      # Testes automatizados
```

## Troubleshooting

### Problemas de permissão

Se encontrar problemas de permissão, execute:

```bash
docker-compose exec app chown -R laravel:laravel /var/www
```

### Limpando o cache

```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
```