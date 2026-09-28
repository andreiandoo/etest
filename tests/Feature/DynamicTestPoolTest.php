<?php

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Testing\AttemptBuilder;

/**
 * La chestionarele auto nu există „testul numărul 7”: există proba, iar
 * întrebările se trag altele la fiecare accesare. Testul nu ține o listă, ci
 * locul din care trage.
 */
function poolSection(int $questions, string $slug = 'permis-categoria-b'): TaxonomyNode
{
    $vertical = Vertical::factory()->create();
    $section = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'type' => TaxonomyNodeType::Exam,
        'name' => 'Categoria B',
        'slug' => $slug,
    ]);

    $chapter = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'parent_id' => $section->id,
        'type' => TaxonomyNodeType::Chapter,
        'name' => 'Prioritate de trecere',
        'slug' => 'prioritate-de-trecere',
    ]);

    foreach (range(1, $questions) as $index) {
        addPoolQuestion($chapter, 'Întrebarea '.$index);
    }

    return $section;
}

function addPoolQuestion(TaxonomyNode $node, string $prompt): Question
{
    $question = Question::create([
        'vertical_id' => $node->vertical_id,
        'taxonomy_node_id' => $node->id,
        'type' => QuestionType::SingleChoice,
        'status' => PublicationStatus::Published,
        'prompt' => $prompt,
        'difficulty' => 2,
    ]);

    foreach ([['Greșit', false], ['Corect', true]] as $position => [$content, $correct]) {
        AnswerOption::create([
            'question_id' => $question->id,
            'content' => $content,
            'is_correct' => $correct,
            'position' => $position,
        ]);
    }

    return $question;
}

test('a dynamic test keeps no list of questions, only the place it draws from', function () {
    $section = poolSection(40);

    $test = app(PracticeTestBuilder::class)->build(
        $section, 'categoria-b-examen', 'Simulare', 'Proba', 26,
        includeChildren: true, dynamic: true,
    );

    expect($test)->not->toBeNull()
        ->and($test->questions()->count())->toBe(0)
        ->and($test->question_limit)->toBe(26)
        ->and($test->question_pool['node'])->toBe($section->id)
        ->and($test->question_pool['include_children'])->toBeTrue();
});

test('every attempt draws a fresh set from the section', function () {
    $section = poolSection(40);
    $test = app(PracticeTestBuilder::class)->build(
        $section, 'categoria-b-examen', 'Simulare', 'Proba', 26,
        includeChildren: true, dynamic: true,
    );
    $test->forceFill(['status' => PublicationStatus::Published, 'published_at' => now()->subDay()])->save();

    $builder = app(AttemptBuilder::class);
    $first = $builder->startOrResume(User::factory()->create(), $test);
    $second = $builder->startOrResume(User::factory()->create(), $test);

    $drawn = fn ($attempt) => $attempt->questions()->orderBy('position')->pluck('question_id')->all();

    expect($drawn($first))->toHaveCount(26)
        ->and($drawn($second))->toHaveCount(26)
        ->and($drawn($first))->not->toBe($drawn($second));
});

test('a question added today enters the papers without rebuilding anything', function () {
    $section = poolSection(30);
    $test = app(PracticeTestBuilder::class)->build(
        $section, 'categoria-b-examen', 'Simulare', 'Proba', 26,
        includeChildren: true, dynamic: true,
    );

    $chapter = $section->children()->firstOrFail();
    $fresh = addPoolQuestion($chapter, 'Întrebarea adăugată azi');

    $seen = collect(range(1, 12))
        ->map(fn () => app(AttemptBuilder::class)->startOrResume(User::factory()->create(), $test))
        ->flatMap(fn ($attempt) => $attempt->questions()->pluck('question_id'))
        ->unique();

    expect($seen)->toContain($fresh->id);
});

test('a fixed test still keeps its own list, untouched', function () {
    $section = poolSection(10);

    $test = app(PracticeTestBuilder::class)->build($section, 'lot-fix', 'Test', 'Fix', 10, includeChildren: true);

    expect($test->question_pool)->toBeNull()
        ->and($test->questions()->count())->toBe(10);

    addPoolQuestion($section->children()->firstOrFail(), 'Întrebare adăugată după construire');

    expect($test->refresh()->questions()->count())->toBe(10);
});

test('the public page counts the section, not an empty list', function () {
    $section = poolSection(40);
    $test = app(PracticeTestBuilder::class)->build(
        $section, 'categoria-b-examen', 'Simulare', 'Proba probei', 26,
        includeChildren: true, dynamic: true,
    );
    $test->forceFill([
        'status' => PublicationStatus::Published,
        'published_at' => now()->subDay(),
        'passing_questions' => 22,
        'max_wrong_answers' => 5,
    ])->save();

    $vertical = $section->vertical;
    $vertical->forceFill(['is_active' => true])->save();
    TaxonomyNode::query()->where('vertical_id', $vertical->id)->update(['is_active' => true]);

    $this->get(app(PublicUrlGenerator::class)->test($test->refresh()))
        ->assertSuccessful()
        ->assertSee('26 întrebări')
        ->assertSee('se închide la 5 greșeli')
        ->assertSee('prag 22 din 26');
});
