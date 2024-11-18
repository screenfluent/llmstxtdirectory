# llmstxt.directory Technical Documentation

## Overview
llmstxt.directory is a web application for cataloging llms.txt implementations. It features a secure admin interface for managing implementations and a public interface for viewing them.

## Architecture

### Database
- SQLite3 database (`votes.db`)
- Environment-aware initialization
- Automatic recreation in staging
- Sample data management
  - Staging: Full set of sample implementations
  - Production: Minimal verified implementations

### Authentication
- Environment-based admin credentials
- Session-based authentication
- Secure password verification
- Planned: Two-factor authentication

### Admin Interface
- Complete CRUD operations
- Logo upload functionality
- Toggle controls:
  - Featured status
  - Draft mode
  - Full implementation flag
- URL uniqueness validation
- All entries visible in admin panel

### Public Interface
- View non-draft implementations
- Vote functionality
- Sort by featured status
- Performance metrics tracking

## Environment Configuration

### Production
- `APP_ENV=production`
- Database preservation
- Minimal sample data
- Error logging only

### Staging
- `APP_ENV=staging`
- Fresh database on each deploy
- Full sample data
- Verbose error reporting

## Security Measures
- Environment-based configuration
- Secure session management
- URL uniqueness validation
- Error logging and monitoring
- Planned:
  - Rate limiting
  - Two-factor authentication
  - Advanced password hashing

## Development

### Requirements
- PHP 8.3+
- SQLite3
- Web server (Apache/Nginx)

### Setup
1. Clone repository
2. Copy `.env.example` to `.env`
3. Set environment variables
4. Run database initialization
5. Configure web server

### Deployment
- Via Laravel Forge
- Zero-downtime deployment
- Automatic database initialization
- Environment-specific configuration

## File Structure
```
/
├── .cascade/          # Project documentation
├── db/               # Database files and initialization
├── includes/         # Core PHP includes
├── public/           # Web root
│   ├── admin/       # Admin interface
│   ├── assets/      # Static assets
│   └── logos/       # Uploaded logos
└── logs/            # Application logs
```

## API Documentation
See `features.json` for detailed API endpoints and functionality.
