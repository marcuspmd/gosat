FROM php:8.4-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    zlib1g-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Configura opções para compilar extensões (freetype/jpeg)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Instala extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_pgsql pgsql intl zip bcmath mbstring gd

# Instala redis extension via pecl
RUN pecl install redis && docker-php-ext-enable redis

# Copia composer do image oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
