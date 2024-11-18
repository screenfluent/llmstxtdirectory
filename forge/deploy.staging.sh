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

# Always recreate database in staging for clean testing
echo "Recreating staging database..."
rm -f db/votes.db
touch db/votes.db
chown stagingllmstxtdirectory:www-data db/votes.db
chmod 664 db/votes.db

# Initialize with fresh schema and sample data
echo "Initializing database with fresh schema and sample data..."
php -r "
    require_once 'db/database.php';
    \$db = new Database();
    \$schema = file_get_contents('db/schema.sql');
    \$db->db->exec('BEGIN TRANSACTION;');
    try {
        \$db->db->exec(\$schema);
        require_once 'db/init.php';
        \$db->db->exec('COMMIT;');
        echo \"Database initialized successfully.\n\";
    } catch (Exception \$e) {
        \$db->db->exec('ROLLBACK;');
        echo \"Error initializing database: \" . \$e->getMessage() . \"\n\";
        exit(1);
    }
"

# Set database permissions
chown stagingllmstxtdirectory:www-data db/votes.db
chmod 664 db/votes.db

# Restart PHP
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

echo "Deployment completed successfully!"