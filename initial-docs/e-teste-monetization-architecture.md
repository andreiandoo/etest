# e-teste.ro — Monetization Architecture & Product Requirements

**Document purpose:** Technical-business specification for implementing monetization on **e-teste.ro** without charging end users for access to tests.

**Audience:** Product / development session responsible for the e-teste.ro application.

**Status:** Product direction / implementation specification  
**Principle:** Tests remain free. A registered user can access and complete tests without a paid subscription.

---

## 1. Product rule: what must never change

e-teste.ro is a free testing platform across multiple verticals and professional/educational domains.

The monetization model must **not** introduce:

- paid access to tests;
- premium-only questions;
- paid result pages;
- paid explanations;
- user subscriptions required to access tests;
- a "free quota" after which the user has to pay;
- forced commercial consent as a condition for taking a test.

The only base requirement for taking tests is:

> **The user must have a registered account.**

Commercial monetization is built around the intent, context and infrastructure created by testing — not around selling the test itself to the individual user.

---

# 2. Monetization models selected for implementation

The platform should be designed from the beginning to support six monetization engines:

1. **Lead generation**
2. **White-label testing**
3. **e-teste.ro API**
4. **Vertical sponsorships**
5. **Affiliate marketplace for books and related learning products**
6. **Interest-based newsletters**

These systems should share the same taxonomy, analytics and partner infrastructure where possible.

---

# 3. Relevant test verticals

The platform architecture must support an unlimited number of verticals. The current catalog/discovery already includes, among others:

- Auto / Transport
- Medicină / Sănătate
- Drept / Justiție
- Construcții / Instalații
- ISCIR
- CNCAN / Radioprotecție
- Finanțe
- Administrație publică
- Admiteri universitare
- Militar
- Feroviar
- Maritim / Fluvial

The monetization architecture must not hard-code commercial behavior for a particular vertical.

A vertical should instead expose configuration such as:

```text
vertical
├── monetization.leads
├── monetization.sponsorship
├── monetization.affiliate
├── monetization.newsletter
├── monetization.white_label
└── monetization.api
```

Each module can be independently enabled or disabled.

---

# 4. Core monetization principle

A test reveals **high-intent context**.

Examples:

- a user completing an admission medicine test is likely interested in admission preparation;
- a user repeatedly solving driving-license tests is likely interested in driving school / learning materials;
- a user taking an ISCIR test may be interested in professional training or certification;
- a user taking a law exam simulation may be interested in books or preparatory courses;
- a user taking a finance/accounting test may be interested in training or professional resources.

The platform should therefore understand:

```text
USER
  ↓
INTEREST
  ↓
VERTICAL
  ↓
TEST
  ↓
TOPICS / SKILLS
  ↓
RESULT
  ↓
COMMERCIAL CONTEXT
```

Monetization modules consume that context.

---

# 5. Shared entities required by all monetization systems

Before implementing individual monetization modules, build a shared commercial layer.

Suggested entities:

```text
users

verticals
categories
tests
test_topics
questions
test_attempts
test_attempt_answers

user_interests
user_interest_events

partners
partner_contacts

campaigns
campaign_verticals
campaign_tests
campaign_topics

consents
consent_events

commercial_impressions
commercial_clicks
commercial_conversions

leads
lead_deliveries

affiliate_merchants
affiliate_products
affiliate_links

sponsorships
sponsorship_assets

newsletter_subscriptions
newsletter_campaigns
newsletter_events

white_label_tenants
white_label_domains
white_label_branding
white_label_users
white_label_tests

api_clients
api_keys
api_usage
api_webhooks
```

The exact database decomposition can be adjusted during implementation, but monetization should not be implemented as isolated ad-hoc tables inside each test type.

---

# 6. Interest graph

The platform should maintain an explicit and inferred interest graph.

Examples:

```text
user_id: 1531

interests:
- medicine.admission
- medicine.anatomy
- university.umf
```

Another example:

```text
user_id: 2843

interests:
- auto.driving_license
- auto.category_b
- auto.road_rules
```

Interest signals can be generated from:

