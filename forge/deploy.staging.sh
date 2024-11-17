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

# Create and initialize database if it doesn't exist
if [ ! -f "db/votes.db" ] || [ ! -s "db/votes.db" ]; then
    echo "Initializing database..."
    rm -f db/votes.db
    touch db/votes.db
    chown forge:www-data db/votes.db
    chmod 664 db/votes.db
    sudo -u forge php db/init.php
else
    echo "Database exists, checking schema..."
    # Apply schema updates
    sudo -u forge php -r "
        require_once 'db/database.php';
        \$db = new Database();
        \$schema = file_get_contents('db/schema.sql');
        \$db->db->exec(\$schema);
    "
fi

# Set database permissions
chown forge:www-data db/votes.db
chmod 664 db/votes.db

# Restart PHP
sudo -S service php8.2-fpm restart

echo "Deployment completed successfully!"