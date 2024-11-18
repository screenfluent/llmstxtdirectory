#!/bin/bash

# Enable error reporting
set -e

# Configure git
git config --global --add safe.directory /home/stagingllmstxtdirectory/staging.llmstxt.directory

# Update repository
cd /home/stagingllmstxtdirectory/staging.llmstxt.directory

# Initial git setup
git fetch --all
git checkout -f staging || git checkout -f main
git pull origin staging || git pull origin main

# Set permissions
chown -R stagingllmstxtdirectory:stagingllmstxtdirectory .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Create and set permissions for storage directories
mkdir -p public/logos
chown -R stagingllmstxtdirectory:www-data public/logos
chmod -R 775 public/logos

# Ensure db directory exists and is accessible
mkdir -p db
chmod 755 db

# Remove existing database to force recreation with new schema
rm -f db/votes.db
touch db/votes.db
chown stagingllmstxtdirectory:www-data db/votes.db
chmod 664 db/votes.db

# Initialize database with new schema
echo "Initializing database with new schema..."
php db/init.php

# Set database permissions
chown stagingllmstxtdirectory:www-data db/votes.db
chmod 664 db/votes.db

# Restart PHP
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

echo "Deployment completed successfully!"