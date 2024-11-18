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

1. Push changes to the `staging` branch for testing
2. Once tested, merge to `production` branch for production deployment

## Branches

- `staging`: Development branch, deploys to staging
- `production`: Production branch, deploys to production

## Development Workflow

1. Clone the repository and checkout staging branch:
```bash
git clone https://github.com/screenfluent/llmstxtdirectory.git
cd llmstxtdirectory
git checkout staging
git pull origin staging
```

2. Make your changes and commit them:
```bash
git add .
git commit -m "Your commit message"
```

3. Push to staging:
```bash
git checkout staging
git pull origin staging
git push origin staging
```

4. After testing on staging, merge to production:
```bash
git checkout production
git pull origin production
git merge staging
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
