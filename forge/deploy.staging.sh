#!/bin/bash

# Staging deployment script

# Exit on error
set -e

echo "🚀 Starting staging deployment..."

# Pull the latest changes from the git repository
echo "📥 Pulling latest changes..."
git pull origin main

# Install/update composer dependencies
echo "📦 Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Create necessary directories
echo "📁 Setting up directories..."
mkdir -p storage/logs
mkdir -p public/logos

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 775 storage
chmod -R 775 public/logos
chown -R forge:forge .

# Ensure database exists
echo "🗄️ Checking database..."
if [ ! -f "db/votes.db" ]; then
    echo "⚠️ Database not found, initializing..."
    php db/init.php
fi

# Clear caches
echo "🧹 Clearing caches..."
php -r "if(function_exists('opcache_reset')) { opcache_reset(); }"

echo "✅ Deployment complete!"
