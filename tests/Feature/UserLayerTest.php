<?php

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Livewire\User\Dashboard as UserDashboard;
use App\Livewire\User\FavoriteToggle;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserStat;
use App\Models\UserXpEvent;
use App\Models\Vertical;
use App\Services\Testing\AttemptBuilder;
use App\Services\Testing\AttemptEngine;
use App\Services\User\UserInsights;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function m5AttachChoiceQuestion(
    TestDefinition $test,
    int $position,
    string $prompt,
    ?TaxonomyNode $taxonomyNode = null,
): Question {
    $question = Question::create([
        'vertical_id' => $test->vertical_id,
        'taxonomy_node_id' => $taxonomyNode?->id,
        'type' => QuestionType::SingleChoice,
        'status' => PublicationStatus::Published,
        'prompt' => $prompt,
        'difficulty' => 2,
    ]);

    $wrong = AnswerOption::create([
        'question_id' => $question->id,
        'content' => 'Greșit',
        'is_correct' => false,
        'position' => 0,
    ]);

    $correct = AnswerOption::create([
        'question_id' => $question->id,
        'content' => 'Corect',
        'is_correct' => true,
        'position' => 1,
    ]);

    $test->questions()->attach($question->id, [
        'position' => $position,
        'points' => 1,
        'required' => true,
    ]);

    $question->setRelation('options', collect([$wrong, $correct]));

    return $question;
}

function m5CompleteAttempt(User $user, TestDefinition $test, bool $correct = true): void
{
    $attempt = app(AttemptBuilder::class)->startOrResume($user, $test);
    $engine = app(AttemptEngine::class);

    foreach ($attempt->questions()->get() as $attemptQuestion) {
        $options = collect($attemptQuestion->question_snapshot['options']);
        $option = $options->firstWhere('is_correct', $correct);

        $engine->submit(
            $attempt->fresh(),
            $attemptQuestion,
            ['selected' => $option['id']],
        );
    }

    $engine->finish($attempt->fresh());
}

test('completion awards xp exactly once and unlocks achievements', function () {
    $user = User::factory()->create(['timezone' => 'Europe/Bucharest']);
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create([
        'passing_percentage' => 60,
    ]);

    m5AttachChoiceQuestion($test, 0, 'XP question');
    m5CompleteAttempt($user, $test, true);

    $stats = UserStat::query()->where('user_id', $user->id)->firstOrFail();

    expect($stats->xp)->toBe(110)
        ->and($stats->completed_attempts)->toBe(1)
        ->and($stats->current_streak)->toBe(1)
        ->and(UserXpEvent::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(UserAchievement::query()->where('user_id', $user->id)->pluck('achievement_key')->all())
        ->toContain('first_test', 'perfect_score', 'xp_100');

    $attempt = $user->attempts()->firstOrFail();
    app(AttemptEngine::class)->finish($attempt->fresh());

    expect(UserStat::query()->findOrFail($user->id)->xp)->toBe(110)
        ->and(UserXpEvent::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('streak increments across consecutive local calendar days', function () {
    $user = User::factory()->create(['timezone' => 'Europe/Bucharest']);
    $vertical = Vertical::factory()->create();

    Carbon::setTestNow('2026-09-18 18:00:00');

    $first = TestDefinition::factory()->for($vertical)->create();
    m5AttachChoiceQuestion($first, 0, 'Day one');
    m5CompleteAttempt($user, $first);

    Carbon::setTestNow('2026-09-19 18:00:00');

    $second = TestDefinition::factory()->for($vertical)->create();
    m5AttachChoiceQuestion($second, 0, 'Day two');
    m5CompleteAttempt($user, $second);

    $stats = UserStat::query()->findOrFail($user->id);

    expect($stats->current_streak)->toBe(2)
        ->and($stats->longest_streak)->toBe(2);

    Carbon::setTestNow();
});

test('weak areas aggregate incorrect answers by taxonomy node', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $node = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'type' => TaxonomyNodeType::Subject,
        'name' => 'Prioritate',
        'slug' => 'prioritate',
        'is_active' => true,
    ]);

    $test = TestDefinition::factory()->for($vertical)->create([
        'taxonomy_node_id' => $node->id,
    ]);

    m5AttachChoiceQuestion($test, 0, 'Weak 1', $node);
    m5AttachChoiceQuestion($test, 1, 'Weak 2', $node);
    m5CompleteAttempt($user, $test, false);

    $areas = app(UserInsights::class)->weakAreas($user);

    expect($areas)->toHaveCount(1)
        ->and($areas[0]['name'])->toBe('Prioritate')
        ->and($areas[0]['answered_count'])->toBe(2)
        ->and($areas[0]['accuracy'])->toBe(0.0);
});

test('users can add and remove favorite tests', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create();

    Livewire::actingAs($user)
        ->test(FavoriteToggle::class, ['test' => $test])
        ->assertSet('favorite', false)
        ->call('toggle')
        ->assertSet('favorite', true);

    expect($user->favoriteTests()->whereKey($test->id)->exists())->toBeTrue();

    Livewire::actingAs($user)
        ->test(FavoriteToggle::class, ['test' => $test])
        ->call('toggle')
        ->assertSet('favorite', false);

    expect($user->favoriteTests()->whereKey($test->id)->exists())->toBeFalse();
});

