# .cascade Project Documentation

This directory contains comprehensive documentation and specifications for the llmstxt.directory project.

## File Structure

- `project.json`: Core project specifications and technical stack
- `database.json`: Database schema, management policies, and security settings
- `deployment.json`: Deployment configurations for production and staging
- `features.json`: Implemented and planned feature specifications

## Key Project Characteristics

### Environment Management
- Production: Data-preserving, zero-downtime deployments
- Staging: Clean testing environment with sample data

### Database Strategy
- Production: Preserves data, uses transactional schema updates
- Staging: Fresh database on each deployment

### Security Considerations
- Proper file permissions (644 for files, 755 for directories)
- Secure database management
- Input validation and sanitization

### Development Workflow
1. Development on staging branch
2. Testing in staging environment
3. Merge to production when ready
4. Zero-downtime production deployments

## Project Evolution

### Completed Features
- Draft system implementation
- Logo management
- URL validation
- Deployment automation
- Database management improvements

### Planned Features
- Advanced search functionality
- User authentication
- Voting system

## Technical Specifications

### Backend
- PHP 8.3
- SQLite3 Database
- Nginx Web Server

### Frontend
- Vanilla JavaScript
- HTML5 & CSS3
- Space Grotesk Font

### Deployment
- Laravel Forge
- Production & Staging Environments
- Automated Deployment Scripts
