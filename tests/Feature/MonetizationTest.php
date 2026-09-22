<?php

use App\Enums\PublicationStatus;
use App\Enums\TaxonomyNodeType;
use App\Livewire\Monetization\LeadCapture;
use App\Livewire\Monetization\NewsletterSignup;
use App\Mail\ConfirmNewsletterSubscription;
use App\Models\AffiliateMerchant;
use App\Models\AffiliateResource;
use App\Models\LeadCampaign;
use App\Models\LeadSubmission;
use App\Models\MonetizationClick;
use App\Models\NewsletterSubscription;
use App\Models\Sponsor;
use App\Models\SponsorCampaign;
use App\Models\SponsorPlacement;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Monetization\MonetizationResolver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function monetizationContext(): array
{
    $vertical = Vertical::factory()->create([
        'name' => 'Auto',
        'slug' => 'auto',
        'is_active' => true,
    ]);

    $parent = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'type' => TaxonomyNodeType::Exam,
        'name' => 'DRPCIV',
        'slug' => 'drpciv',
        'is_active' => true,
    ]);

    $node = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'parent_id' => $parent->id,
        'type' => TaxonomyNodeType::Subject,
        'name' => 'Categoria B',
        'slug' => 'categoria-b',
        'is_active' => true,
    ]);

    $test = TestDefinition::factory()->for($vertical)->create([
        'taxonomy_node_id' => $node->id,
        'title' => 'Simulare B',
        'slug' => 'simulare-b',
        'status' => PublicationStatus::Published,
        'published_at' => now()->subMinute(),
    ]);

    return compact('vertical', 'parent', 'node', 'test');
}

function createSponsorPlacement(
    string $name,
    ?int $verticalId = null,
    ?int $taxonomyNodeId = null,
    ?int $testId = null,
    int $priority = 0,
): SponsorPlacement {
    $sponsor = Sponsor::create([
        'name' => $name,
        'is_active' => true,
    ]);

    $campaign = SponsorCampaign::create([
        'sponsor_id' => $sponsor->id,
        'name' => $name.' campaign',
        'headline' => $name.' headline',
        'cta_label' => 'Detalii',
        'cta_url' => 'https://partner.example/'.$sponsor->id,
        'disclosure_label' => 'Conținut sponsorizat',
        'is_active' => true,
        'priority' => $priority,
    ]);

    return SponsorPlacement::create([
        'sponsor_campaign_id' => $campaign->id,
        'vertical_id' => $verticalId,
        'taxonomy_node_id' => $taxonomyNodeId,
        'test_id' => $testId,
        'placement' => 'content',
        'is_active' => true,
    ]);
}

test('monetization resolver prefers test then taxonomy then vertical then global targeting', function () {
    $context = monetizationContext();

    createSponsorPlacement('Global');
    createSponsorPlacement('Vertical', verticalId: $context['vertical']->id);
    createSponsorPlacement('Parent', taxonomyNodeId: $context['parent']->id);
    createSponsorPlacement('Node', taxonomyNodeId: $context['node']->id);
    $testPlacement = createSponsorPlacement('Test', testId: $context['test']->id);

    LeadCampaign::create([
        'name' => 'Global lead',
        'title' => 'Global lead',
        'consent_text' => 'Sunt de acord cu prelucrarea datelor pentru această solicitare de informații.',
        'is_active' => true,
    ]);
    LeadCampaign::create([
        'test_id' => $context['test']->id,
        'name' => 'Test lead',
        'title' => 'Test lead',
        'consent_text' => 'Sunt de acord cu prelucrarea datelor pentru această solicitare de informații.',
        'is_active' => true,
    ]);

    $merchant = AffiliateMerchant::create(['name' => 'Target Merchant', 'is_active' => true]);
    AffiliateResource::create([
        'affiliate_merchant_id' => $merchant->id,
        'title' => 'Global resource',
        'affiliate_url' => 'https://books.example/global',
        'is_active' => true,
    ]);
    AffiliateResource::create([
        'affiliate_merchant_id' => $merchant->id,
        'test_id' => $context['test']->id,
        'title' => 'Test resource',
        'affiliate_url' => 'https://books.example/test',
        'is_active' => true,
    ]);

    $resolver = app(MonetizationResolver::class);

    $testResolved = $resolver->resolve(
        $context['vertical'],
        $context['node'],
        $context['test'],
    );

    expect($testResolved['sponsor']?->id)->toBe($testPlacement->id)
        ->and($testResolved['lead']?->title)->toBe('Test lead')
        ->and($testResolved['affiliate_resources']->first()?->title)->toBe('Test resource');

    $taxonomyResolved = $resolver->resolve(
        $context['vertical'],
        $context['node'],
    );

    expect($taxonomyResolved['sponsor']?->campaign->sponsor->name)->toBe('Node');
});

test('inactive or expired commercial content is not resolved', function () {
    $context = monetizationContext();

    $placement = createSponsorPlacement('Expired', testId: $context['test']->id);
    $placement->campaign->forceFill(['ends_at' => now()->subMinute()])->save();

    $merchant = AffiliateMerchant::create(['name' => 'Books', 'is_active' => true]);
    AffiliateResource::create([
        'affiliate_merchant_id' => $merchant->id,
        'test_id' => $context['test']->id,
        'title' => 'Expired book',
        'affiliate_url' => 'https://books.example/item',
        'is_active' => true,
        'ends_at' => now()->subMinute(),
    ]);

    $resolved = app(MonetizationResolver::class)->resolve(
        $context['vertical'],
        $context['node'],
        $context['test'],
    );

    expect($resolved['sponsor'])->toBeNull()
        ->and($resolved['affiliate_resources'])->toHaveCount(0);
});