- test started;
- test completed;
- repeated attempts;
- category viewed;
- topic performance;
- newsletter selection;
- explicit user preference;
- lead-generation opt-in.

Store both:

```text
interest_source = explicit | behavioral
```

and an optional score:

```text
interest_score = 0–100
```

This becomes shared infrastructure for lead generation, affiliate recommendations, sponsorship targeting and newsletters.

---

# 7. Monetization Model 1 — Lead Generation

## 7.1 Objective

Generate qualified leads for external partners without restricting access to tests.

The user completes the test normally and receives the full result.

At appropriate high-intent moments, e-teste.ro can display an optional CTA such as:

> Vrei să primești informații despre cursuri de pregătire pentru admiterea la Medicină?

or:

> Cauți o școală de șoferi / program de pregătire?

or:

> Vrei să fii contactat de un furnizor de cursuri ISCIR?

The CTA must always be optional.

---

## 7.2 Lead flow

Recommended flow:

```text
User completes test
        ↓
Result calculated
        ↓
Commercial matching engine evaluates:
- vertical
- test
- topics
- score
- location if voluntarily supplied and relevant
- partner campaigns
        ↓
Lead CTA displayed
        ↓
User explicitly opts in
        ↓
Lead form
        ↓
Consent stored
        ↓
Lead qualified
        ↓
Lead delivered to partner
        ↓
Delivery + status recorded
```

---

## 7.3 Lead form

Do not ask again for information already available in the user's account.

Typical fields:

```text
name
email
phone        optional/configurable
city         optional/configurable
county       optional/configurable
preferred_contact_method
message      optional
```

Campaign-specific questions should be configurable.

Examples:

Medicine:

```text
target_university
exam_year
subjects
```

Driving:

```text
license_category
city
desired_start_period
```

Professional certification:

```text
certification_type
experience_level
desired_course_period
```

---

## 7.4 Consent requirements

Every lead must have a traceable consent event.

Store at minimum:

```text
user_id
campaign_id
partner_id
consent_text_version
timestamp
ip / request metadata where legally appropriate
delivery_scope
```

Do not treat account creation or general newsletter consent as permission to send lead data to a partner.

Lead consent must be independent.

---

## 7.5 Lead delivery methods

Support multiple delivery methods:

### MVP

- partner dashboard;
- email notification;
- CSV export.

### Phase 2

- webhook;
- REST API delivery;
- CRM integration.

Suggested webhook:

```http
POST /webhooks/leads
```

Example payload:

```json
{
  "lead_id": "ld_01...",
  "campaign": "medicine-admission-2027",
  "created_at": "2026-09-19T18:00:00+03:00",
  "contact": {
    "name": "Example User",
    "email": "example@example.com"
  },
  "context": {
    "vertical": "medicine",
    "test": "admission-biology",
    "score": 78
  }
}
```

Only fields covered by the user's explicit consent should be sent.

---

## 7.6 Lead billing models

The application should support multiple billing strategies even if the initial commercial implementation uses only one.

### CPL — Cost per lead

Example logic:

```text
Partner pays X RON for each accepted lead.
```

### Qualified CPL

Higher price when conditions are met:

```text
city = Bucharest
AND
exam_year = 2027
AND
phone_verified = true
```

### Fixed campaign + included leads

Example:

```text
2,000 RON / month
includes 50 leads
additional lead = X RON
```

### Exclusive lead

Lead is delivered to a single partner.

### Shared lead

Lead may be delivered to several partners **only if the consent interface explicitly communicates this**.

Recommended default: **exclusive or clearly scoped delivery**, because it produces a better user experience.

---

## 7.7 Lead campaign configuration

Admin should be able to configure:

```text
campaign name
partner
vertical
category
specific tests
specific topics
score range
active period
geo targeting
CTA title
CTA description
form fields
consent text
thank-you message
delivery method
price / commercial model
lead cap
daily cap
status
```

---

## 7.8 Lead statuses

Recommended:

```text
created
validated
delivered
accepted
rejected
converted
expired
```

Reject reasons should also be recorded.

---

## 7.9 KPIs

Track:

