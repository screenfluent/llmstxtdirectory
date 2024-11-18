# Deployment Guide for llms.txt Directory

## Local Development

1. Make sure you're on staging branch:
```bash
git checkout staging
git pull origin staging
```

2. Make changes and test locally:
- Edit files
- Test at http://llmstxtdirectory.test
- Check all features work

3. Commit changes:
```bash
git add .
git commit -m "Descriptive message"
git push origin staging
```

## Server Configuration

### Nginx Configuration Files

The project uses two nginx configuration files:
- `nginx.conf`: Production configuration (llmstxt.directory)
- `nginx.staging.conf`: Staging configuration (staging.llmstxt.directory)

Key differences between environments:
- Different SSL certificate paths
- Environment-specific PHP-FPM sockets
- Staging-specific development timeouts

To update nginx configuration:
1. Make changes locally in the appropriate .conf file
2. Test configuration syntax
3. Update in Forge:
   - Go to server
   - Click "Edit Files"
   - Select nginx configuration
   - Paste new configuration
   - Save and verify nginx restarts successfully

## Staging Deployment

1. Changes automatically deploy to https://staging.llmstxt.directory via Forge's Quick Deploy

2. If changes aren't visible:
   - Go to Forge dashboard
   - Click on staging.llmstxt.directory
   - Click "Deploy Now"
   - Watch deployment log
   - If needed, click "Restart PHP"

3. Verify deployment:
   - Check site loads correctly
   - Test all modified features
   - Verify error logs: `/var/log/nginx/staging.llmstxt.directory-error.log`

## Production Deployment

After verifying on staging:
```bash
git checkout production
git merge staging
git push origin production
```

Visit https://llmstxt.directory to verify changes.

## Environment Management

### PHP Configuration
- Production: php8.3-fpm-llmstxtdirectory.sock
- Staging: php8.3-fpm-stagingllmstxtdirectory.sock

### Database Management
- Production: Preserves data between deployments
- Staging: Fresh database on each deployment

### SSL Certificates
Managed by Forge:
- Production: /etc/nginx/ssl/llmstxt.directory/[cert_id]/
- Staging: /etc/nginx/ssl/staging.llmstxt.directory/[cert_id]/

## Git Commands Reference

### View Commits

1. View recent commits:
```bash
# Show last 5 commits
git log -5

# Show commits with files changed
git log --stat

# Show commits in one line
git log --oneline

# Show commits with dates
git log --pretty=format:"%h - %an, %ar : %s"
```

2. Find specific commit:
```bash
# Search commit messages
git log --grep="search term"

# Search for changes in code
git log -S "search term"

# Show commits for specific file
git log -- path/to/file

# Show commits with patches
git log -p path/to/file
```

### Rollback Procedures

1. Staging Rollback:
```bash
# View last commits
git log -5

# Revert last commit
git revert HEAD
# or
git reset --hard HEAD^

git push origin staging --force
```

2. Production Rollback:
```bash
git checkout production
git reset --hard HEAD^
git push origin production --force
```

## Troubleshooting

### Common Issues

1. Nginx Configuration:
- Check syntax: `nginx -t`
- Review error logs: `tail -f /var/log/nginx/*.log`
- Verify SSL certificate paths
- Check PHP-FPM socket paths

2. PHP Issues:
- Check PHP-FPM status
- Review PHP error logs
- Verify correct PHP version (8.3)
- Check file permissions

3. Deployment Issues:
- Verify Forge deployment script execution
- Check Git branch status
- Review deployment logs in Forge
- Verify database migrations

### Emergency Contacts

- Server Issues: Laravel Forge Support
- Domain/SSL: Forge/Domain Registrar
- Application: Development Team

## Useful Commands

```bash
# Check current branch
git branch --show-current

# Check remote configuration
git remote -v

# View file changes
git diff path/to/file

# View staged changes
git diff --staged

# Undo local changes
git checkout -- path/to/file

# View branch history
git show-branch

# Check file status
git status -s
```

## Contact

For deployment issues:
- Create GitHub issue
- Contact system administrator
- Check Forge status page
