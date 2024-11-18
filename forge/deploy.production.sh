#!/bin/bash

# Enable error reporting
set -e

# Configure git
git config --global --add safe.directory /home/llmstxtdirectory/llmstxt.directory

# Update repository
cd /home/llmstxtdirectory/llmstxt.directory
git fetch origin production
git reset --hard origin/production

# Set permissions
chown -R llmstxtdirectory:llmstxtdirectory .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Create and set permissions for storage directories
mkdir -p public/logos
chown -R llmstxtdirectory:www-data public/logos
chmod -R 775 public/logos

# Ensure db directory exists and is accessible
mkdir -p db
chmod 755 db

# Database handling
if [ ! -f "db/votes.db" ]; then
    # Only create new database if it doesn't exist
    echo "Creating new database..."
    touch db/votes.db
    chown llmstxtdirectory:www-data db/votes.db
    chmod 664 db/votes.db
    
    # Initialize with schema and sample data
    echo "Initializing database with schema and sample data..."
    php db/init.php
else
    # Database exists, just update schema
    echo "Updating existing database schema..."
    php -r "
        require_once 'db/database.php';
        \$db = new Database();
        \$schema = file_get_contents('db/schema.sql');
        \$db->db->exec('BEGIN TRANSACTION;');
        try {
            \$db->db->exec(\$schema);
            \$db->db->exec('COMMIT;');
            echo \"Schema updated successfully.\n\";
        } catch (Exception \$e) {
            \$db->db->exec('ROLLBACK;');
            echo \"Error updating schema: \" . \$e->getMessage() . \"\n\";
            exit(1);
        }
    "
fi

# Set database permissions
chown llmstxtdirectory:www-data db/votes.db
chmod 664 db/votes.db

# Restart PHP
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

echo "Deployment completed successfully!"