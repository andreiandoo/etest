# VPS deployment

Recommended baseline:

- Ubuntu LTS or Debian stable
- Nginx
- PHP 8.4 FPM
- PostgreSQL 16+
- Redis 7+
- Supervisor
- Node.js 22 for frontend builds, unless assets are built in CI
- TLS via Let's Encrypt or an equivalent provider

## Required PHP extensions

At minimum:

- mbstring
- pdo_pgsql
- gd
- zip
- xml
- xmlreader
- xmlwriter
- ctype
- dom
- fileinfo
- iconv
- simplexml
- zlib

The spreadsheet extensions are required by PhpSpreadsheet for CSV/XLS/XLSX content imports.

## Application layout

Use a dedicated system user and deploy into a stable application directory such as /var/www/etest. The Nginx document root must point to Laravel's public directory, never to the repository root.

## Required production processes

1. PHP-FPM for web requests.
2. Laravel queue workers managed by Supervisor. Content imports and newsletter confirmation emails are processed through the queue.
3. Laravel scheduler from cron.

Example scheduler entry:

    * * * * * cd /var/www/etest && php artisan schedule:run >> /dev/null 2>&1

## Mail configuration

M6 newsletter double opt-in requires a production mail transport.

Configure the Laravel mail environment for the selected provider and verify that mail is not using the local log transport in production.

The following must remain stable and correct because confirmation and unsubscribe links are Laravel signed URLs:

- APP_URL=https://e-test.ro
- APP_KEY

APP_URL must use the public HTTPS hostname before confirmation emails are sent.

Rotating APP_KEY invalidates previously issued signed URLs.

## Deployment sequence

1. Pull the intended release.
2. composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
3. npm install && npm run build, unless CI produces assets.
4. php artisan migrate --force
5. php artisan optimize
6. php artisan queue:restart

## First administrator

Create a normal user account first, then grant admin access from the VPS:

    php artisan admin:grant admin@example.com

The is_admin flag is deliberately guarded from normal mass assignment.

Production must use APP_DEBUG=false, APP_ENV=production and APP_URL=https://e-test.ro. Secrets and APP_KEY must never be committed.


## White-label domains

Adding a tenant domain in the application is only one part of making a custom host live.

For every production white-label host:

1. Configure DNS to resolve to the e-test.ro reverse proxy/VPS.
2. Configure Nginx to accept the hostname and route it to the same Laravel public directory.
3. Provision a TLS certificate for that hostname.
4. Add the hostname to the tenant in /admin/white-label.
5. Assign the verticals that tenant is allowed to expose.
6. Verify the homepage, one allowed route, one blocked route and the tenant sitemap.

Store only the hostname in tenant_domains, for example:

    teste.partener.ro

Do not store a scheme, path or trailing dot.

The first domain entered in the admin is marked primary for tenant administration purposes.

APP_URL remains the canonical primary e-test.ro application URL. ResolveTenant treats that primary hostname as the global platform. In production, unknown hosts are rejected with 404.

### TLS and proxy headers

The reverse proxy must preserve the original Host header. If TLS terminates upstream, configure the trusted proxy/network layer so Laravel still generates HTTPS URLs for the public request.

A white-label site must not be made public until its HTTPS certificate is valid, because canonical URLs, sitemap URLs and structured data are generated from the current request host.

### Google authentication on custom domains

The current Google OAuth integration uses a configured callback URI. A custom white-label domain should not be assumed to support Google login automatically.

Before enabling Google login on partner domains, either:

- register the required callback domains/URIs with the OAuth provider; or
- deliberately centralize the OAuth callback on the primary e-test.ro domain and implement the return-to-tenant flow.

Email/password authentication does not have this callback-domain limitation.

## API operations

The partner API is available under:

    /api/v1

API clients should be created in /admin/api.

Plaintext API keys are displayed only once. Never store them in application logs, source control or browser-side JavaScript.

Recommended operational practices:

- use a separate key for each integration/environment;
- assign the minimum scopes required;
- use finite quotas for external clients;
- set an expiry where practical;
- revoke a key before replacing it;
- monitor api_usage_daily for unexpected request/error growth.

API usage metering relies on PostgreSQL advisory transaction locks, so PostgreSQL remains a production requirement for the current quota implementation.
