#!/bin/bash

# Deployment script for SPMURI Backend

echo "Starting deployment..."

# Check if production environment
if [ "$1" = "production" ]; then
    echo "Setting up production environment..."
    cp .env.production.example .env
    
    # Update production values
    sed -i 's/APP_ENV=local/APP_ENV=production/' .env
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
    sed -i 's/APP_URL=http:\/\/localhost:8000/APP_URL=https:\/\/yourdomain.com/' .env
    
    echo "Production .env created"
else
    echo "Setting up local environment..."
    cp .env.example .env
fi

# Install dependencies
composer install --optimize-autoloader --no-dev

# Generate key if not exists
php artisan key:generate --force

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache for production
if [ "$1" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "Deployment completed!"