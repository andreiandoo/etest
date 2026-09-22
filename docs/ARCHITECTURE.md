# e-test.ro architecture

## Product constraints

- Users must authenticate before taking a test.
- Tests themselves remain free.
- Public SEO landing pages remain accessible without authentication.
- URLs do not repeat /teste/.
- Production target: VPS with PostgreSQL and Redis.

## URL strategy

Examples:

- /auto
- /auto/chestionar-drpciv-categoria-b
- /auto/chestionar-drpciv-categoria-b/start
- /medicina/rezidentiat-simulare-01
- /drept/admitere-barou-simulare-01

The test page is public and indexable. The /start route is authenticated and creates or resumes an attempt. Completed attempts are shown under /attempts/{attempt}/results and are ownership-protected.

## Core domain

Vertical is the SEO and commercial root.

TaxonomyNode is a flexible hierarchy inside a vertical and can represent domain, exam, certification, subject, chapter or topic. A vertical does not have to use every level.

TestDefinition maps to the tests table. Questions live in a reusable question bank. test_question carries per-test order, points and settings.

TestAttempt belongs to a user and a test. AttemptQuestion freezes question content, option order and randomized question order for the lifetime of an attempt. AttemptAnswer stores the normalized user answer, correctness and awarded score.

QuestionReport captures user-reported quality problems. QuestionStatistic contains rebuildable first-party performance metrics such as answer count, correctness rate, points and average response duration.

ContentImport records queued content imports and their row-level results.

## Question types

- single choice
- multiple choice
- true/false
- numeric
- short text
- matching
- ordering

## Modes

- practice
- exam
- quick
- daily
- adaptive
- custom

Practice mode may show feedback immediately after submission. Exam-style modes continue without exposing correctness until results.

## Test engine

The engine is split into focused services:

- AttemptBuilder creates or resumes an in-progress attempt and freezes a snapshot.
- DeterministicOrder derives stable randomized order from the attempt seed.
- AnswerNormalizer converts browser input to a canonical JSON representation.
- AnswerScorer scores canonical answers according to question type and configuration.
- AttemptEngine persists answers, refreshes score, expires/completes attempts and triggers analytics rebuilding.
- QuestionAnalytics rebuilds per-question statistics from source answer rows, making the aggregate idempotent.

See docs/TEST_ENGINE.md for the data contracts used by the scoring engine.

## Content platform

The admin area is a native Livewire application protected by the access-admin Gate.

Current sections:

- verticals;
- taxonomy;
- tests and question assignment;
- question bank/editor;
- queued imports;
- review queue;
- source verification and user reports.

EditorialWorkflow enforces draft -> review -> published transitions. Imported questions always return to draft and therefore cannot bypass review.

ContentImportParser supports CSV, JSON, XLS and XLSX. QuestionImporter uses the pair vertical_id + source_key as the stable external identity when source_key is supplied, allowing imports to update existing questions instead of duplicating them.

See docs/CONTENT_PLATFORM.md for the import and editorial contracts.

## User layer

M5 builds private, noindex user surfaces on top of completed test attempts.

Persisted user-layer data is intentionally small:

- user_stats — XP, streak counters and leaderboard privacy settings;
- user_xp_events — idempotent reward ledger;
- user_achievements — unlocked achievement keys;
- favorite_tests — saved tests.

History, vertical progress and weak-area analytics are derived from attempts and answers instead of duplicating the testing source of truth.

Attempt completion uses a database row lock before changing state, so concurrent finish requests cannot award the same completion twice.

See docs/USER_LAYER.md for formulas and privacy rules.

## Monetization layer

M6 adds contextual commercial modules without changing access to tests.

MonetizationResolver is the single resolver used by public vertical, taxonomy and test pages. It resolves:

- one sponsored content placement;
- one lead generation campaign;
- up to six affiliate resources;
- the newsletter interest represented by the current page.

Target specificity is:

    test > current/ancestor taxonomy > vertical > global

Priority is a tie-breaker inside the same specificity level.

Commercial routes such as /go/* and newsletter confirmation/unsubscribe endpoints are noindex.

Sponsor and affiliate outbound redirects record clicks before redirecting. No IP address is persisted in the click table.

See docs/MONETIZATION.md for campaign, disclosure, consent and newsletter rules.


## White-label & API

M7 adds a tenant context on top of the shared e-test.ro content and user platform.

White-label tenants provide:

- one or more custom hosts;
- brand name, logo, favicon, primary color and copy overrides;
- a restricted set of allowed verticals;
- tenant-aware public pages, sitemaps, runner access, history/results and user insights.

Tenant resolution is host-based and happens in web middleware before page controllers. In production, an unknown host that is not the primary APP_URL host is rejected rather than silently falling back to the global platform.

This is tenant-ready application isolation, not a separate database per customer. Users, questions and tests remain shared platform records; tenants select and brand the verticals they are allowed to expose.

The API is versioned under /api/v1 and uses application-managed API clients and keys.

API keys:

- are shown as plaintext only at issuance time;
- are stored only as SHA-256 hashes plus a display prefix;
- may expire or be revoked;
- become unusable if their client or bound tenant is inactive;
- carry explicit allowlisted scopes and optional daily/monthly quotas;
- reject arbitrary/wildcard scopes at issuance.

ApiUsageMeter uses a PostgreSQL advisory transaction lock plus a daily UPSERT so quota reservation is atomic per key. Usage is aggregated per day as request count, response bytes and error count. Quota-exhausted requests do not consume another slot; admitted exceptions are retained as consumed requests and counted as errors.

Tenant-bound API clients inherit the tenant's vertical restriction.

See docs/WHITE_LABEL.md and docs/API.md.
