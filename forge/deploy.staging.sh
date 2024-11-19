#!/bin/bash

# Enable error reporting
set -e

# Change to the correct directory first
cd /home/stagingllmstxtdirectory/staging.llmstxt.directory

# Clear any cached git configs
rm -f ~/.gitconfig
rm -rf .git
rm -rf *

# Reinitialize git
git init
git config --global --add safe.directory /home/stagingllmstxtdirectory/staging.llmstxt.directory
git remote add origin https://github.com/screenfluent/llmstxtdirectory.git

# Fetch and reset to staging branch
git fetch origin staging
git checkout -f -b staging --track origin/staging

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

# Database handling
echo "Recreating staging database..."
rm -f db/directory.db
touch db/directory.db
chown stagingllmstxtdirectory:www-data db/directory.db
chmod 664 db/directory.db

# Initialize with fresh schema and sample data
echo "Initializing database with fresh schema and sample data..."
php -r "
    require_once 'db/database.php';
    \$db = new Database();
    
    try {
        // Initialize schema
        \$schema = file_get_contents('db/schema.sql');
        if (\$schema === false) {
            throw new Exception('Could not read schema.sql');
        }
        
        // Execute schema
        \$result = \$db->db->exec(\$schema);
        if (\$result === false) {
            throw new Exception('Failed to execute schema: ' . \$db->db->lastErrorMsg());
        }
        
        // Initialize sample data
        require_once 'db/init.php';
        
        echo \"Database initialized successfully.\n\";
    } catch (Exception \$e) {
        echo \"Error initializing database: \" . \$e->getMessage() . \"\n\";
        exit(1);
    }
"

# Set database permissions
chown stagingllmstxtdirectory:www-data db/directory.db
chmod 664 db/directory.db

# Restart PHP
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

echo "Deployment completed successfully!"