test('leaderboard exposes only users who explicitly opted in', function () {
    $viewer = User::factory()->create();
    $visible = User::factory()->create(['name' => 'Visible Account']);
    $hidden = User::factory()->create(['name' => 'Hidden Account']);

    UserStat::create([
        'user_id' => $visible->id,
        'xp' => 500,
        'completed_attempts' => 10,
        'leaderboard_opt_in' => true,
        'leaderboard_display_name' => 'Public Alias',
    ]);

    UserStat::create([
        'user_id' => $hidden->id,
        'xp' => 900,
        'completed_attempts' => 20,
        'leaderboard_opt_in' => false,
        'leaderboard_display_name' => 'Should Not Appear',
    ]);

    $this->actingAs($viewer)
        ->get('/clasament')
        ->assertOk()
        ->assertSee('Public Alias')
        ->assertDontSee('Hidden Account')
        ->assertDontSee('Should Not Appear')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('dashboard and history are private user surfaces', function () {
    $this->get('/panou')->assertRedirect('/autentificare');
    $this->get('/istoric')->assertRedirect('/autentificare');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/panou')
        ->assertOk()
        ->assertSee('Progresul tău')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->actingAs($user)
        ->get('/istoric')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('expired attempts do not award xp or streaks', function () {
    Carbon::setTestNow('2026-09-19 12:00:00');

    $user = User::factory()->create(['timezone' => 'Europe/Bucharest']);
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create([
        'duration_seconds' => 60,
    ]);

    m5AttachChoiceQuestion($test, 0, 'Expired reward test');

    $attempt = app(AttemptBuilder::class)->startOrResume($user, $test);

    Carbon::setTestNow('2026-09-19 12:02:00');

    app(AttemptEngine::class)->finish($attempt->fresh(), 'expired');

    expect(UserXpEvent::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(UserStat::query()->where('user_id', $user->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});

test('leaderboard participation is persisted only after explicit opt in', function () {
    $user = User::factory()->create(['name' => 'Private Name']);

    Livewire::actingAs($user)
        ->test(UserDashboard::class)
        ->assertSet('leaderboardOptIn', false)
        ->set('leaderboardOptIn', true)
        ->set('leaderboardDisplayName', 'Public Learner')
        ->call('saveLeaderboardSettings')
        ->assertHasNoErrors();

    $stats = UserStat::query()->findOrFail($user->id);

    expect($stats->leaderboard_opt_in)->toBeTrue()
        ->and($stats->leaderboard_display_name)->toBe('Public Learner');
});
