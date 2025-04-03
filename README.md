# Microsserviço de Gerenciamento de Viagens Corporativas

Este é um microsserviço desenvolvido em Laravel para gerenciar pedidos de viagem corporativa, fornecendo uma API REST para operações de criação, atualização, consulta e listagem de pedidos.

## Tecnologias Utilizadas

- Laravel 12
- MySQL 8.0
- Redis
- Docker
- JWT para autenticação
- PHPUnit para testes automatizados

## Funcionalidades

- Criação de pedidos de viagem (destino, data de ida, data de volta)
- Atualização de status de pedidos (aprovado, cancelado)
- Consulta detalhada de pedidos
- Listagem de todos os pedidos com filtros (status, período, destino)
- Cancelamento de pedidos
- Notificações por e-mail para aprovações e cancelamentos
- Autenticação JWT
- Controle de acesso baseado em perfis (usuário e admin)

## Requisitos

- Docker
- Docker Compose
- Git

## Instalação e Configuração

### Passo 1: Clonar o repositório

```bash
git clone https://github.com/seu-usuario/viagens-corporativas.git
cd viagens-corporativas
```

### Passo 2: Configurar o ambiente

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações desejadas (banco de dados, etc.)

### Passo 3: Iniciar os containers Docker

```bash
docker-compose up -d
```

### Passo 4: Instalar dependências e configurar o projeto

```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed (opcional para dados de teste)
```

## Estrutura do Banco de Dados

- `users`: Armazena informações dos usuários
- `travel_requests`: Armazena os pedidos de viagem
- `notifications`: Armazena as notificações enviadas aos usuários

## Endpoints da API

### Autenticação
- `POST /api/register`: Registrar um novo usuário
- `POST /api/login`: Autenticar usuário e obter token JWT
- `POST /api/logout`: Deslogar (invalidar token)
- `POST /api/refresh`: Atualizar token JWT

### Pedidos de Viagem
- `GET /api/travel-requests`: Listar todos os pedidos de viagem do usuário atual (ou todos para admin)
- `POST /api/travel-requests`: Criar um novo pedido de viagem
- `GET /api/travel-requests/{id}`: Consultar um pedido de viagem específico
- `PATCH /api/travel-requests/{id}/status`: Atualizar o status de um pedido (apenas admin)
- `POST /api/travel-requests/{id}/cancel`: Cancelar um pedido de viagem (pelo solicitante)

## Filtros Disponíveis

Ao listar pedidos (`GET /api/travel-requests`), os seguintes filtros podem ser aplicados:

- `status`: Filtrar por status (solicitado, aprovado, cancelado)
- `destination`: Filtrar por destino
- `start_date` e `end_date`: Filtrar por período

Exemplo: `/api/travel-requests?status=aprovado&destination=São%20Paulo&start_date=2025-04-01&end_date=2025-04-30`

## Executando Testes

Para rodar os testes automatizados:

```bash
docker-compose exec app php artisan test
```

## Pipeline CI/CD

O projeto inclui um pipeline de CI/CD configurado com GitHub Actions que:
1. Executa testes automatizados
2. Verifica o estilo de código
3. Realiza deploy automático (quando configurado)

## Recursos Adicionais

- A API implementa validação de dados no backend
- Tratamento de erros apropriado
- Documentação detalhada
- Testes automatizados para todas as funcionalidades principais

## Contribuição

Para contribuir com o projeto:
1. Faça um fork do repositório
2. Crie uma branch para sua feature (`git checkout -b feature/nome-da-feature`)
3. Faça commit das suas alterações (`git commit -am 'Adiciona nova feature'`)
4. Envie para o GitHub (`git push origin feature/nome-da-feature`)
5. Crie um Pull Request
