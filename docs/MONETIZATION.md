# Monetization infrastructure

M6 implements monetization without creating paid user accounts or restricting access to tests.

The four production modules are:

1. lead generation;
2. sponsored content / sponsored verticals;
3. affiliate books and resources;
4. interest-based newsletter.

White-label and the public API remain M7 concerns.

## Core rule: tests remain free

Commercial modules are rendered around public content and test presentation pages.

They do not:

- change question availability;
- change test scoring;
- unlock a test;
- shorten a free test;
- require a purchase;
- create a paid account tier.

The runner continues to use the same authenticated test engine regardless of commercial content.

## Contextual targeting

Sponsor placements, lead campaigns and affiliate resources can target:

- a specific test;
- a taxonomy node;
- a vertical;
- all content globally.

Only one targeting level is stored for a campaign/resource.

MonetizationResolver applies this order:

    specific test
    current taxonomy node
    closest ancestor taxonomy node
    vertical
    global

Within the same target specificity, the higher priority value wins.

Campaign start/end timestamps are enforced by the resolver and by outbound click controllers.

Inactive or expired campaigns are not rendered.

## Sponsorships

Data model:

- sponsors;
- sponsor_campaigns;
- sponsor_placements.

A campaign contains:

- public headline/body;
- CTA label and URL;
- disclosure label;
- start/end window;
- priority;
- active flag.

The public component always displays an explicit disclosure label, defaulting to:

    Conținut sponsorizat

The sponsor name is displayed next to the sponsored content.

Outbound sponsor links use:

    rel="sponsored nofollow"

Clicks pass through:

    /go/sponsor/{placement}

The redirect records a monetization_click row and then redirects to the configured external URL.

We currently track clicks, not impressions. This avoids counting search crawlers and server-side page fetches as sponsor impressions.

## Affiliate resources

Data model:

- affiliate_merchants;
- affiliate_resources.

A resource supports:

- title;
- description;
- resource type, e.g. book/course/tool;
- affiliate URL;
- optional image URL;
- optional price label;
- target context;
- start/end window;
- priority;
- active flag.

Public cards include the disclosure:

    Unele linkuri sunt linkuri de afiliere.

Affiliate links use:

    rel="sponsored nofollow"

Clicks pass through:

    /go/resource/{resource}

The click is stored before redirecting to the external affiliate URL.

The platform itself does not sell or fulfill the book/resource.

## Click tracking

monetization_clicks stores:

- channel: sponsor or affiliate;
- sponsor placement OR affiliate resource;
- optional authenticated user_id;
- referring source URL when supplied by the request;
- created_at.

It deliberately does not store IP addresses.

The click endpoints are covered by the NoIndex middleware and robots.txt excludes /go.

## Lead generation

Data model:

- lead_campaigns;
- lead_submissions.

A lead campaign defines:

- public title/description;
- CTA;
- requested contact fields;
- consent text;
- target context;
- active window;
- priority.

Email is always required.

Name and phone are requested only if the campaign config explicitly includes them.

### Consent

A lead cannot be persisted until the consent checkbox is accepted.

The consent text displayed for that campaign is administrator-controlled so the concrete purpose can be stated.

Each lead stores consented_at.

### Abuse controls

LeadCapture includes:

- a honeypot field;
- request-rate limiting;
- server-side validation.

No lead is silently generated from a page view.

### Lead workflow

Admin statuses:

- new;
- contacted;
- qualified;
- closed.

The admin view shows the most recent leads and permits operational status changes.

## Interest-based newsletter

newsletter_subscriptions is interest-specific.

Current interest keys are generated from:

- vertical:{id}
- taxonomy:{id}

A single email may therefore have multiple independently managed interests.

### Double opt-in

A new subscription starts as:

    pending

The application queues ConfirmNewsletterSubscription.

The email contains a Laravel temporary signed confirmation link valid for 48 hours.

Only the signed confirmation route changes the status to:

    active

An administrator may resend confirmation for a pending subscription but cannot bypass confirmation by activating it manually in the M6 UI.

### Unsubscribe

Every subscription can be addressed by a permanent Laravel signed unsubscribe URL.

Unsubscribe changes the status to:

    unsubscribed

A stale confirmation URL cannot reactivate an unsubscribed record.

To subscribe again, the user must submit the form again, which returns the record to pending and issues a new confirmation.

### Newsletter consent

The signup component:

- requires explicit consent;
- uses a honeypot;
- uses rate limiting;
- records consented_at;
- stores source_url when available.

No newsletter subscription is active just because a user has an e-test.ro account.

## Admin routes

M6 admin screens:

    /admin/monetization/sponsors
    /admin/monetization/leads
    /admin/monetization/affiliate
    /admin/monetization/newsletter

They are protected by:

    auth + access-admin Gate

Admin capabilities include:

- sponsor and sponsor campaign creation/editing;
- contextual sponsor target configuration;
- lead campaign creation/editing;
- lead operational status;
- affiliate merchant/resource creation/editing;
- contextual affiliate target configuration;
- newsletter state counts;
- pending confirmation resend;
- administrative unsubscribe;
- active-interest counts.

## Public indexing

The commercial redirect and subscription-action surfaces are not indexable.

robots.txt disallows:

    /go
    /newsletter

The routes also emit:

    X-Robots-Tag: noindex, nofollow

Commercial modules embedded on public SEO pages do not alter the page canonical.

## Current reporting scope

M6 provides first-party operational counts for:

- sponsor clicks;
- affiliate clicks;
- lead submissions/status;
- newsletter pending/active/unsubscribed.

Advanced campaign reporting, impression tracking, revenue attribution and payout reconciliation can be added later without changing the core campaign models.