- lead CTA impressions;
- CTA click rate;
- form start rate;
- form completion rate;
- consent rate;
- valid lead rate;
- lead acceptance rate;
- cost / lead;
- revenue / test completion;
- revenue / vertical;
- partner conversion rate where reported.

---

# 8. Monetization Model 2 — White-label

## 8.1 Objective

Allow companies, educational institutions, training providers or professional organizations to use the e-teste.ro testing engine under their own branding.

The public e-teste.ro product remains free.

The organization pays for the infrastructure.

---

## 8.2 Possible white-label use cases

Examples:

### Training provider

```text
academy.company.ro
```

Tests participants after a course.

### Employer

Tests internal staff or job candidates.

### Professional association

Provides competency or certification preparation tests.

### School / university / educational organization

Runs internal assessments.

### Training center

Creates its own test catalog.

---

## 8.3 Tenant model

The architecture should be multi-tenant.

Suggested entity:

```text
white_label_tenants
```

A tenant owns:

```text
branding
domains
admins
tests
question banks
participants
attempts
reports
webhooks
API keys
```

Data must be logically isolated between tenants.

---

## 8.4 Branding options

Tenant configuration:

```text
logo
favicon
primary color
secondary color
font configuration
email sender name
email logo
login page
result page branding
certificate branding
custom footer
legal links
```

---

## 8.5 Domain models

Support:

### Subdomain

```text
partner.e-teste.ro
```

### Custom domain

```text
teste.partner.ro
```

Custom domain mapping should include:

- domain verification;
- TLS/SSL;
- status;
- DNS instructions.

---

## 8.6 Test visibility

A white-label test may be:

```text
private
unlisted
tenant_public
e_teste_public
```

A tenant must not automatically gain the right to modify public e-teste.ro tests.

Possible feature:

> "Use e-teste template"

The platform copies or references an existing public test according to licensing/product rules.

---

## 8.7 Participant access

Support:

- invitation link;
- invitation email;
- access code;
- tenant account;
- optional SSO later.

---

## 8.8 Reporting

Tenant dashboard should include:

```text
participants
completed tests
average score
score distribution
topic performance
attempt history
CSV export
PDF report
```

---

## 8.9 White-label commercial model

Recommended commercial architecture supports:

### Setup fee

For:

- custom domain;
- branding;
- initial configuration;
- migration / setup.

### Monthly or annual platform fee

Based on package.

### Included test executions

Example conceptual package:

```text
Base subscription
+ N executions/month
+ overage per execution
```

### Enterprise/custom

For high volume, API, SSO or custom integrations.

Do not hard-code prices into the application.

Implement pricing plans and limits as configuration.

---

## 8.10 Tenant limits

Plan limits may include:

```text
admins
active tests
question bank size
monthly attempts
custom domains
storage
API calls
webhooks
exports
```

---

# 9. Monetization Model 3 — e-teste.ro API

## 9.1 Objective

Expose the testing engine as infrastructure that external applications can integrate.

Potential clients:

- HR platforms;
- LMS systems;
- schools;
- training platforms;
- recruitment platforms;
- professional associations;
- educational applications.

---

## 9.2 API design principle

The public web application and the API should use the same core domain logic.

Avoid implementing two separate testing engines.

Recommended internal architecture:

```text
Domain services
      ↑
 ┌────┴────┐
Web       API
```

---

## 9.3 Initial API resources

Suggested version:

```text
/api/v1/
```

Core endpoints:

```http
GET    /api/v1/tests
GET    /api/v1/tests/{id}

POST   /api/v1/assessments
GET    /api/v1/assessments/{id}

POST   /api/v1/invitations
GET    /api/v1/invitations/{id}

GET    /api/v1/attempts/{id}
GET    /api/v1/results/{id}

POST   /api/v1/webhooks
GET    /api/v1/webhooks
DELETE /api/v1/webhooks/{id}
```

Later:

```http
POST /api/v1/tests
POST /api/v1/questions
```

for customers allowed to create private tests programmatically.

---

## 9.4 Authentication

Recommended:

```text
API key / token
```

Requirements:

- hashed keys in database;
- visible once at creation;
- scopes;
- revocation;
- expiration optional;
- per-client rate limits;
- audit log.

