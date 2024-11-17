# llms.txt Directory

A directory of AI-friendly documentation implementations using the llms.txt standard.

## Local Development

1. Clone the repository:
```bash
git clone git@github.com:yourusername/llmstxtdirectory.git
cd llmstxtdirectory
```

2. Set up environment:
```bash
cp .env.example .env
# Edit .env with your local settings
```

3. Initialize database:
```bash
php db/init.php
```

4. Set up Herd:
```bash
# Ensure .herd/config.json points to your local path
```

## Deployment

The site uses Laravel Forge for deployment. The deployment process is:

1. Push changes to the `main` branch for staging
2. Push changes to the `production` branch for live site

### Branch Strategy

- `main`: Development branch, deploys to staging
- `production`: Production branch, deploys to live site
- `feature/*`: Feature branches
- `fix/*`: Bug fix branches

### Deployment Process

1. Create feature branch:
```bash
git checkout main
git pull origin main
git checkout -b feature/your-feature
```

2. Make changes and test locally

3. Push to staging:
```bash
git checkout main
git merge feature/your-feature
git push origin main
```

4. After testing on staging, deploy to production:
```bash
git checkout production
git merge main
git push origin production
```

## Directory Structure

```
llmstxtdirectory/
├── .herd/              # Herd configuration
├── db/                 # Database files
├── includes/           # PHP includes
├── public/            # Web root
│   ├── admin/        # Admin interface
│   ├── logos/        # Logo storage
│   └── index.php     # Main entry point
└── storage/          # Application storage
    └── logs/         # Log files
```

## Security

- Never commit `.env` file
- Never commit database files
- Keep admin credentials secure
- Always use HTTPS
- Regular security updates

## Contributing

1. Fork the repository
2. Create feature branch
3. Submit pull request

## License

[Your License]
