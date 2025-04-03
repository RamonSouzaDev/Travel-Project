FROM php:8.2-fpm

# Argumentos definidos no docker-compose.yml
ARG user=www-data
ARG uid=1000

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev

# Limpar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Obter Composer mais recente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar diretório do sistema
RUN mkdir -p /var/www

# Copiar o código da aplicação existente
COPY . /var/www

# Definir o diretório de trabalho
WORKDIR /var/www


RUN if [ ! -f ".env" ]; then touch .env; fi

# Definir permissões de pasta
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Expor porta 9000 e iniciar servidor php-fpm
EXPOSE 9000
CMD ["php-fpm"]