Possible scopes:

```text
tests:read
assessments:read
assessments:write
results:read
webhooks:manage
tests:write
```

---

## 9.5 Webhooks

Essential events:

```text
assessment.created
attempt.started
attempt.completed
result.created
invitation.accepted
```

Webhook infrastructure should have:

- signing secret;
- HMAC signature;
- retry policy;
- delivery history;
- response status;
- manual retry.

---

## 9.6 API billing models

Architecture should support:

### Per test execution

```text
X RON / completed assessment
```

### API credits

```text
1 execution = N credits
```

### Subscription + included usage

```text
monthly fee
includes N executions
overage after limit
```

### Enterprise committed volume

Annual contract with usage pool.

Again: prices should be configuration, not application constants.

---

## 9.7 API usage metering

Store:

```text
api_client_id
endpoint
request_id
timestamp
status_code
latency
billable_unit
assessment_id
```

Aggregate monthly.

---

## 9.8 Developer portal

Phase 2 feature:

```text
developers.e-teste.ro
```

Should eventually expose:

- API docs;
- authentication guide;
- examples;
- webhook docs;
- API keys;
- usage;
- billing;
- sandbox credentials.

---

# 10. Monetization Model 4 — Sponsorship of a Vertical

## 10.1 Objective

Allow one or more brands to sponsor a relevant vertical without compromising test neutrality.

Examples:

```text
Admitere Medicină
susținută de PARTNER
```

```text
Teste Auto
cu sprijinul PARTNER
```

```text
Pregătire ISCIR
partener PARTNER
```

---

## 10.2 Important rule

Sponsorship must influence **branding and commercial placement**, never:

- correct answers;
- scores;
- ranking of educational content;
- difficulty;
- recommendations represented as objective results.

Sponsored content must be visibly identified.

---

## 10.3 Sponsorship inventory

Possible placements:

### Vertical landing page

- sponsor logo;
- short message;
- CTA;
- branded module.

### Test start page

Small sponsor placement before the test.

### Result page

Sponsor module after the test result.

### Newsletter

Sponsor slot for the same interest vertical.

### Resource area

Sponsored resource / offer.

Avoid placing disruptive sponsored content between individual test questions.

---

## 10.4 Sponsorship exclusivity

Configuration:

```text
exclusive = true | false
```

Possible scope:

```text
vertical
category
test
topic
```

Possible period:

```text
start_at
end_at
```

The system must prevent overlapping exclusive sponsorships.

---

## 10.5 Commercial models

Support:

### Fixed monthly sponsorship

### Quarterly campaign

### Annual vertical partner

### Fixed fee + performance component

Example:

```text
base sponsorship
+
CPL for generated leads
```

This can combine naturally with the Lead Generation module.

---

## 10.6 Sponsorship reporting

Partner dashboard/report:

- impressions;
- unique reach;
- CTA clicks;
- CTR;
- test starts in sponsored vertical;
- completed tests;
- leads generated;
- affiliate conversions where relevant.

---

# 11. Monetization Model 5 — Affiliate Marketplace for Books and Learning Products

## 11.1 Objective

Recommend relevant books and learning resources while transactions remain on the merchant's website.

e-teste.ro should **not** become the seller of the books.

The platform acts as an affiliate publisher.

---

## 11.2 User experience

Example:

User completes:

```text
Admitere Medicină — Anatomie
Score: 62%
```

Results indicate weakness in:

```text
sistem nervos
anatomie cardiovasculară
```

The result page may show:

> Resurse care te pot ajuta să aprofundezi aceste capitole

Products are selected from affiliate catalogs.

---

## 11.3 Recommendation hierarchy

Affiliate products should be matched by:

```text
vertical
category
test
topic
skill / subject
exam
education level
```

Priority:

```text
topic match
> test match
> category match
> vertical match
```

Do not simply display the products with the largest affiliate commission.

---

## 11.4 Affiliate product entity

Suggested structure:

```text
affiliate_products

id
merchant_id
external_product_id
name
description
image_url
landing_url
affiliate_url
price
currency
availability
vertical_id
category_id
metadata
last_synced_at
active
```

