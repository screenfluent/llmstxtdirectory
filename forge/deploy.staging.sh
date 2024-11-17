#!/bin/bash

# Enable error reporting
set -e

# Configure git
git config --global --add safe.directory /home/forge/staging.llmstxt.directory

# Update repository
cd /home/forge/staging.llmstxt.directory
git fetch origin staging
git reset --hard origin/staging

# Set permissions
chown -R forge:forge .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Create and set permissions for storage directories
mkdir -p public/logos
chown -R forge:www-data public/logos
chmod -R 775 public/logos

# Ensure db directory exists and is accessible
mkdir -p db
chmod 755 db

# Remove existing database to force recreation with new schema
rm -f db/votes.db
touch db/votes.db
chown forge:www-data db/votes.db
chmod 664 db/votes.db

# Initialize database with new schema
echo "Initializing database with new schema..."
php db/init.php

# Set database permissions
chown forge:www-data db/votes.db
chmod 664 db/votes.db

# Restart PHP
sudo -S service php8.2-fpm restart

echo "Deployment completed successfully!"