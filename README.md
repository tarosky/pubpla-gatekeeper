# Taro Bypass Password Protection

A WordPress plugin that enables password-protected posts to function under Basic authentication.

## Description

Normally, when WordPress admin area (wp-admin) is protected by Basic authentication, password-protected posts cannot function properly because Basic authentication is required when submitting the password form.

This plugin uses REST API and AJAX to perform password authentication, allowing password-protected posts to work even under Basic authentication environments.

## Features

- **Basic Auth Environment Support**: Password-protected posts work normally even when wp-login.php is protected by Basic authentication
- **REST API Endpoint**: `/wp-json/taro-bypass-password-protection/v1/post-password/{post_id}`
- **AJAX Authentication**: Seamless password authentication on the frontend
- **Cookie Management**: Session management for authenticated users
- **Universal Compatibility**: Works without affecting existing WordPress themes

## Origin

This plugin is a generic version of a solution developed for password-protected posts under Basic authentication environments.

## Installation

### 1. Environment Setup

```bash
# Install dependencies
npm install

# Start wp-env environment (Basic authentication configured automatically)
npm run start
```

**Note**: Running `npm run start` will automatically apply Basic authentication settings after starting the wp-env environment.

### 2. WordPress Setup

1. Access http://localhost:8888/wp-login.php
2. Enter `admin` / `admin` in the Basic authentication dialog
3. Log in to WordPress admin (initial setup required on first run)
4. Go to Plugins > Installed Plugins and activate "Taro Bypass Password Protection"

### Basic Authentication Mechanism

- **`.htpasswd` File**: Stored locally in the project root
- **File Mapping**: Mapped to container via `.wp-env.json`
- **`.htaccess` Configuration**: Detects existing files and appends only when necessary
- **Automation**: Configured automatically when starting environment with `npm run start`

### Manual Basic Authentication Setup

```bash
# Run Basic authentication setup only
npm run setup-auth
```

## Usage

### Creating Password-Protected Posts

1. Go to Posts > Add New
2. Create your post
3. In Publish settings, select "Password protected"
4. Set a password (e.g., `test123`)
5. Publish the post

### Verification

1. Access the password-protected post page
2. A password input form should appear
3. Enter the correct password and the post content should be displayed

## Technical Specifications

### File Structure

```
taro-bypass-password-protection/
├── taro-bypass-password-protection.php    # Main plugin file
├── includes/
│   └── class-rest-post-password-override.php  # REST API endpoint
├── assets/
│   └── js/
│       └── post-password.js               # Frontend JavaScript
├── scripts/
│   └── setup-auth.sh                      # Basic authentication setup script
├── .wp-env.json                           # wp-env configuration
├── package.json                           # npm configuration
└── .htpasswd                              # Basic authentication credentials
```

### REST API Endpoint

**POST** `/wp-json/taro-bypass-password-protection/v1/post-password/{post_id}`

```json
{
  "post_password": "test123"
}
```

Response example:
```json
{
  "success": true,
  "redirect_url": "http://localhost:8888/post-permalink"
}
```

### JavaScript API

The plugin automatically detects password-protected post forms and converts them to AJAX submission.

## Development & Testing

For detailed testing procedures, see [TESTING.md](TESTING.md).

### Environment Information

- WordPress: Latest version (auto-configured by wp-env)
- PHP: 8.1
- Apache: 2.4
- Basic Authentication: admin/admin
- Port: 8888 (default wp-env port)
- Protected Area: wp-login.php only

### Environment Cleanup

```bash
# Stop wp-env environment
npm run stop

# Destroy wp-env environment (complete cleanup)
npm run destroy
```

## Basic Authentication Scope

This plugin specifically addresses the issue where **wp-login.php is protected by Basic authentication**. The protection scope is:

- ✅ **Protected**: `http://localhost:8888/wp-login.php` (Login page only)
- 🔓 **Not Protected**: `http://localhost:8888/` (Frontend)
- 🔓 **Not Protected**: `http://localhost:8888/wp-admin/` (Admin area)

The plugin uses managed `.htaccess` rules with separators:

```apache
# //taro-bypass-password-protection START
<Files "wp-login.php">
    AuthType Basic
    AuthName "WordPress Admin Area"
    AuthUserFile /var/www/html/.htpasswd
    Require valid-user
</Files>
# //taro-bypass-password-protection END
```

## Troubleshooting

### docker-compose Not Found

```bash
# Run from project directory
chmod +x docker-compose
PATH="$PWD:$PATH" npm run start
```

### Basic Authentication Not Working

```bash
# Check authentication
curl -I http://localhost:8888/wp-login.php          # Should return 401
curl -I -u admin:admin http://localhost:8888/wp-login.php  # Should return 200
```

### Testing the Plugin

1. Create a password-protected post
2. Access the post from frontend
3. Verify that the password form works without Basic authentication prompts
4. Check that the AJAX request succeeds

## Requirements

- Node.js
- Docker
- WordPress (via wp-env)

## License

GPL-2.0

## Author

Ported from: WordPress site development project

## Changelog

### 1.0.0
- Initial release
- REST API endpoint for password verification
- AJAX-based password form handling
- Basic authentication bypass for wp-login.php
- Automatic environment setup with wp-env
