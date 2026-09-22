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

## URLs

All public and account URLs are Romanian: `/cauta`, `/panou`, `/istoric`, `/clasament`,
`/autentificare`, `/cont-nou`, `/rezultate/{attempt}` and `/{verticala}/{test}/incepe`.

This matters for one piece of external configuration: the Google OAuth redirect URI is
now `https://e-test.ro/autentificare/google/revenire`, and it must match the value
registered in the Google console and in `GOOGLE_REDIRECT_URI`.

Verticals live at the site root, so a handful of slugs are reserved and rejected by the
admin form. See `App\Services\Content\ReservedSlugs`.

## Development content

    php artisan db:seed --class=DevContentSeeder

Creates demonstrative verticals, taxonomy, tests and questions so the interface can be
worked on against realistic data. Every record carries `metadata.demo = true`, the
source labels say the content must be replaced, and the seeder refuses to run in
production.

## Content administration

The administration area is available at /admin for users with is_admin=true.

Grant the first administrator with:

    php artisan admin:grant admin@example.com

The content platform manages verticals, taxonomy, tests, question authoring, review/publication, source verification and queued CSV/JSON/XLS/XLSX imports.

The M6 monetization admin manages sponsors, contextual lead campaigns, affiliate resources and interest-based newsletter subscriptions. Monetization never unlocks or restricts test access.

M7 adds white-label tenants with custom domains/branding/vertical access and a versioned partner API with hash-only API keys, scopes, quotas and usage metering.

See docs/ARCHITECTURE.md, docs/TEST_ENGINE.md, docs/CONTENT_PLATFORM.md, docs/SEO.md, docs/USER_LAYER.md, docs/MONETIZATION.md, docs/WHITE_LABEL.md, docs/API.md and docs/DEPLOYMENT.md.
