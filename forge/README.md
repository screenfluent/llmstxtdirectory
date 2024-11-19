# Forge Deployment Templates

This directory contains deployment script templates for Laravel Forge. These files are **not** executed directly from the repository.

## Usage

The deployment scripts in this directory serve as templates that should be manually copied into the Laravel Forge deployment script section in the admin panel:

- `deploy.production.sh` → Production environment deployment script
- `deploy.staging.sh` → Staging environment deployment script

## Deployment Process

1. The actual deployment is handled by Laravel Forge using Quick Deploy
2. When changes are pushed to the respective branches (production/staging), Forge automatically triggers the deployment
3. The deployment scripts in the Forge admin panel execute the necessary steps:
   - Pulling latest changes
   - Setting proper permissions
   - Handling database migrations
   - Managing environment-specific configurations

## Important Notes

- These scripts are templates and need to be manually copied to Forge
- Any changes to these templates need to be manually synchronized with Forge's deployment scripts
- Quick Deploy is enabled, so pushing to production/staging branches automatically triggers deployment
