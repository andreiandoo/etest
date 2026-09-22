# Public SEO architecture

M4 establishes the public discovery layer for e-test.ro.

## URL model

The domain name already communicates the product, so public URLs do not repeat /teste/.

Examples:

    /
    /auto
    /auto/chestionare-drpciv
    /auto/chestionare-drpciv/categoria-b
    /auto/chestionare-drpciv/categoria-b/simulare-001
    /medicina/rezidentiat
    /drept/admitere-barou

Taxonomy URLs follow the parent hierarchy. Test URLs append the test slug to the taxonomy hierarchy.

PublicUrlGenerator is the single source of truth for these URLs.

If an old/shallow URL resolves to a known taxonomy node or published test but does not match its canonical hierarchy, PublicContentController issues a permanent 301 redirect to the canonical URL.

Runner URLs remain operational rather than SEO URLs:

    /{vertical}/{test}/start

They require authentication and emit X-Robots-Tag: noindex, nofollow.

## Canonical rules

Every indexable page emits rel=canonical.

- homepage -> /
- vertical -> /{vertical}
- taxonomy -> full taxonomy path
- test -> full taxonomy path + test slug
- page 2+ of a paginated listing -> self canonical including ?page=N

Do not canonicalize all paginated pages to page 1.

Only one canonical URL should be used in internal navigation, sitemap output and structured data.

Reference:
https://developers.google.com/search/docs/crawling-indexing/canonicalization

## Indexing policy

Indexable:

- homepage
- active verticals
- active taxonomy nodes
- published test presentation pages

Noindex / private:

- /login
- /register
- /auth/*
- /dashboard
- /attempts/*
- /admin/*
- runner URLs ending in /start

Private Laravel routes use the NoIndex middleware, which adds:

    X-Robots-Tag: noindex, nofollow

robots.txt also disallows private areas and points to the sitemap index.

## Sitemaps

Entry point:

    /sitemap.xml

Current child sitemaps:

    /sitemaps/verticals.xml
    /sitemaps/taxonomy.xml
    /sitemaps/tests.xml

Only active/public content is emitted.

If a content group approaches the sitemap protocol limit of 50,000 URLs, split that group into numbered sitemap files and keep the top-level sitemap index as the stable submission URL.

## Structured data

### Homepage

The homepage emits a JSON-LD graph containing:

- WebSite
- Organization

### Vertical and taxonomy landing pages

These pages emit:

- BreadcrumbList
- CollectionPage

### Test presentation pages

These pages emit:

- BreadcrumbList
- schema.org Quiz

Quiz is used as a semantic Schema.org type for a test of knowledge.

We deliberately do not add Question/Answer hasPart markup or claim Google's Education Q&A rich result because the public test presentation page does not expose flashcards with visible answers.

References:

- https://developers.google.com/search/docs/appearance/structured-data/breadcrumb
- https://developers.google.com/search/docs/appearance/structured-data/education-qa
- https://schema.org/Quiz

## Metadata

The public layout emits:

- title
- meta description
- meta robots
- rel=canonical
- Open Graph title/description/url/site name/locale
- Twitter summary card
- theme color
- favicon

Vertical, taxonomy and test records support editorial SEO title and description overrides.

When the override is empty, the application generates deterministic defaults from the content title/name and description.

## Content rules

Programmatic pages should not be published solely because a URL can be generated.

Before publishing an indexable page:

1. The title/name must identify a real search intent.
2. The description must be useful and materially specific to that page.
3. Test landing pages must describe what is assessed.
4. Taxonomy landing pages should explain the exam/subject/chapter, not merely list links.
5. Avoid creating empty near-duplicate taxonomy branches.
6. Questions and factual explanations should retain source provenance and verification dates where applicable.

## Post-deploy Google checklist

After production is available on https://e-test.ro:

1. Add/verify the domain property in Google Search Console.
2. Submit https://e-test.ro/sitemap.xml.
3. Inspect the homepage, one vertical, one nested taxonomy page and one test page with URL Inspection.
4. Confirm Google's selected canonical matches the application canonical.
5. Run representative public pages through Google's Rich Results Test and validate BreadcrumbList.
6. Check Search Console Page Indexing for accidental indexing of private URLs.
7. Monitor Core Web Vitals after real traffic begins.
8. Watch sitemap discovered/indexed URL counts as content imports scale.

Google notes that an explicit canonical is a signal rather than an absolute command, so internal links, sitemap URLs, redirects and rel=canonical should all agree.

References:

- https://developers.google.com/search/docs/crawling-indexing/canonicalization
- https://developers.google.com/search/docs/crawling-indexing/canonicalization-troubleshooting
- https://developers.google.com/search/docs/appearance/structured-data/breadcrumb
