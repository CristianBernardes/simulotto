# Use a imagem baseada em Alpine com PHP 8.4
FROM php:8.4-fpm-alpine

# Arguments
ARG user=simulotto
ARG uid=1000

# Atualize os pacotes do sistema operacional e instale os pacotes necessários
RUN apk update && \
    apk upgrade && \
    apk add --no-cache \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    g++ \
    lame \
    netcat-openbsd \
    postgresql-dev && \
    rm -rf /var/cache/apk/*

# Instale as extensões PHP necessárias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd && \
    docker-php-ext-install intl zip pdo_mysql pdo_pgsql pgsql

# Instale o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Cria o usuário da aplicação
RUN adduser --uid $uid --home /home/$user -s /bin/sh -D $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copie o código fonte do Laravel para o diretório de trabalho
COPY . .

# Define o usuário como root para fazer instalações e manipulações de arquivos
USER root

# Defina o timezone do sistema como America/Sao_Paulo
ENV TZ=America/Sao_Paulo
RUN apk add --no-cache tzdata && \
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && \
    echo $TZ > /etc/timezone && \
    apk del tzdata

# Adiciona as configurações ao arquivo custom.ini
RUN echo "memory_limit = 2048M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 80M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 80M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone = America/Sao_Paulo" >> /usr/local/etc/php/conf.d/custom.ini

# Defina as permissões para o diretório de trabalho
RUN chown -R www-data:www-data /var/www/html

# Defina o usuário com o qual o contêiner será executado
USER $user

# Defina o ponto de entrada para o contêiner
CMD ["php-fpm"]
