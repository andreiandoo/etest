<?php

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Livewire\TestRunner;
use App\Models\AnswerOption;
use App\Models\ContentImport;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Content\QuestionImporter;
use App\Services\Testing\AttemptBuilder;
use App\Services\Testing\AttemptEngine;
use Livewire\Livewire;

/**
 * Jumătate dintre chestionarele de legislație rutieră arată un indicator sau o
 * intersecție, iar enunțul singur nu înseamnă nimic fără imagine. Proba de aici
 * o urmărește de la întrebare până în pagina pe care o vede candidatul.
 */
function questionWithMedia(TestDefinition $test, array $media): Question
{
    $question = Question::create([
        'vertical_id' => $test->vertical_id,
        'type' => QuestionType::SingleChoice,
        'status' => PublicationStatus::Published,
        'prompt' => 'Ce obligație aveți la întâlnirea acestui indicator?',
        'media' => $media,
        'difficulty' => 2,
    ]);

    foreach ([['Cedați trecerea', true], ['Opriți obligatoriu', false]] as $position => [$content, $correct]) {
        AnswerOption::create([
            'question_id' => $question->id,
            'content' => $content,
            'is_correct' => $correct,
            'position' => $position,
        ]);
    }

    $test->questions()->attach($question->id, ['position' => 0, 'points' => 1, 'required' => true]);

    return $question;
}

test('the image travels into the attempt snapshot, like the rest of the question', function () {
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create(['question_limit' => 1]);

    questionWithMedia($test, [
        'path' => 'questions/indicator-cedeaza-trecerea.svg',
        'alt' => 'Indicator de prioritate: cedează trecerea',
        'credit' => 'Desen propriu',
        'license' => 'act normativ',
    ]);

    $attempt = app(AttemptBuilder::class)->startOrResume(User::factory()->create(), $test);
    $snapshot = $attempt->questions()->firstOrFail()->question_snapshot;

    expect($snapshot['media']['path'])->toBe('questions/indicator-cedeaza-trecerea.svg')
        ->and($snapshot['media']['alt'])->toBe('Indicator de prioritate: cedează trecerea');
});

test('the candidate sees the image and its alternative text', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create(['question_limit' => 1]);

    questionWithMedia($test, [
        'path' => 'questions/indicator-cedeaza-trecerea.svg',
        'alt' => 'Indicator de prioritate: cedează trecerea',
        'credit' => 'Desen propriu',
        'license' => 'CC BY-SA 4.0',
    ]);

    Livewire::actingAs($user)
        ->test(TestRunner::class, ['vertical' => $vertical, 'test' => $test])
        ->assertSee('questions/indicator-cedeaza-trecerea.svg', false)
        ->assertSee('Indicator de prioritate: cedează trecerea', false)
        ->assertSee('CC BY-SA 4.0', false);
});

test('a question without an image renders no empty frame', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create(['question_limit' => 1]);

    $question = questionWithMedia($test, []);
    $question->forceFill(['media' => null])->save();

    Livewire::actingAs($user)
        ->test(TestRunner::class, ['vertical' => $vertical, 'test' => $test])
        ->assertDontSee('<figure', false);
});

/**
 * La proba teoretică auto se cer 22 de răspunsuri corecte din 26, nu 84,62%.
 * Pragul în întrebări are întâietate, iar pagina de rezultat îl spune în
 * cuvintele examenului.
 */
test('the verdict counts questions when the exam counts questions', function () {
    $user = User::factory()->create();
    $vertical = Vertical::factory()->create();
    $test = TestDefinition::factory()->for($vertical)->create([
        'question_limit' => 4,
        'passing_questions' => 3,
        'passing_percentage' => null,
        'randomize_questions' => false,
        'randomize_options' => false,
    ]);

    foreach (range(0, 3) as $position) {
        $question = Question::create([
            'vertical_id' => $vertical->id,
            'type' => QuestionType::SingleChoice,
            'status' => PublicationStatus::Published,
            'prompt' => 'Întrebarea '.($position + 1),
            'difficulty' => 2,
        ]);

        foreach ([['Greșit', false], ['Corect', true]] as $index => [$content, $correct]) {
            AnswerOption::create([
                'question_id' => $question->id,
                'content' => $content,
                'is_correct' => $correct,
                'position' => $index,
            ]);
        }

        $test->questions()->attach($question->id, ['position' => $position, 'points' => 1, 'required' => true]);
    }

    $attempt = app(AttemptBuilder::class)->startOrResume($user, $test);
    $engine = app(AttemptEngine::class);

    // Trei corecte din patru: sub procent ar fi 75%, dar pragul e în întrebări.
    foreach ($attempt->questions()->orderBy('position')->get() as $index => $question) {
        $options = collect($question->question_snapshot['options']);
        $wanted = $index === 3
            ? $options->firstWhere('is_correct', false)
            : $options->firstWhere('is_correct', true);

        $engine->submit($attempt, $question, ['selected' => [$wanted['id']]]);
    }

    $engine->finish($attempt);

    $this->actingAs($user)
        ->get(route('attempts.results', $attempt))
        ->assertSuccessful()
        ->assertSee('Ai trece examenul')
        ->assertSee('3 răspunsuri corecte din 4');
});

/**
 * Întrebările scrise de noi intră în loturi, iar cele de legislație rutieră vin
 * cu indicatorul lângă enunț. Dacă importul n-ar duce imaginea mai departe,
 * fiecare poză ar trebui pusă de mână, una câte una.
 */
test('an imported batch brings its images along', function () {
    $vertical = Vertical::factory()->create();
    $import = ContentImport::create([
        'vertical_id' => $vertical->id,
        'type' => 'questions',
        'format' => 'csv',
        'original_name' => 'lot.csv',
        'stored_path' => 'imports/lot.csv',
        'status' => 'processing',
        'started_at' => now(),
    ]);

    $media = ['path' => 'questions/b/indicator-stop.svg', 'alt' => 'Indicator STOP', 'license' => 'desen propriu'];

    app(QuestionImporter::class)->import($import, [[
        'source_key' => 'auto:b:0001',
        'type' => 'multiple_choice',
        'prompt' => 'Ce obligație aveți la acest indicator?',
        'media' => json_encode($media, JSON_THROW_ON_ERROR),
        'options' => json_encode([
            ['content' => 'Opriți', 'is_correct' => true],
            ['content' => 'Reduceți viteza', 'is_correct' => false],
        ], JSON_THROW_ON_ERROR),
    ]]);

    $question = Question::query()->where('source_key', 'auto:b:0001')->firstOrFail();

    // jsonb nu păstrează ordinea cheilor, deci se compară conținutul.
    expect($question->media)->toEqual($media);

    // Un al doilea lot, care corectează doar enunțul, nu șterge imaginea.
    app(QuestionImporter::class)->import($import, [[
        'source_key' => 'auto:b:0001',
        'type' => 'multiple_choice',
        'prompt' => 'Ce obligație aveți la întâlnirea acestui indicator?',
        'options' => json_encode([
            ['content' => 'Opriți', 'is_correct' => true],
            ['content' => 'Reduceți viteza', 'is_correct' => false],
        ], JSON_THROW_ON_ERROR),
    ]]);

    expect($question->refresh()->media)->toEqual($media)
        ->and($question->prompt)->toBe('Ce obligație aveți la întâlnirea acestui indicator?');
});
