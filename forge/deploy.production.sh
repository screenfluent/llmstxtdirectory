#!/bin/bash

# Enable error reporting
set -e

# Configure git
git config --global --add safe.directory /home/llmstxtdirectory/llmstxt.directory

# Update repository
cd /home/llmstxtdirectory/llmstxt.directory
git fetch origin $FORGE_SITE_BRANCH
git reset --hard origin/$FORGE_SITE_BRANCH

# Set permissions
chown -R llmstxtdirectory:llmstxtdirectory .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Create and set permissions for storage directories
mkdir -p public/logos
chown -R llmstxtdirectory:llmstxtdirectory public/logos
chmod -R 775 public/logos

# Create and initialize database if it doesn't exist
if [ ! -f "db/votes.db" ] || [ ! -s "db/votes.db" ]; then
    echo "Initializing database..."
    rm -f db/votes.db
    touch db/votes.db
    chown llmstxtdirectory:llmstxtdirectory db/votes.db
    chmod 664 db/votes.db
    php db/init.php
else
    echo "Database exists, checking schema..."
    # Apply schema updates
    php -r "
        require_once 'db/database.php';
        \$db = new Database();
        \$schema = file_get_contents('db/schema.sql');
        \$db->db->exec(\$schema);
    "
fi

# Set database permissions
chown llmstxtdirectory:llmstxtdirectory db/votes.db
chmod 664 db/votes.db

# Restart PHP
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

echo "Deployment completed successfully!"