test('sponsor and affiliate outbound links are tracked before redirect', function () {
    $context = monetizationContext();
    $placement = createSponsorPlacement('Tracked', testId: $context['test']->id);

    $merchant = AffiliateMerchant::create([
        'name' => 'Book Shop',
        'is_active' => true,
    ]);

    $resource = AffiliateResource::create([
        'affiliate_merchant_id' => $merchant->id,
        'test_id' => $context['test']->id,
        'title' => 'Manual',
        'affiliate_url' => 'https://books.example/manual',
        'is_active' => true,
    ]);

    $this->get(route('sponsor.click', $placement))
        ->assertRedirect($placement->campaign->cta_url)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->get(route('affiliate.click', $resource))
        ->assertRedirect($resource->affiliate_url)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect(MonetizationClick::query()->where('channel', 'sponsor')->count())->toBe(1)
        ->and(MonetizationClick::query()->where('channel', 'affiliate')->count())->toBe(1);
});

test('lead capture requires explicit consent and stores only submitted contact fields', function () {
    $context = monetizationContext();

    $campaign = LeadCampaign::create([
        'test_id' => $context['test']->id,
        'name' => 'Driving School Lead',
        'title' => 'Vrei o ofertă?',
        'requested_fields' => ['name', 'phone'],
        'consent_text' => 'Sunt de acord ca datele mele să fie folosite pentru această solicitare de ofertă.',
        'is_active' => true,
    ]);

    Livewire::test(LeadCapture::class, ['campaign' => $campaign])
        ->set('name', 'Andrei Test')
        ->set('email', 'TEST@EXAMPLE.COM')
        ->set('phone', '0712345678')
        ->call('submit')
        ->assertHasErrors(['consent']);

    expect(LeadSubmission::query()->count())->toBe(0);

    Livewire::test(LeadCapture::class, ['campaign' => $campaign])
        ->set('name', 'Andrei Test')
        ->set('email', 'TEST@EXAMPLE.COM')
        ->set('phone', '0712345678')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $lead = LeadSubmission::query()->firstOrFail();

    expect($lead->email)->toBe('test@example.com')
        ->and($lead->name)->toBe('Andrei Test')
        ->and($lead->phone)->toBe('0712345678')
        ->and($lead->consented_at)->not->toBeNull();
});

test('newsletter uses double opt in and signed unsubscribe flow', function () {
    Mail::fake();

    $context = monetizationContext();

    Livewire::test(NewsletterSignup::class, [
        'interestKey' => 'taxonomy:'.$context['node']->id,
        'verticalId' => $context['vertical']->id,
        'taxonomyNodeId' => $context['node']->id,
        'interestLabel' => $context['node']->name,
    ])
        ->set('email', 'NEWS@EXAMPLE.COM')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('state', 'pending');

    $subscription = NewsletterSubscription::query()->firstOrFail();

    expect($subscription->email)->toBe('news@example.com')
        ->and($subscription->status)->toBe('pending')
        ->and($subscription->confirmed_at)->toBeNull();

    Mail::assertQueued(ConfirmNewsletterSubscription::class);

    $confirmationUrl = URL::temporarySignedRoute(
        'newsletter.confirm',
        now()->addHour(),
        ['subscription' => $subscription->id],
    );

    $this->get($confirmationUrl)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect($subscription->fresh()->status)->toBe('active')
        ->and($subscription->fresh()->confirmed_at)->not->toBeNull();

    $unsubscribeUrl = URL::signedRoute(
        'newsletter.unsubscribe',
        ['subscription' => $subscription->id],
    );

    $this->get($unsubscribeUrl)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect($subscription->fresh()->status)->toBe('unsubscribed')
        ->and($subscription->fresh()->unsubscribed_at)->not->toBeNull();

    $this->get($confirmationUrl)->assertOk();

    expect($subscription->fresh()->status)->toBe('unsubscribed');
});

test('public test pages disclose sponsored and affiliate content without affecting free test access', function () {
    $context = monetizationContext();
    createSponsorPlacement('Visible Sponsor', testId: $context['test']->id);

    $merchant = AffiliateMerchant::create([
        'name' => 'Visible Merchant',
        'is_active' => true,
    ]);

    AffiliateResource::create([
        'affiliate_merchant_id' => $merchant->id,
        'test_id' => $context['test']->id,
        'title' => 'Carte recomandată',
        'affiliate_url' => 'https://books.example/recommended',
        'is_active' => true,
    ]);

    $this->get('/auto/drpciv/categoria-b/simulare-b')
        ->assertOk()
        ->assertSee('Conținut sponsorizat')
        ->assertSee('Visible Sponsor')
        ->assertSee('Unele linkuri sunt linkuri de afiliere')
        ->assertSee('Carte recomandată')
        ->assertSee('Gratuit');
});

test('monetization admin routes require admin access', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    foreach ([
        '/admin/monetizare/sponsori',
        '/admin/monetizare/lead-uri',
        '/admin/monetizare/afiliere',
        '/admin/monetizare/newsletter',
    ] as $url) {
        $this->actingAs($user)->get($url)->assertForbidden();
        $this->actingAs($admin)->get($url)->assertOk();
    }
});
