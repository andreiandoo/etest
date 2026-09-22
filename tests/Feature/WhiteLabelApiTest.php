<?php

use App\Enums\AttemptStatus;
use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Enums\TestMode;
use App\Http\Middleware\EnforceApiQuota;
use App\Models\AnswerOption;
use App\Models\ApiClient;
use App\Models\ApiUsageDaily;
use App\Models\Question;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TestAttempt;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Api\ApiKeyIssuer;
use Illuminate\Http\Request;

function m7TenantWithVertical(string $host = 'partner.test'): array
{
    $allowed = Vertical::factory()->create([
        'name' => 'Auto',
        'slug' => 'auto',
    ]);

    $blocked = Vertical::factory()->create([
        'name' => 'Medicină',
        'slug' => 'medicina',
    ]);

    $tenant = Tenant::create([
        'name' => 'Partner Academy',
        'slug' => 'partner-academy',
        'is_active' => true,
        'branding' => [
            'site_name' => 'Partner Tests',
            'primary_color' => '#123456',
            'tagline' => 'Pregătire oferită de Partner Academy',
            'footer_text' => 'Platformă de testare Partner Academy',
            'seo_title' => 'Partner Tests — pregătire online',
            'seo_description' => 'Teste white-label pentru cursanții Partner Academy.',
        ],
    ]);

    TenantDomain::create([
        'tenant_id' => $tenant->id,
        'host' => $host,
        'is_primary' => true,
        'is_active' => true,
    ]);

    $tenant->verticals()->attach($allowed->id);

    return compact('tenant', 'allowed', 'blocked');
}

function m7PublishedTest(Vertical $vertical, string $slug = 'simulare'): TestDefinition
{
    return TestDefinition::factory()->for($vertical)->create([
        'title' => 'Simulare API '.$slug,
        'slug' => $slug,
        'status' => PublicationStatus::Published,
        'published_at' => now()->subMinute(),
    ]);
}

function m7AttachQuestion(TestDefinition $test): Question
{
    $question = Question::create([
        'vertical_id' => $test->vertical_id,
        'type' => QuestionType::SingleChoice,
        'status' => PublicationStatus::Published,
        'prompt' => 'Care este varianta corectă?',
        'explanation' => 'Explicația corectă.',
        'difficulty' => 2,
        'answer_config' => ['mode' => 'single'],
    ]);

    AnswerOption::create([
        'question_id' => $question->id,
        'content' => 'Greșit',
        'is_correct' => false,
        'position' => 0,
        'feedback' => 'Nu.',
    ]);

    AnswerOption::create([
        'question_id' => $question->id,
        'content' => 'Corect',
        'is_correct' => true,
        'position' => 1,
        'feedback' => 'Da.',
    ]);

    $test->questions()->attach($question->id, [
        'position' => 0,
        'points' => 1,
        'required' => true,
    ]);

    return $question;
}

function m7IssueKey(
    ApiClient $client,
    array $scopes,
    ?int $dailyQuota = 100,
    ?int $monthlyQuota = 1000,
): array {
    return app(ApiKeyIssuer::class)->issue(
        $client,
        'Test key',
        $scopes,
        $dailyQuota,
        $monthlyQuota,
    );
}

test('white label host applies tenant branding and hides unassigned verticals', function () {
    $context = m7TenantWithVertical();

    m7PublishedTest($context['allowed']);
    m7PublishedTest($context['blocked'], 'rezidentiat');

    $this->get('http://partner.test/')
        ->assertOk()
        ->assertSee('Partner Tests')
        ->assertSee('Auto')
        ->assertDontSee('Medicină')
        ->assertSee('#123456');

    $this->get('http://partner.test/auto')
        ->assertOk();

    $this->get('http://partner.test/medicina')
        ->assertNotFound();

    $this->get('http://partner.test/medicina/rezidentiat')
        ->assertNotFound();

    $this->get('http://partner.test/sitemaps/domenii.xml')
        ->assertOk()
        ->assertSee('/auto')
        ->assertDontSee('/medicina');
});

test('inactive white label domain or tenant cannot serve content', function () {
    $context = m7TenantWithVertical('inactive.test');

    TenantDomain::query()
        ->where('host', 'inactive.test')
        ->update(['is_active' => false]);

    $this->get('http://inactive.test/')
        ->assertNotFound();

    TenantDomain::query()
        ->where('host', 'inactive.test')
        ->update(['is_active' => true]);

    $context['tenant']->update(['is_active' => false]);

    $this->get('http://inactive.test/')
        ->assertNotFound();
});

