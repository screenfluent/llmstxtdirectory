# Changelog

## [Unreleased]

## [1.0.0] - 2024-01-25

### Added
- Admin authentication system
  - Secure login with environment-based credentials
  - Session management
  - Logout functionality
  - Access control for admin routes

- Admin Dashboard
  - Complete CRUD operations for implementations
  - Implementation management interface
  - Logo upload functionality
  - Toggle controls for Featured/Draft status
  - Vote count management

- Environment Configuration
  - `.env` file support
  - Environment-based configuration
  - Debug mode toggle
  - Admin credentials management

### Changed
- Simplified authentication system
  - Replaced complex password hashing with secure environment variables
  - Improved session handling
  - Streamlined login process

- Admin Interface
  - Updated admin panel layout
  - Added navigation between CMS and metrics
  - Improved form validation
  - Enhanced error handling

### Fixed
- Session management issues
- Password verification bugs
- Undefined variable warnings
- Implementation update functionality
- File upload handling

## [0.3.0] - 2024-01-18
### Added
- Environment-aware database initialization
- Database recreation for staging environment
- More comprehensive sample data for staging
- URL uniqueness validation with `isUrlTaken` method
- Public methods for database operations: `executeQuery`, `executeRawSQL`

### Changed
- Admin panel now shows all entries including drafts
- Improved error handling and logging
- Separated staging and production initialization logic
- Made database property public for initialization

### Fixed
- Draft entries visibility in admin panel
- URL conflict handling when updating entries
- Database initialization in staging environment
- Sample data loading consistency

## File Changes

### New Files
- `/public/admin/logout.php`
  - Added secure logout functionality
  - Session destruction
  - Redirect to login page

### Modified Files
- `/includes/admin_auth.php`
  - Simplified authentication logic
  - Added environment variable support
  - Improved session handling

- `/public/admin/index.php`
  - Updated admin dashboard interface
  - Fixed implementation management
  - Added success/error messages
  - Improved form handling

- `/public/admin/login.php`
  - Integrated new authentication system
  - Enhanced error handling
  - Improved redirect logic

- `/.env`
  - Added admin credentials
  - Environment configuration
  - Debug settings

### Security
- Session-based authentication
- Environment variable protection
- Secure credential storage
- Access control implementation

### Development
- PHP Version: 8.3.13
- Web Server: Herd
- Database: SQLite3

## Access
- Admin Login: `/admin/login.php`
- Admin Dashboard: `/admin/index.php`
- Performance Metrics: `/admin/metrics.php`
- Logout: `/admin/logout.php`

## Default Credentials
- Username: `admin`
- Password: `admin123`

## TODO
- [ ] Implement password hashing
- [ ] Add two-factor authentication
- [ ] Create log rotation system
- [ ] Enhance security measures
- [ ] Add rate limiting
- [ ] Implement HTTPS enforcement
