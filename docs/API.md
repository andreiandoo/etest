# e-test.ro API v1

M7 provides a read-oriented partner API for approved integrations.

Base path:

    /api/v1

## Authentication

Supported credentials:

    Authorization: Bearer <API_KEY>

or:

    X-API-Key: <API_KEY>

Keys are issued in /admin/api.

A plaintext key resembles:

    et_live_<client-id>_<prefix>_<secret>

The exact plaintext value is displayed only once.

The database stores:

- key_prefix for recognition in admin;
- SHA-256 key_hash for authentication.

The plaintext secret is not recoverable from the database.

## Key lifecycle

A request is rejected with 401 when:

- the key is missing;
- the hash is unknown;
- the key was revoked;
- the key expired;
- the API client is inactive;
- a tenant-bound client belongs to an inactive tenant.

last_used_at is updated after successful key authentication.

## Clients and tenant binding

An API client may be:

- global — sees all active platform verticals;
- bound to a white-label tenant — sees only verticals assigned to that tenant.

Tenant restrictions are enforced independently from scopes.

A client with tests:read does not gain access to a vertical outside its tenant.

## Scopes

Current scopes:

    catalog:read
    tests:read
    questions:read
    answers:read

Scopes are validated against this explicit allowlist both in the admin UI and in the key issuer. Arbitrary scopes and wildcard "*" are rejected.

### catalog:read

Allows:

    GET /api/v1/verticals

### tests:read

Allows:

    GET /api/v1/verticals/{vertical-slug}/tests
    GET /api/v1/tests/{test-id}

### questions:read

Allows:

    GET /api/v1/tests/{test-id}/questions

Without answers:read, the question payload deliberately excludes:

- answer_config;
- explanation;
- option is_correct;
- option feedback.

### answers:read

answers:read is an additional sensitive scope.

It only has meaning together with questions:read on the current v1 routes.

When present, the questions endpoint may include answer configuration, explanations, correctness flags and feedback.

Do not issue answers:read to an integration that only needs a public question bank.

## Endpoints

### GET /api/v1/status

Requires a valid key, no additional scope.

Returns client/key metadata such as:

- client ID/name;
- bound tenant slug;
- key ID/name/prefix;
- scopes;
- quotas;
- expiry.

### GET /api/v1/verticals

Scope:

    catalog:read

Returns active verticals visible to the client.

### GET /api/v1/verticals/{slug}/tests

Scope:

    tests:read

Returns published tests for the selected visible vertical.

Query:

    per_page

Accepted range is clamped to 1–100; default is 25.

### GET /api/v1/tests/{id}

Scope:

    tests:read

Returns one published test if it belongs to an active, visible vertical.

### GET /api/v1/tests/{id}/questions

Scope:

    questions:read

Returns published questions attached to the test.

Query:

    per_page

Accepted range is clamped to 1–100; default is 50.

The response meta includes:

    answers_included

This explicitly indicates whether answers:read enriched the payload.

## Quotas

An API key may define:

- daily_quota;
- monthly_quota.

A null quota means no limit for that period.

Quota enforcement happens after key authentication and before route/controller execution.

If a quota is exhausted:

HTTP:

    429 Too Many Requests

JSON:

    {
      "error": {
        "code": "quota_exceeded",
        "message": "Daily API quota exceeded."
      }
    }

or the monthly equivalent.

Admitted requests include current usage headers:

    X-RateLimit-Daily-Used
    X-RateLimit-Monthly-Used

When a corresponding finite quota is configured, the response also includes:

    X-RateLimit-Daily-Limit
    X-RateLimit-Monthly-Limit

For an unlimited period (null quota), the Limit header is omitted rather than reported as 0. The same usage/limit headers are returned on quota-exceeded responses.

## Concurrency

Quota reservation is serialized per API key with a PostgreSQL advisory transaction lock.

The daily usage row is incremented through PostgreSQL UPSERT:

    unique(api_key_id, usage_date)

This prevents parallel requests from independently observing the same quota slot.

## Usage metering

api_usage_daily aggregates per key/day:

- request_count;
- response_bytes;
- error_count.

request_count is reserved before the application endpoint runs.

response_bytes and error_count are updated after a response returns through EnforceApiQuota.

An admitted 4xx/5xx response is counted as an error.

If application code throws after quota admission, the reserved request remains consumed and error_count is incremented even though there is no response body to add to response_bytes.

Requests rejected before quota admission, such as an invalid API key, are not attributed to a valid key's usage row. A request rejected because the quota is already exhausted also does not consume an additional quota slot.

## Error format

Authentication/scope/quota middleware uses a stable envelope:

    {
      "error": {
        "code": "forbidden",
        "message": "Missing required scope: tests:read"
      }
    }

Typical codes:

- unauthorized
- forbidden
- quota_exceeded

Normal Laravel 404 JSON is used when a resource is unavailable or outside the client's tenant visibility.

## Examples

List verticals:

    curl \
      -H "Authorization: Bearer $ETEST_API_KEY" \
      https://e-test.ro/api/v1/verticals

List tests:

    curl \
      -H "X-API-Key: $ETEST_API_KEY" \
      "https://e-test.ro/api/v1/verticals/auto/tests?per_page=25"

Read questions without answer keys:

    curl \
      -H "Authorization: Bearer $ETEST_API_KEY" \
      https://e-test.ro/api/v1/tests/123/questions

## Administration

API administration:

    /admin/api

Admin capabilities:

- create/edit API clients;
- bind client to a tenant;
- enable/disable client;
- issue keys;
- choose scopes;
- set daily/monthly quota;
- set expiry;
- revoke keys;
- inspect 30-day request/error/response-byte totals.

The newly issued plaintext key is shown in an explicit one-time UI state.

## Versioning

All current external routes are under:

    /api/v1

Breaking response-contract changes should be introduced under a new API version rather than silently changing v1 semantics.
