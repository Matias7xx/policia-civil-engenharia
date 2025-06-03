FROM php:8.4-apache

# Instale as dependências e extensões necessárias
RUN apt-get update && apt-get install -y \
    sudo \
    nano \
    cron \
    libpng-dev \
    libjpeg-dev \
    libpq-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    wget \
    dnsutils \
    iputils-ping \
    telnet \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql zip opcache bcmath

# Configurar curl
RUN echo "curl.cainfo=/etc/ssl/certs/ca-certificates.crt" >> /usr/local/etc/php/php.ini

# Instale uma versão mais recente do Node.js (v22)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Habilite o módulo de reescrita do Apache
RUN a2enmod rewrite

# Configurar o Apache para servir o Laravel
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Configurar o tamanho máximo de upload e de post criando o arquivo customizado
RUN echo "upload_max_filesize = 1G\npost_max_size = 1G\nmemory_limit = 1024M\nmax_execution_time = 1200" > /usr/local/etc/php/conf.d/uploads.ini \
    && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Configurações específicas para curl e resolução DNS
RUN echo "default_socket_timeout = 60\nauto_detect_line_endings = On" >> /usr/local/etc/php/conf.d/network.ini

# Configuração do OPcache para produção
RUN echo "opcache.enable=1\nopcache.memory_consumption=256\nopcache.interned_strings_buffer=16\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=0\nopcache.save_comments=1\nopcache.fast_shutdown=1" > /usr/local/etc/php/conf.d/opcache.ini

# Instale o Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Atualizar o Composer para a versão mais recente
RUN composer self-update

# Copie o conteúdo do projeto para o diretório de trabalho no container
COPY . /var/www/html
COPY crontab /etc/cron.d/root
COPY entrypoint.sh /entrypoint.sh

RUN chmod 0644 /etc/cron.d/root
RUN chmod +x /entrypoint.sh

# Defina as permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Defina o diretório de trabalho
WORKDIR /var/www/html

# Comando para iniciar o Apache e o cron
CMD ["/entrypoint.sh"]