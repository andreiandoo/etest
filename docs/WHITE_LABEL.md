# White-label architecture

M7 makes e-test.ro tenant-ready for branded partner deployments while keeping a single application and shared content platform.

## What a tenant controls

A tenant contains:

- internal name and slug;
- active/inactive state;
- one or more domains;
- permitted verticals;
- branding JSON;
- settings JSON reserved for future tenant configuration.

Current branding fields:

- site_name
- logo_url
- favicon_url
- primary_color
- tagline
- footer_text
- seo_title
- seo_description

Branding is applied to the public layout, homepage metadata and structured-data identity.

## Host resolution

ResolveTenant runs on the web middleware stack.

For each request:

1. Normalize the request hostname to lowercase.
2. Look for an exact tenant_domains.host match.
3. If a record exists, both the domain and tenant must be active.
4. Load the tenant and its permitted verticals into TenantContext.
5. If there is no tenant domain match:
   - the configured APP_URL host is the normal global e-test.ro site;
   - in production, any other unknown host returns 404.

An inactive known host does not fall through to the global platform.

## Domain model

tenant_domains fields:

- tenant_id
- host
- is_primary
- is_active

The host is unique across all tenants.

The admin accepts one hostname per line. The first hostname is marked primary.

Example:

    teste.partener.ro
    evaluare.partener.ro

Do not include:

    https://
    /paths
    query strings

## Vertical isolation

tenant_vertical defines which content roots a tenant may expose.

TenantContext provides:

    allowsVertical()
    allowedVerticalIds()

The restriction is applied to:

- homepage vertical discovery;
- public vertical landing pages;
- nested taxonomy/test presentation routes;
- sitemaps;
- test runner;
- attempt result pages;
- history;
- dashboard aggregates and recent attempts;
- favorite test display;
- vertical progress and weak-area analytics.

Typing the URL of an unassigned vertical should return 404 rather than reveal the global platform content.

## SEO behavior

White-label pages generate URLs from the current request host.

The following use the tenant brand where applicable:

- page title fallback;
- Open Graph site name;
- homepage SEO title/description;
- WebSite structured data;
- Organization structured data;
- breadcrumb root label;
- Quiz provider.

Tenant sitemaps include only assigned verticals and their public taxonomy/tests.

APP_URL remains the primary e-test.ro host; it is not replaced for every tenant.

## Shared identity and content

M7 is intentionally not a database-per-tenant SaaS architecture.

Shared globally:

- users and authentication identity;
- core vertical records;
- taxonomy;
- questions;
- tests;
- attempt data;
- editorial/admin content.

Tenant-specific:

- hostname;
- branding;
- allowed vertical set;
- API client binding.

This allows the same approved content bank to be exposed under different partner brands without duplicating questions and tests.

If a later commercial product requires tenant-specific users, custom questions, independent billing or isolated data ownership, those should be introduced as explicit tenant-scoped domain rules rather than inferred from M7.

## Admin

White-label administration:

    /admin/white-label

The screen supports:

- create/edit tenant;
- activate/deactivate tenant;
- custom hosts;
- brand identity;
- homepage/SEO copy;
- permitted verticals.

The route requires:

    auth + access-admin

## Production setup

Before activating a partner host:

1. Point DNS to the e-test.ro infrastructure.
2. Configure the hostname in Nginx/reverse proxy.
3. Issue a valid TLS certificate.
4. Create the tenant/domain mapping.
5. Assign verticals.
6. Test allowed and blocked URLs.
7. Inspect tenant sitemap/canonical URLs.
8. Confirm the intended authentication methods work on that hostname.

See docs/DEPLOYMENT.md for deployment notes.
