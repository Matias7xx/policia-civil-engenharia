#!/bin/bash

# Saia imediatamente em caso de erro
set -e

# Definir arquivo marcador para migrações no volume persistente
MIGRATION_MARKER="/var/www/html/storage/app/.migrations_run"
BUILD_MARKER="/var/www/html/storage/app/.build_completed"

# Forçar rebuild e migrações se variável estiver definida
if [ "$FORCE_REBUILD" = "true" ]; then
    echo "Removendo marcadores para forçar rebuild e migração..."
    rm -f "$BUILD_MARKER" "$MIGRATION_MARKER"
fi

# Instala as dependências do Composer, se o vendor não existir
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Instala as dependências do npm e executa o build apenas se o marcador não existir
if [ "$APP_ENV" = "production" ]; then
    if [ ! -f "$BUILD_MARKER" ]; then
        echo "Instalando dependências do npm se necessário..."
        if [ ! -d "/var/www/html/node_modules" ]; then
            npm ci --no-audit --no-fund
        fi

        echo "Executando build do npm (produção)..."
        npm run build

        mkdir -p /var/www/html/storage/app
        touch "$BUILD_MARKER"
        echo "Build concluído e marcador criado"
    else
        echo "Build já foi executado anteriormente, pulando..."
    fi
else
    echo "Instalando dependências do npm se necessário..."
    if [ ! -d "/var/www/html/node_modules" ]; then
        npm ci --no-audit --no-fund
    fi
    echo "Iniciando Vite HMR (dev)..."
    npm run dev -- --host 0.0.0.0 &
fi

# Garante as permissões corretas
echo "Configurando permissões..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Gera a chave da aplicação, caso não exista
#if [ ! -f "/var/www/html/.env" ] || ! grep -q "APP_KEY=" /var/www/html/.env; then
    echo "Gerando chave da aplicação..."
    php artisan key:generate --force
#fi

# Executa as migrações e seed apenas se o marcador não existir
if [ ! -f "$MIGRATION_MARKER" ]; then
    echo "Executando migrações e seeds..."
    php artisan migrate --seed --force

    mkdir -p /var/www/html/storage/app
    touch "$MIGRATION_MARKER"
    echo "Migrações concluídas e marcador criado"
else
    echo "Migrações já foram executadas anteriormente, pulando..."
fi

# Cria o link simbólico para o storage, se ainda não existir
if [ ! -L "/var/www/html/public/storage" ]; then
    echo "Criando link simbólico para storage..."
    php artisan storage:link --force
fi

# Determinar ambiente e otimizar se estiver em produção
if [ "$APP_ENV" = "production" ]; then
    echo "Ambiente de produção detectado, otimizando..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "Limpando caches para desenvolvimento..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

# Iniciar o processamento das filas em background
echo "Iniciando filas..."
php artisan queue:work --daemon &

# Inicie o cron em background
echo "Iniciando cron..."
service cron start

# Inicie o Apache no foreground
echo "Iniciando Apache..."
exec apache2-foreground