Topic matching can use a pivot:

```text
affiliate_product_topics
```

---

## 11.5 Merchant/catalog ingestion

Support three ingestion models:

### Manual

Admin creates product and affiliate URL.

### CSV/XML/feed

Scheduled sync.

### Merchant / affiliate network API

Automated sync.

Normalize all feeds into the same internal product model.

---

## 11.6 Affiliate click tracking

All outgoing links should first pass through an internal tracking route:

```text
/go/{affiliate_link_id}
```

Flow:

```text
user clicks
→ click stored
→ redirect to affiliate URL
```

Track:

```text
user_id if authenticated
test_id
attempt_id
vertical_id
product_id
campaign_id
timestamp
```

Do not add sensitive result details to third-party query strings.

---

## 11.7 Conversion import

If an affiliate network provides conversion reports/API/webhooks, store:

```text
conversion_id
click_id
merchant
order_value
commission
status
timestamp
```

Possible status:

```text
pending
approved
rejected
paid
```

---

## 11.8 Marketplace UI

Possible routes:

```text
/resurse
/resurse/medicina
/resurse/drept
/resurse/auto
```

This should be a **curated affiliate catalog**, not a local shopping cart.

There is no:

- local checkout;
- local payment;
- stock management;
- order management;
- fulfillment;
- invoicing of end users.

CTA:

> Vezi oferta

or:

> Cumpără de la partener

with appropriate affiliate disclosure.

---

## 11.9 Affiliate placement locations

Useful placements:

- result page;
- test explanation page;
- vertical landing page;
- topic page;
- newsletter;
- dedicated resources marketplace.

Avoid excessive product widgets inside the actual question flow.

---

# 12. Monetization Model 6 — Interest-Based Newsletter

## 12.1 Objective

Use the interest graph to build highly relevant newsletters instead of one generic mailing list.

Examples:

```text
Admitere Medicină
Permis Categoria B
Drept / Barou
ISCIR
Finanțe
Administrație publică
```

---

## 12.2 Subscription model

Newsletter permission should be explicit.

The user can subscribe to one or several interests.

Example:

```text
[x] Admitere Medicină
[x] Biologie
[ ] Chimie
[ ] Alte admiteri universitare
```

The user should be able to change subscriptions from account settings.

---

## 12.3 Newsletter content

Potential content modules:

```text
new_tests
recommended_tests
continue_learning
score_progress
new_question_sets
educational_resources
affiliate_products
partner_offer
vertical_sponsor
lead_generation_cta
```

Every email does not need every module.

---

## 12.4 Segmentation

Segmentation should be based on:

```text
explicit interests
vertical activity
test activity
recent attempts
score ranges
topics
recency
engagement
```

Example segment:

```text
Users interested in medicine admission
AND
completed at least 2 biology tests
AND
active in last 30 days
```

---

## 12.5 Newsletter cadence

Do not hard-code one frequency.

Possible user preferences:

```text
weekly
twice_monthly
important_updates_only
```

Initial default recommendation:

```text
weekly
```

but only after explicit subscription.

---

## 12.6 Newsletter monetization

The newsletter can monetize through the other selected models:

### Sponsor placement

```text
Newsletter "Admitere Medicină"
sponsored by PARTNER
```

### Affiliate recommendation

Relevant books/resources.

### Lead-generation CTA

Opt-in commercial offer.

This makes the newsletter primarily a **retention engine**, with monetization layered on top.

---

## 12.7 Required email events

Track:

```text
sent
delivered
opened where technically/legal applicable
clicked
unsubscribed
bounced
complaint
```

Use click activity primarily for product analytics; avoid relying solely on open-rate tracking.

---

# 13. How the six monetization systems work together

The architecture becomes much more valuable when the modules are connected.

Example — Medicine:

```text
Google
  ↓
Free medicine test
  ↓
Account
  ↓
Test completion
  ↓
Interest: medicine.admission
  ↓
Result
  ├── Sponsor placement
  ├── Affiliate books
  ├── Optional course lead CTA
  └── Newsletter subscription
```

Later:

