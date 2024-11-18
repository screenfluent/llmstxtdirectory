#!/bin/bash

# Turn on maintenance mode
php artisan down || true

# Pull the latest changes from the git repository
git pull origin staging

# Install/update composer dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Create storage directory if it doesn't exist
mkdir -p storage/logs
mkdir -p public/logos

# Set permissions
chmod -R 775 storage
chmod -R 775 public/logos
chown -R forge:forge .

# Turn off maintenance mode
php artisan up || true
