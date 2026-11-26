# Development

Developer notes for PubPla Gatekeeper. For test procedures, see [TESTING.md](TESTING.md).

## Environment Setup

Requirements: Node.js 22 (see `.node-version`), PHP 7.4+, Composer, Docker.

```bash
# Install dependencies and build assets
composer install
npm install
npm run package

# Start wp-env (Basic authentication is configured automatically)
npm run start
```

Without `npm run package`, the password form still works without JS, but errors are not shown on the form. Running `npm run start` applies the Basic authentication settings after wp-env starts.

1. Access http://localhost:8888/wp-login.php
2. Enter `admin` / `admin` in the Basic authentication dialog
3. Log in to WordPress admin (initial setup required on first run)
4. Go to Plugins > Installed Plugins and activate "PubPla Gatekeeper"

### Basic Authentication Mechanism

- **`.htpasswd`**: stored in the project root
- **File mapping**: mapped to the container via `.wp-env.json`
- **`.htaccess`**: the setup script appends its rules only when necessary
- **Automation**: configured automatically by `npm run start`

To run the Basic authentication setup only:

```bash
npm run setup-auth
```

The protection scope in the development environment:

- Protected: `http://localhost:8888/wp-login.php`
- Not protected: `http://localhost:8888/` (frontend)
- Not protected: `http://localhost:8888/wp-admin/`

The setup script manages `.htaccess` rules between markers:

```apache
# //pubpla-gatekeeper START
<Files "wp-login.php">
    AuthType Basic
    AuthName "WordPress Admin Area"
    AuthUserFile /var/www/html/.htpasswd
    Require valid-user
</Files>
# //pubpla-gatekeeper END
```

### Cleanup

```bash
# Stop wp-env
npm run stop

# Destroy wp-env (complete cleanup)
npm run destroy
```

## Lint, Test, and Build

```bash
composer lint      # PHPCS (WordPress Coding Standards)
composer phpstan   # PHPStan level 5
npm run lint       # ESLint
npm test           # PHPUnit inside wp-env (requires `npm run start`)
npm run package    # Build assets/js -> dist/js and generate wp-dependencies.json
```

husky + lint-staged run the linters on staged files at commit time.

JavaScript sources live in `assets/js/` and are built into `dist/js/` by [grab-deps](https://github.com/kuno1/grab-deps). Each file declares its handle and dependencies in a `/*!` header comment, and the plugin registers them from `wp-dependencies.json` on `init`. Both `dist/` and `wp-dependencies.json` are build artifacts and are not committed.

## CI/CD

- **Pull requests**: `test.yml` runs PHPUnit (PHP 7.4/8.3 × WordPress 6.6/latest), PHPCS, PHPStan, and the asset build. `Status Check` is the single required check. After CI passes, Claude reviews the PR.
- **Merges to main**: release-drafter updates the draft release.
- **Publishing a release**: `deploy.yml` builds the plugin, converts `README.md` to `readme.txt`, writes the tag as the version, and attaches `pubpla-gatekeeper.zip` to the release. The version in the source stays `nightly`.

The plugin is not on WordPress.org yet. Once the slug is approved, enable the commented-out deploy step in `deploy.yml` and add `wp-plugin-audit.yml` / `asset-update.yml` from the reference plugin (rich-taxonomy).

## File Structure

```
pubpla-gatekeeper/
├── pubpla-gatekeeper.php                       # Main plugin file
├── includes/
│   ├── class-pubpla-gatekeeper.php             # Main class
│   └── class-rest-post-password-override.php   # REST API endpoint for post passwords
├── assets/
│   └── js/
│       └── post-password.js                    # Frontend JavaScript (source)
├── dist/                                       # Built assets (not committed)
├── tests/                                      # PHPUnit tests
├── scripts/
│   └── setup-auth.sh                           # Basic authentication setup script
├── .github/workflows/                          # CI/CD
├── .wp-env.json                                # wp-env configuration
├── composer.json                               # PHP dev tools (PHPCS, PHPStan, PHPUnit)
├── package.json                                # npm configuration
└── .htpasswd                                   # Basic authentication credentials (development only)
```

## REST API

### Post password

**POST** `/wp-json/pubpla-gatekeeper/v1/post-password/{post_id}`

```json
{
  "post_password": "test123"
}
```

Response:

```json
{
  "url": "http://localhost:8888/post-permalink/"
}
```

The password form posts here instead of `wp-login.php?action=postpass`, so it keeps working when `wp-login.php` is behind Basic authentication. On success the endpoint sets the same `wp-postpass_{COOKIEHASH}` cookie as core (`HttpOnly`, `SameSite=Lax`).

The response depends on the `Accept` header:

| Case | JSON (`Accept: application/json`, sent by the JS) | Form submission without JS |
|---|---|---|
| Correct password | 200 `{ "url": permalink }` | 303 to the permalink |
| Wrong password | 403 `rest_post_wrong_password` (no cookie) | 303 to the permalink (the form is shown again) |
| Empty password | 400 `rest_post_no_password` | 303 to the permalink |
| Missing or not publicly viewable post | 404 `rest_post_not_found` | 303 to the home page |

Drafts and private posts are treated as missing so that the endpoint does not reveal which post IDs exist.

`tests/TestPostPasswordRest.php` checks that the cookie value is accepted by core's `post_password_required()`, so a change in core's hashing fails CI.

## Readme

`README.md` is converted to `readme.txt` for WordPress.org by [fumikito/wp-readme](https://github.com/fumikito/wp-readme). Write it in the WordPress readme format:

- `#` becomes `=== ===`, `##` becomes `== ==`, `###` becomes `= =`
- Header fields end with two spaces for line breaks on GitHub
- Wrap GitHub-only content in `<!-- only:github/ -->` ... `<!-- /only:github -->`

## Troubleshooting

### docker-compose not found

```bash
# Run from the project directory
chmod +x docker-compose
PATH="$PWD:$PATH" npm run start
```

### Basic authentication not working

```bash
curl -I http://localhost:8888/wp-login.php                 # Should return 401
curl -I -u admin:admin http://localhost:8888/wp-login.php  # Should return 200
```