```text
Training company
  ↓
wants its own testing environment
  ↓
White-label
  ↓
API integration
```

A single vertical can therefore generate revenue from several independent sources without charging the test taker.

---

# 14. Example monetization matrix by vertical

| Vertical | Lead Generation | White-label | API | Sponsor | Affiliate | Newsletter |
|---|---|---|---|---|---|---|
| Medicină / Admitere | preparation providers | training centers | edu platforms | publishers / education | books / study material | admission updates |
| Drept / Justiție | preparation courses | training organizations | edu/legal platforms | publishers / training | law books / exam books | exam preparation |
| Auto / Transport | driving schools / training | fleet/training companies | driving apps | auto/education partners | manuals / learning products | category-specific tests |
| ISCIR | training/certification providers | training companies | HR/LMS | industry partners | technical resources | certification updates |
| CNCAN / Radioprotecție | training providers | specialized organizations | LMS / compliance systems | industry partners | specialist resources | professional updates |
| Finanțe | training providers | employers / training | HR/LMS | finance education partners | books / study material | finance tests |
| Administrație publică | exam preparation | institutions/training | education platforms | publishers / training | preparation books | competitions/exams |
| Construcții / Instalații | professional training | employers / training | HR/LMS | industry brands | technical books | professional learning |
| Militar | preparation providers where appropriate | training organizations | education systems | relevant partners | preparation books | exam preparation |
| Feroviar | professional training | operators/training | HR/LMS | industry partners | technical learning | professional tests |
| Maritim / Fluvial | training/certification | academies / operators | LMS | maritime partners | technical materials | certification content |

This matrix is illustrative. Partner eligibility and commercial offers must remain configurable.

---

# 15. Unified Partner model

Instead of creating separate account types for every monetization module, implement a shared Partner entity.

A partner can have capabilities:

```text
lead_buyer
sponsor
affiliate_merchant
white_label_customer
api_customer
newsletter_sponsor
```

Example:

```text
Partner A
- sponsor = yes
- lead_buyer = yes
- white_label = no
```

This simplifies CRM and reporting.

---

# 16. Partner dashboard

Eventually a partner portal should show only modules enabled for that partner.

Possible navigation:

```text
Dashboard
Campaigns
Leads
Sponsorship
API
White-label
Reports
Billing
Team
Settings
```

MVP can begin with admin-managed partners and add self-service later.

---

# 17. Unified Campaign entity

A generic campaign layer can power:

- leads;
- sponsorship;
- newsletter placements;
- affiliate promotions.

Suggested base fields:

```text
id
partner_id
type
name
status
starts_at
ends_at
budget
vertical targeting
test targeting
topic targeting
geo targeting
metadata
```

Campaign types:

```text
lead_generation
sponsorship
affiliate_promotion
newsletter_sponsorship
```

---

# 18. Commercial matching engine

Build a service that receives context:

```json
{
  "user_id": 100,
  "vertical": "medicine",
  "test": "admission-biology",
  "topics": ["anatomy"],
  "score": 71,
  "surface": "result"
}
```

and returns eligible placements:

```json
{
  "lead_campaign": {},
  "sponsor": {},
  "affiliate_products": [],
  "newsletter_interest": {}
}
```

Conceptual service:

```php
CommercialPlacementService
```

Possible methods:

```php
getLeadCampaign(Context $context)
getSponsor(Context $context)
getAffiliateRecommendations(Context $context)
getNewsletterSuggestion(Context $context)
```

This avoids scattering monetization logic across Blade templates/controllers.

---

# 19. Placement surfaces

Define explicit placement surfaces:

```text
vertical_header
vertical_sidebar
test_intro
test_result
test_explanation
resource_page
dashboard
newsletter
```

Campaigns should target surfaces.

Do not allow arbitrary partner HTML/JavaScript inside e-teste.ro.

Store controlled assets:

```text
headline
text
image
logo
cta_label
cta_url
```

This reduces security, performance and UX problems.

---

# 20. Analytics event model

Implement a unified event taxonomy.

Examples:

