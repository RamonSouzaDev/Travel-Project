# docker/Dockerfile
FROM php:8.2-fpm

# Argumentos definidos no docker-compose.yml
ARG user=laravel
ARG uid=1000

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Limpar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário do sistema
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar código da aplicação
COPY . /var/www

# Dar permissões para o usuário laravel
RUN chown -R $user:$user /var/www

# Alterar para o usuário laravel
USER $user

# Expor porta 9000
EXPOSE 9000

CMD ["php-fpm"]