test('api keys are stored hashed and plaintext is returned only when issued', function () {
    $client = ApiClient::create([
        'name' => 'Partner API',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, ['catalog:read']);

    expect($issued['plain_text'])->toStartWith('et_live_')
        ->and($issued['key']->key_hash)->toBe(hash('sha256', $issued['plain_text']))
        ->and($issued['key']->key_hash)->not->toBe($issued['plain_text'])
        ->and($issued['key']->toArray())->not->toHaveKey('key_hash');

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertOk()
        ->assertJsonPath('data.client.name', 'Partner API');

    $issued['key']->forceFill(['revoked_at' => now()])->save();

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertUnauthorized();
});

test('api scopes protect catalog questions and correct answers independently', function () {
    $vertical = Vertical::factory()->create([
        'name' => 'Auto',
        'slug' => 'auto',
    ]);
    $test = m7PublishedTest($vertical);
    m7AttachQuestion($test);

    $client = ApiClient::create([
        'name' => 'Scoped client',
        'is_active' => true,
    ]);

    $catalogOnly = m7IssueKey($client, ['catalog:read']);

    $this->withToken($catalogOnly['plain_text'])
        ->getJson('/api/v1/verticals')
        ->assertOk()
        ->assertJsonFragment(['slug' => 'auto']);

    $this->withToken($catalogOnly['plain_text'])
        ->getJson('/api/v1/tests/'.$test->id.'/questions')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');

    $questionsKey = m7IssueKey($client, ['questions:read']);

    $response = $this->withToken($questionsKey['plain_text'])
        ->getJson('/api/v1/tests/'.$test->id.'/questions')
        ->assertOk()
        ->assertJsonPath('meta.answers_included', false);

    $payload = $response->json();

    expect(json_encode($payload))
        ->not->toContain('is_correct')
        ->not->toContain('answer_config')
        ->not->toContain('Explicația corectă');

    $answersKey = m7IssueKey($client, ['questions:read', 'answers:read']);

    $responseWithAnswers = $this->withToken($answersKey['plain_text'])
        ->getJson('/api/v1/tests/'.$test->id.'/questions')
        ->assertOk()
        ->assertJsonPath('meta.answers_included', true);

    $answerPayload = $responseWithAnswers->json();

    expect($answerPayload['data'][0]['answer_config'])->toBe(['mode' => 'single'])
        ->and($answerPayload['data'][0]['explanation'])->toBe('Explicația corectă.')
        ->and(collect($answerPayload['data'][0]['options'])->pluck('is_correct')->all())
        ->toContain(true, false);
});

test('tenant bound api clients cannot access verticals outside their tenant', function () {
    $context = m7TenantWithVertical();
    $allowedTest = m7PublishedTest($context['allowed']);
    $blockedTest = m7PublishedTest($context['blocked'], 'blocked-test');

    $client = ApiClient::create([
        'tenant_id' => $context['tenant']->id,
        'name' => 'Tenant API',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, ['catalog:read', 'tests:read']);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/verticals')
        ->assertOk()
        ->assertJsonFragment(['slug' => 'auto'])
        ->assertJsonMissing(['slug' => 'medicina']);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/tests/'.$allowedTest->id)
        ->assertOk();

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/tests/'.$blockedTest->id)
        ->assertNotFound();

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/verticals/medicina/tests')
        ->assertNotFound();
});

test('daily api quota is enforced and usage is metered', function () {
    $client = ApiClient::create([
        'name' => 'Quota client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, [], dailyQuota: 2, monthlyQuota: 100);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertOk()
        ->assertHeader('X-RateLimit-Daily-Limit', '2')
        ->assertHeader('X-RateLimit-Daily-Used', '1');

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertOk()
        ->assertHeader('X-RateLimit-Daily-Used', '2');

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'quota_exceeded');

    $usage = ApiUsageDaily::query()
        ->where('api_key_id', $issued['key']->id)
        ->whereDate('usage_date', today())
        ->firstOrFail();

    expect($usage->request_count)->toBe(2)
        ->and($usage->response_bytes)->toBeGreaterThan(0)
        ->and($usage->error_count)->toBe(0);
});

test('monthly api quota is enforced independently from daily quota', function () {
    $client = ApiClient::create([
        'name' => 'Monthly client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, [], dailyQuota: 100, monthlyQuota: 1);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertOk();

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertStatus(429)
        ->assertJsonPath('error.message', 'Monthly API quota exceeded.');
});

test('api usage meter aggregates admitted authorization errors', function () {
    $vertical = Vertical::factory()->create([
        'name' => 'Metered Scope',
        'slug' => 'metered-scope',
    ]);

    $client = ApiClient::create([
        'name' => 'Error client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, ['catalog:read']);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/verticals/'.$vertical->slug.'/tests')
        ->assertForbidden();

    $usage = ApiUsageDaily::query()
        ->where('api_key_id', $issued['key']->id)
        ->firstOrFail();

    expect($usage->request_count)->toBe(1)
        ->and($usage->error_count)->toBe(1)
        ->and($usage->response_bytes)->toBeGreaterThan(0);
});

test('white label and api admin routes require administrator access', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    foreach (['/admin/marca-proprie', '/admin/api'] as $url) {
        $this->actingAs($user)->get($url)->assertForbidden();
        $this->actingAs($admin)->get($url)->assertOk();
    }
});

test('api keys bound to an inactive tenant are rejected', function () {
    $context = m7TenantWithVertical('api-inactive.test');

    $client = ApiClient::create([
        'tenant_id' => $context['tenant']->id,
        'name' => 'Inactive tenant client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, ['catalog:read']);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertOk();

    $context['tenant']->update(['is_active' => false]);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertUnauthorized();
});

test('main application host remains unrestricted without a matching tenant domain', function () {
    $first = Vertical::factory()->create([
        'name' => 'Auto Main',
        'slug' => 'auto-main',
    ]);

    $second = Vertical::factory()->create([
        'name' => 'Drept Main',
        'slug' => 'drept-main',
    ]);

    $this->withHeader('Host', 'localhost')
        ->get('/')
        ->assertOk()
        ->assertSee($first->name)
        ->assertSee($second->name);
});

test('white label history and result pages hide unassigned vertical content', function () {
    $context = m7TenantWithVertical('private-partner.test');
    $allowedTest = m7PublishedTest($context['allowed'], 'allowed-test');
    $blockedTest = m7PublishedTest($context['blocked'], 'blocked-test');
    $user = User::factory()->create(['timezone' => 'Europe/Bucharest']);

    $allowedAttempt = TestAttempt::create([
        'user_id' => $user->id,
        'test_id' => $allowedTest->id,
        'status' => AttemptStatus::Completed,
        'mode' => TestMode::Practice,
        'seed' => 7001,
        'started_at' => now()->subMinutes(10),
        'completed_at' => now()->subMinutes(5),
        'score' => 8,
        'max_score' => 10,
        'percentage' => 80,
        'duration_seconds' => 300,
        'current_position' => 0,
        'configuration' => ['completion_reason' => 'submitted'],
    ]);

    $blockedAttempt = TestAttempt::create([
        'user_id' => $user->id,
        'test_id' => $blockedTest->id,
        'status' => AttemptStatus::Completed,
        'mode' => TestMode::Practice,
        'seed' => 7002,
        'started_at' => now()->subMinutes(20),
        'completed_at' => now()->subMinutes(15),
        'score' => 9,
        'max_score' => 10,
        'percentage' => 90,
        'duration_seconds' => 300,
        'current_position' => 0,
        'configuration' => ['completion_reason' => 'submitted'],
    ]);

    $this->actingAs($user)
        ->get('http://private-partner.test/istoric')
        ->assertOk()
        ->assertSee($allowedTest->title)
        ->assertDontSee($blockedTest->title);

    $this->actingAs($user)
        ->get('http://private-partner.test/rezultate/'.$allowedAttempt->id)
        ->assertOk();

    $this->actingAs($user)
        ->get('http://private-partner.test/rezultate/'.$blockedAttempt->id)
        ->assertNotFound();
});

test('expired api keys are rejected and x api key header is supported', function () {
    $client = ApiClient::create([
        'name' => 'Header client',
        'is_active' => true,
    ]);

    $active = m7IssueKey($client, ['catalog:read']);

    $this->withHeader('X-API-Key', $active['plain_text'])
        ->getJson('/api/v1/verticals')
        ->assertOk();

    $expired = app(ApiKeyIssuer::class)->issue(
        $client,
        'Expired key',
        ['catalog:read'],
        100,
        1000,
        now()->subMinute(),
    );

    $this->withToken($expired['plain_text'])
        ->getJson('/api/v1/status')
        ->assertUnauthorized();
});

test('api authentication rejects missing and unknown credentials', function () {
    $this->getJson('/api/v1/status')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthorized');

    $this->withToken('et_live_unknown_invalid_secret')
        ->getJson('/api/v1/status')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthorized');
});

test('answers read alone cannot bypass the questions read scope', function () {
    $vertical = Vertical::factory()->create([
        'name' => 'Scope Dependency',
        'slug' => 'scope-dependency',
    ]);

    $test = m7PublishedTest($vertical, 'scope-dependency-test');
    m7AttachQuestion($test);

    $client = ApiClient::create([
        'name' => 'Answers only client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, ['answers:read']);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/tests/'.$test->id.'/questions')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});

test('api key issuer rejects unsupported scopes and inactive owners', function () {
    $issuer = app(ApiKeyIssuer::class);

    $client = ApiClient::create([
        'name' => 'Strict scope client',
        'is_active' => true,
    ]);

    expect(fn () => $issuer->issue($client, 'Wildcard', ['*']))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $issuer->issue($client, 'Unknown', ['admin:all']))
        ->toThrow(InvalidArgumentException::class);

    $client->update(['is_active' => false]);

    expect(fn () => $issuer->issue($client->fresh(), 'Inactive client', ['catalog:read']))
        ->toThrow(InvalidArgumentException::class);

    $context = m7TenantWithVertical('issuer-inactive.test');
    $tenantClient = ApiClient::create([
        'tenant_id' => $context['tenant']->id,
        'name' => 'Inactive tenant owner',
        'is_active' => true,
    ]);

    $context['tenant']->update(['is_active' => false]);

    expect(fn () => $issuer->issue($tenantClient, 'Inactive tenant', ['catalog:read']))
        ->toThrow(InvalidArgumentException::class);
});

test('unlimited quota does not advertise a zero limit', function () {
    $client = ApiClient::create([
        'name' => 'Unlimited client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, [], dailyQuota: null, monthlyQuota: null);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertOk()
        ->assertHeaderMissing('X-RateLimit-Daily-Limit')
        ->assertHeaderMissing('X-RateLimit-Monthly-Limit')
        ->assertHeader('X-RateLimit-Daily-Used', '1')
        ->assertHeader('X-RateLimit-Monthly-Used', '1');
});

test('quota rejection returns current usage and configured limits without consuming another request', function () {
    $client = ApiClient::create([
        'name' => 'Boundary client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, [], dailyQuota: 1, monthlyQuota: 10);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertOk();

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertStatus(429)
        ->assertHeader('X-RateLimit-Daily-Limit', '1')
        ->assertHeader('X-RateLimit-Daily-Used', '1')
        ->assertHeader('X-RateLimit-Monthly-Limit', '10')
        ->assertHeader('X-RateLimit-Monthly-Used', '1');

    $usage = ApiUsageDaily::query()
        ->where('api_key_id', $issued['key']->id)
        ->firstOrFail();

    expect($usage->request_count)->toBe(1);
});

test('monthly quota includes usage from earlier days in the same month', function () {
    $client = ApiClient::create([
        'name' => 'Month aggregation client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, [], dailyQuota: 100, monthlyQuota: 2);

    ApiUsageDaily::create([
        'api_key_id' => $issued['key']->id,
        'usage_date' => now()->startOfMonth()->toDateString(),
        'request_count' => 1,
        'response_bytes' => 50,
        'error_count' => 0,
    ]);

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertOk()
        ->assertHeader('X-RateLimit-Monthly-Used', '2');

    $this->withToken($issued['plain_text'])
        ->getJson('/api/v1/status')
        ->assertStatus(429)
        ->assertJsonPath('error.message', 'Monthly API quota exceeded.');
});

test('admitted exceptions are still metered as API errors', function () {
    $client = ApiClient::create([
        'name' => 'Exception metering client',
        'is_active' => true,
    ]);

    $issued = m7IssueKey($client, [], dailyQuota: 10, monthlyQuota: 100);

    $request = Request::create('/api/v1/failing-test', 'GET');
    $request->attributes->set('api_key', $issued['key']);

    expect(fn () => app(EnforceApiQuota::class)->handle(
        $request,
        fn () => throw new RuntimeException('Simulated endpoint failure'),
    ))->toThrow(RuntimeException::class, 'Simulated endpoint failure');

    $usage = ApiUsageDaily::query()
        ->where('api_key_id', $issued['key']->id)
        ->firstOrFail();

    expect($usage->request_count)->toBe(1)
        ->and($usage->error_count)->toBe(1)
        ->and($usage->response_bytes)->toBe(0);
});
