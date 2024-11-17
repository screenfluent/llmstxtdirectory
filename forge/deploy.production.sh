cd /home/llmstxtdirectory/llmstxt.directory
set -e
echo "🚀 Starting production deployment..."
chown -R llmstxtdirectory:llmstxtdirectory .
chmod -R 775 .
git config --global --add safe.directory /home/llmstxtdirectory/llmstxt.directory
git fetch origin $FORGE_SITE_BRANCH
git reset --hard origin/$FORGE_SITE_BRANCH
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev
mkdir -p storage/logs
mkdir -p public/logos
chmod -R 775 storage
chmod -R 775 public/logos
chmod -R 775 db
chmod 775 .
if [ ! -f "db/votes.db" ]; then
    php db/init.php
    chmod 664 db/votes.db
fi
echo "🔄 Restarting PHP..."
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
echo "✅ Deployment complete!"