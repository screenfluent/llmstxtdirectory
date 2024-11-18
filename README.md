# llms.txt Directory

A directory of AI-friendly documentation implementations using the llms.txt standard. This project aims to showcase and catalog various implementations of the llms.txt specification, making it easier for developers to discover and implement AI-friendly documentation.

## Features

- Browse llms.txt implementations
- Search by name, type, or features
- View implementation details and statistics
- Vote for useful implementations
- Submit new implementations
- Performance monitoring and analytics
- Staging environment for testing

## Infrastructure

### Production Environment
- Domain: [llmstxt.directory](https://llmstxt.directory)
- SSL: Enabled via Forge
- CDN: Cloudflare with Full SSL mode
- Analytics: Beam Analytics

### Staging Environment
- Domain: [staging.llmstxt.directory](https://staging.llmstxt.directory)
- SSL: Enabled via Forge
- CDN: Cloudflare with Full SSL mode
- Debug Mode: Enhanced logging and performance metrics

### Performance Monitoring
- Request Duration Tracking
- Database Query Performance
- Memory Usage Monitoring
- Route-specific Performance Metrics
- Statistical Analysis (avg, median, percentiles)

## Getting Started

### As a User

1. Visit [llmstxt.directory](https://llmstxt.directory)
2. Browse available implementations
3. Vote for useful implementations
4. Submit new implementations through the form

### As a Developer

1. Clone the repository:
```bash
git clone https://github.com/yourusername/llmstxtdirectory.git
cd llmstxtdirectory
```

2. Set up environment:
```bash
cp .env.example .env
# Edit .env with your settings
```

3. Initialize database:
```bash
php db/init.php
```

## Contributing

We welcome contributions! Here's how you can help:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PHP PSR-12 coding standards
- Write meaningful commit messages
- Update documentation as needed
- Test thoroughly on staging before production
- Monitor performance metrics after changes

## Project Structure

```
llmstxtdirectory/
├── db/                 # Database management
├── includes/           # PHP includes
│   ├── environment.php # Environment detection
│   ├── monitoring.php  # Performance monitoring
│   └── helpers.php     # Utility functions
├── public/            # Web root
│   ├── admin/        # Admin interface
│   ├── logos/        # Implementation logos
│   └── index.php     # Main entry point
├── logs/             # Application logs
└── storage/          # Application storage
```

## Environment Configuration

### Production
- `APP_ENV=production`
- Debug mode disabled
- Full performance monitoring
- Cloudflare CDN enabled

### Staging
- `APP_ENV=staging`
- Enhanced debugging capabilities
- Detailed performance metrics
- No-cache headers for testing

## Performance Metrics

The application tracks various performance metrics:

- **Request Duration**
  - Average response time
  - 95th/99th percentiles
  - Maximum duration

- **Database Performance**
  - Query execution time
  - Query counts
  - Performance by route

- **Memory Usage**
  - Average consumption
  - Peak usage
  - Usage patterns

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contact

Project Link: [https://github.com/yourusername/llmstxtdirectory](https://github.com/yourusername/llmstxtdirectory)

## Acknowledgments

- All contributors who have helped build and maintain this project
- The llms.txt specification creators and community
