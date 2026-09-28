<?php

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Enums\TestMode;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Database\Seeders\CatalogSeeder;

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

function stockCategory(string $slug, int $many): TaxonomyNode
{
    $auto = Vertical::query()->where('slug', 'auto')->firstOrFail();
    $node = TaxonomyNode::query()
        ->where('vertical_id', $auto->id)
        ->where('slug', $slug)
        ->firstOrFail();

    foreach (range(1, $many) as $index) {
        $question = Question::create([
            'vertical_id' => $auto->id,
            'taxonomy_node_id' => $node->id,
            'type' => QuestionType::MultipleChoice,
            'status' => PublicationStatus::Published,
            'prompt' => 'Întrebarea '.$index.' pentru '.$slug,
            'difficulty' => 2,
        ]);

        AnswerOption::create([
            'question_id' => $question->id,
            'content' => 'Varianta corectă',
            'is_correct' => true,
            'position' => 0,
        ]);
    }

    return $node;
}

test('the exam test is built with the numbers the law gives, not with ours', function () {
    stockCategory('permis-categoria-b', 30);

    $this->artisan('content:teste-examen', ['verticala' => 'auto'])
        ->expectsOutputToContain('Categoria B — simulare examen')
        ->assertSuccessful();

    $test = TestDefinition::query()->where('slug', 'permis-categoria-b-examen')->firstOrFail();

    expect($test->question_limit)->toBe(26)
        ->and($test->duration_seconds)->toBe(1800)
        ->and($test->passing_questions)->toBe(22)
        ->and($test->max_wrong_answers)->toBe(5)
        ->and($test->mode)->toBe(TestMode::Exam);
});

test('the motorcycle paper is shorter and less forgiving', function () {
    stockCategory('permis-categoria-a', 25);

    $this->artisan('content:teste-examen', ['verticala' => 'auto'])->assertSuccessful();

    $test = TestDefinition::query()->where('slug', 'permis-categoria-a-examen')->firstOrFail();

    expect($test->question_limit)->toBe(20)
        ->and($test->duration_seconds)->toBe(1200)
        ->and($test->passing_questions)->toBe(17)
        ->and($test->max_wrong_answers)->toBe(4);
});

test('a category with no questions yet is skipped, not built empty', function () {
    stockCategory('permis-categoria-b', 5);

    $this->artisan('content:teste-examen', ['verticala' => 'auto'])
        ->expectsOutputToContain('secțiuni sărite')
        ->assertSuccessful();

    $test = TestDefinition::query()->where('slug', 'permis-categoria-b-examen')->firstOrFail();

    // Proba cere 26 de întrebări și le cere în continuare: azi fondul are cinci,
    // mâine are destule, iar testul nu trebuie reconstruit între timp.
    expect(TestDefinition::query()->where('slug', 'permis-categoria-e-examen')->exists())->toBeFalse()
        ->and($test->question_limit)->toBe(26)
        ->and($test->question_pool['include_children'])->toBeTrue();
});
