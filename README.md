# e-test.ro

Platformă multi-verticală de testare online. Testele sunt gratuite, iar utilizatorul trebuie să aibă cont pentru a le susține.

## Stack

Laravel 13, Livewire 4, Blade, Tailwind CSS 4, Alpine.js via Livewire, PostgreSQL, Redis, PhpSpreadsheet, Pest, Larastan și Vite.

## Development

1. Copy .env.example to .env.
2. Configure PostgreSQL and Redis.
3. Run composer install.
4. Run php artisan key:generate.
5. Run php artisan migrate.
6. Run npm install.
7. Run composer run dev.

## Content administration

The administration area is available at /admin for users with is_admin=true.

Grant the first administrator with:

    php artisan admin:grant admin@example.com

The content platform manages verticals, taxonomy, tests, question authoring, review/publication, source verification and queued CSV/JSON/XLS/XLSX imports.

The M6 monetization admin manages sponsors, contextual lead campaigns, affiliate resources and interest-based newsletter subscriptions. Monetization never unlocks or restricts test access.

M7 adds white-label tenants with custom domains/branding/vertical access and a versioned partner API with hash-only API keys, scopes, quotas and usage metering.

See docs/ARCHITECTURE.md, docs/TEST_ENGINE.md, docs/CONTENT_PLATFORM.md, docs/SEO.md, docs/USER_LAYER.md, docs/MONETIZATION.md, docs/WHITE_LABEL.md, docs/API.md and docs/DEPLOYMENT.md.
