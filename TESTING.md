# Testing Procedures

## Environment Setup

### Using wp-env

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Start wp-env Environment**
   ```bash
   npm run start
   ```
   
   **Note**: Running `npm run start` automatically applies Basic authentication settings after starting the wp-env environment.

3. **Automatic Basic Authentication Setup**
   - Basic authentication is automatically configured for wp-login.php
   - Authentication credentials: `admin` / `admin`
   - Script: `scripts/setup-auth.sh`

### Manual Basic Authentication Setup

```bash
# Run Basic authentication setup only
npm run setup-auth
```

### Environment Verification

- WordPress Frontend: http://localhost:8888
- WordPress Login: http://localhost:8888/wp-login.php (Basic authentication required)
- Plugin Directory: wp-content/plugins/pubpla-gatekeeper

## Testing Procedures

### 1. Basic Authentication Verification

```bash
# Access without authentication (should return 401 error)
curl -I http://localhost:8888/wp-login.php

# Access with Basic authentication (should succeed)
curl -I -u admin:admin http://localhost:8888/wp-login.php

# Frontend should be accessible without authentication
curl -I http://localhost:8888/
```

### 2. Plugin Activation

1. Access http://localhost:8888/wp-login.php
2. Enter `admin` / `admin` in Basic authentication dialog
3. Log in to WordPress admin (initial setup required on first run)
4. Go to Plugins > Installed Plugins and activate "PubPla Gatekeeper"

### 3. Creating Password-Protected Posts

1. Go to Posts > Add New
2. Create a post
3. In Publish settings, select "Password protected"
4. Set a password (e.g., `test123`)
5. Publish the post

### 4. Plugin Functionality Testing

1. **Normal WordPress Environment (without Basic auth)**
   - Verify that password-protected post forms work normally

2. **Basic Authentication Environment**
   - Without the plugin, password-protected post forms would fail due to Basic auth
   - With the plugin active, password authentication works via AJAX
   - Verify that the password form works correctly on the post page

### 5. REST API Endpoint Verification

```bash
# Check if the plugin's REST API endpoint is available
curl -X POST http://localhost:8888/wp-json/pubpla-gatekeeper/v1/post-password/1 \
  -H "Content-Type: application/json" \
  -d '{"post_password": "test123"}'
```

### 6. JavaScript Functionality Testing

1. Open browser developer tools
2. Enter password on a password-protected post page
3. Verify that AJAX requests are executed in the console
4. Verify that entering the correct password displays the post content

## Environment Cleanup

```bash
# Stop wp-env environment
npm run stop

# Destroy wp-env environment (complete cleanup)
npm run destroy
```

## Troubleshooting

### docker-compose Not Found Error

If wp-env cannot find the `docker-compose` command:

```bash
# Create docker-compose wrapper in project directory
chmod +x docker-compose
PATH="$PWD:$PATH" npm run start
```

### Basic Authentication Not Working

1. Check if .htaccess file is properly configured
2. Verify Apache mod_auth is enabled
3. Check file permissions are set correctly

### Plugin Not Loading

1. Verify `.wp-env.json` `mappings` configuration is correct
2. Check if plugin files are properly mapped to container
3. Try restarting the wp-env environment

## Expected Behavior

- WordPress login page should be accessible with Basic authentication
- Password-protected posts should work normally in Basic authentication environment
- Plugin's REST API endpoint should function correctly
- JavaScript (AJAX) password authentication should work
- CSS and JavaScript files should load properly

## Test Cases

### Test Case 1: Basic Authentication
- **Input**: Access wp-login.php without credentials
- **Expected**: HTTP 401 Unauthorized
- **Input**: Access wp-login.php with admin/admin
- **Expected**: HTTP 200 OK with login form

### Test Case 2: Frontend Access
- **Input**: Access homepage without credentials
- **Expected**: HTTP 200 OK (no Basic auth required)

### Test Case 3: Password-Protected Post
- **Input**: Access password-protected post
- **Expected**: Password form displayed
- **Input**: Enter correct password
- **Expected**: Post content displayed without Basic auth prompt

### Test Case 4: REST API
- **Input**: POST to `/wp-json/pubpla-gatekeeper/v1/post-password/{id}`
- **Expected**: JSON response with success status
- **Input**: Wrong password
- **Expected**: JSON response with error status

### Test Case 5: AJAX Functionality
- **Input**: Submit password form
- **Expected**: AJAX request sent, no page reload
- **Input**: Correct password
- **Expected**: Content displayed via JavaScript

## Performance Testing

1. **Load Testing**: Verify multiple concurrent password attempts
2. **Cookie Management**: Test session persistence across page loads
3. **Error Handling**: Test invalid post IDs and malformed requests

## Security Testing

1. **CSRF Protection**: Verify nonce validation
2. **Input Validation**: Test SQL injection attempts
3. **Authorization**: Verify user permissions
4. **Session Management**: Test cookie security

## Browser Compatibility

Test the plugin functionality across different browsers:
- Chrome/Chromium
- Firefox
- Safari
- Edge

## WordPress Version Compatibility

Test with different WordPress versions:
- Latest stable version
- Previous major version
- WordPress 6.0+