```text
test.viewed
test.started
test.completed
result.viewed

commercial.impression
commercial.clicked

lead.cta_viewed
lead.started
lead.submitted
lead.delivered

affiliate.product_viewed
affiliate.clicked
affiliate.converted

newsletter.subscribed
newsletter.unsubscribed
newsletter.clicked

sponsor.impression
sponsor.clicked

api.requested
api.assessment_completed

white_label.test_completed
```

Common event properties:

```text
user_id
session_id
vertical_id
test_id
attempt_id
partner_id
campaign_id
surface
timestamp
```

---

# 21. Revenue attribution

Revenue reporting should answer:

```text
How much revenue did each vertical generate?
How much revenue did each completed test generate?
Which test creates the highest commercial value?
Which partner generates the most revenue?
Which monetization model performs best?
```

Suggested metrics:

```text
revenue_per_1000_test_completions
revenue_per_active_user
revenue_per_vertical
lead_revenue
affiliate_revenue
sponsorship_revenue
white_label_revenue
api_revenue
newsletter_attributed_revenue
```

---

# 22. Consent & privacy architecture

Consent must be granular.

Recommended consent types:

```text
terms
privacy
newsletter
partner_lead_transfer
marketing_optional
```

Do not merge:

```text
newsletter consent
```

with:

```text
permission to transfer a lead to Partner X
```

Store consent versions.

Suggested table:

```text
consent_events

id
user_id
consent_type
version
status
partner_id nullable
campaign_id nullable
created_at
metadata
```

The system must support withdrawal where applicable.

---

# 23. Admin console requirements

Add a **Monetization** section in admin.

Suggested menu:

```text
Monetization
├── Partners
├── Campaigns
├── Leads
├── Sponsorships
├── Affiliate
│   ├── Merchants
│   ├── Products
│   └── Clicks / Conversions
├── Newsletters
├── White-label
├── API Clients
└── Reports
```

---

# 24. Recommended implementation priority

## Phase 1 — Foundation

Implement first:

1. Partner entity
2. campaign entity
3. vertical/test/topic targeting
4. commercial placement service
5. consent events
6. analytics events
7. interest graph

These are dependencies for almost every monetization model.

---

## Phase 2 — Fastest monetization modules

### 2A. Affiliate products

Why early:

- no partner lead-data transfer;
- simple commercial tracking;
- natural integration into results;
- immediately useful on educational/exam verticals.

Build:

```text
merchant management
product feed/import
product-topic mapping
/go redirect
click analytics
result-page recommendation widget
```

### 2B. Newsletter

Build:

```text
interest subscriptions
subscription settings
segmentation
email templates
unsubscribe
campaign analytics
```

### 2C. Sponsorship

Build:

```text
campaign targeting
sponsor assets
placement engine
impression/click reports
exclusivity
```

---

## Phase 3 — Lead Generation

Once significant traffic exists:

```text
lead campaigns
dynamic lead forms
consent
partner delivery
lead validation
lead reporting
billing attribution
```

---

## Phase 4 — White-label

Build multi-tenancy:

```text
tenant
branding
custom domains
tenant admins
private tests
participant invitations
reports
usage limits
```

---

## Phase 5 — API

Expose stable domain services:

```text
API clients
tokens/scopes
assessment endpoints
result endpoints
webhooks
metering
usage limits
developer docs
```

White-label and API can share much of the same B2B infrastructure.

---

# 25. Suggested MVP cut

Do **not** attempt to launch every commercial system simultaneously.

Recommended MVP monetization scope:

### Core

- partner management;
- campaign management;
- interest graph;
- consent architecture;
- analytics events.

### Affiliate MVP

- manual affiliate products;
- topic/test/vertical mapping;
- tracked redirects;
- result-page recommendations.

### Newsletter MVP

- interest subscriptions;
- weekly segmented newsletter support;
- unsubscribe/preferences.

### Sponsor MVP

- one sponsor per vertical;
- date range;
- logo/headline/CTA;
- impressions and clicks.

### Lead MVP

- configurable CTA;
- basic lead form;
- explicit partner consent;
- email + dashboard delivery.

White-label and API can follow after the public platform and taxonomy are stable.

---

# 26. Important non-functional requirements

