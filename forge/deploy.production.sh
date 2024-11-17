cd /home/llmstxtdirectory/llmstxt.directory

# Exit on error
set -e

echo "🚀 Starting production deployment..."

# Pull the latest changes from the git repository
echo "📥 Pulling latest changes..."
git pull origin $FORGE_SITE_BRANCH

# Install/update composer dependencies
echo "📦 Installing dependencies..."
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Create necessary directories
echo "📁 Setting up directories..."
mkdir -p storage/logs
mkdir -p public/logos

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 775 storage
chmod -R 775 public/logos
chmod -R 775 db
chmod 775 .

# Ensure database exists
echo "🗄️ Checking database..."
if [ ! -f "db/votes.db" ]; then
    echo "⚠️ Database not found, initializing..."
    php db/init.php
    chmod 664 db/votes.db
fi

# Clear caches
echo "🧹 Clearing caches..."
php -r "if(function_exists('opcache_reset')) { opcache_reset(); }"

echo "✅ Deployment complete!"