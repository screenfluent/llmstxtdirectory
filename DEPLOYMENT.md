# Deployment Guide for llms.txt Directory

## Local Development

1. Make sure you're on main branch:
```bash
git checkout main
git pull origin main
```

2. Make changes and test locally:
- Edit files
- Test at http://llmstxtdirectory.test
- Check all features work

3. Commit changes:
```bash
git add .
git commit -m "Descriptive message"
git push origin main
```

## Staging Deployment

1. Changes automatically deploy to https://staging.llmstxt.directory

2. If changes aren't visible:
   - Go to Forge dashboard
   - Click on staging.llmstxt.directory
   - Click "Deploy Now"
   - Watch deployment log
   - If needed, click "Restart PHP"

## Production Deployment

After verifying on staging:
```bash
git checkout production
git merge main
git push origin production
```

Visit https://llmstxt.directory to verify changes.

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

git push origin main --force
```

2. Production Rollback:
```bash
git checkout production
git reset --hard HEAD^
git push origin production --force
```

3. Rollback specific file:
```bash
# Get file from previous commit
git checkout HEAD^ -- path/to/file
git commit -m "Rollback file to previous version"
git push origin main  # for staging
# or
git push origin production  # for production
```

4. Emergency Rollback in Forge:
- Go to Forge dashboard
- Click site name
- Click "Site" tab
- Click "Revert to Previous Deployment"

## Deployment Scripts

### Staging
Location: `forge/deploy.staging.sh`
- Handles file permissions
- Updates code from GitHub
- Manages dependencies
- Restarts PHP

### Production
Location: `forge/deploy.production.sh`
- Similar to staging
- Excludes development dependencies
- Includes additional safety checks

## Common Issues

1. Changes not visible after deployment:
   - Check deployment logs in Forge
   - Restart PHP in Forge dashboard
   - Verify correct branch was pushed

2. Permission issues:
   - Check file ownership
   - Verify directory permissions
   - Review deployment logs

3. Database issues:
   - Check if db/votes.db exists
   - Verify file permissions
   - Review error logs

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