## Performance

Commercial modules must not slow down test interactions.

- load placements efficiently;
- cache eligible campaigns;
- do not block test submission on analytics calls where avoidable;
- affiliate feeds sync asynchronously.

## Security

- no arbitrary JavaScript from sponsors;
- signed webhook deliveries;
- API rate limiting;
- tenant isolation;
- audit partner/admin actions;
- secure API key storage.

## SEO

Affiliate/sponsor modules must not overwhelm educational content.

Vertical/test pages should remain fundamentally useful educational pages.

## Accessibility

Commercial CTAs must not be visually confused with required test controls.

---

# 27. Product rules for commercial neutrality

These rules should be treated as product invariants:

1. User score is calculated independently of sponsors and partners.
2. Sponsors cannot alter questions or correct answers through the monetization system.
3. Affiliate commission must not determine educational correctness.
4. Sponsored modules are labeled.
5. Lead transfer requires explicit consent.
6. Newsletter subscription is optional.
7. The user can complete the test and see the full result without accepting any commercial offer.
8. e-teste.ro does not process the sale of affiliate books/products.
9. Affiliate checkout takes place on the merchant's website.
10. White-label/API customers pay for infrastructure; this does not create a paywall for public e-teste.ro users.

---

# 28. Suggested Laravel domain organization

Illustrative structure:

```text
app/
├── Domain/
│   ├── Testing/
│   ├── Interests/
│   ├── Partners/
│   ├── Campaigns/
│   ├── Leads/
│   ├── Affiliate/
│   ├── Sponsorship/
│   ├── Newsletter/
│   ├── WhiteLabel/
│   └── ApiPlatform/
│
├── Services/
│   ├── CommercialPlacementService.php
│   ├── InterestResolver.php
│   ├── LeadDeliveryService.php
│   ├── AffiliateRecommendationService.php
│   └── CampaignEligibilityService.php
```

Avoid coupling commercial logic directly to individual test controllers.

---

# 29. Suggested feature flags

Use feature flags/configuration:

```text
monetization.leads
monetization.affiliate
monetization.sponsorship
monetization.newsletter
monetization.white_label
monetization.api
```

Also allow activation by vertical.

Example:

```text
medicine:
    leads: true
    affiliate: true
    sponsorship: true

railway:
    leads: false
    affiliate: false
    sponsorship: true
```

---

# 30. Success model

The strategic goal is:

```text
FREE TESTING
      ↓
LARGE ORGANIC AUDIENCE
      ↓
REGISTERED USERS
      ↓
INTEREST + INTENT DATA
      ↓
RELEVANT COMMERCIAL CONTEXT
      ↓
┌───────────────────────────────┐
│ Lead generation               │
│ Affiliate revenue             │
│ Vertical sponsorship          │
│ Interest newsletters          │
│ White-label                   │
│ API                           │
└───────────────────────────────┘
```

The user remains free.

The commercial customer pays for:

- a qualified lead;
- contextual access to an audience;
- affiliate conversions;
- branded testing infrastructure;
- API usage;
- sponsorship visibility.

---

# 31. Final product direction

e-teste.ro should not be architected merely as a collection of quiz pages.

It should be built as three connected layers:

```text
1. FREE TESTING PLATFORM
   public product / SEO / registered users

2. INTENT & CONTENT GRAPH
   verticals / tests / topics / interests / results

3. COMMERCIAL INFRASTRUCTURE
   leads / sponsors / affiliate / newsletter / white-label / API
```

This architecture allows the public promise to remain simple:

> **Tests are free. Create an account and start testing.**

while the platform can develop multiple independent B2B and affiliate revenue streams without converting end users into paying subscribers.

---

## Implementation directive

When developing new testing verticals, every test/category/topic should be compatible with the shared taxonomy and should expose enough metadata to be used later by:

- `InterestResolver`
- `CommercialPlacementService`
- affiliate recommendations
- lead campaign matching
- sponsorship targeting
- newsletter segmentation
- white-label catalog access
- API catalog access

The monetization layer should therefore be considered part of the platform architecture from the beginning, even when a specific monetization module is not yet active.
