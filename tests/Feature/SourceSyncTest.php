<?php

use App\Enums\PublicationStatus;
use App\Models\ContentImport;
use App\Models\Question;
use App\Models\Source;
use App\Models\SourceDocument;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(CatalogSeeder::class);
});

function syncAncom(): PendingCommand
{
    return test()->artisan('sources:sync', [
        'sursa' => 'ancom-radioamator',
        '--fisier' => base_path('tests/Fixtures/ancom-radiotehnica-excerpt.txt'),
    ]);
}

test('the sync registers the source with its rights and its version', function () {
    syncAncom()->assertSuccessful();

    $source = Source::query()->where('key', 'ancom-radioamator')->firstOrFail();

    expect($source->authority)->toBe('ANCOM')
        ->and($source->rights_status)->toBe('explicit_allowed')
        ->and($source->version_label)->toBe('2017-02-09')
        ->and($source->review_status)->toBe('publishable')
        ->and($source->item_count_raw)->toBe(7)
        ->and($source->last_synced_at)->not->toBeNull();
});

test('what the parser read cleanly goes live, what it rejected never arrives', function () {
    syncAncom()->assertSuccessful();

    expect(Question::query()->count())->toBe(6)
        ->and(Question::query()->where('status', PublicationStatus::Published->value)->count())->toBe(6)
        ->and(Question::query()->where('source_key', 'ancom-radio:01B44')->exists())->toBeFalse();
});

test('with --ciorne nothing is published', function () {
    test()->artisan('sources:sync', [
        'sursa' => 'ancom-radioamator',
        '--fisier' => base_path('tests/Fixtures/ancom-radiotehnica-excerpt.txt'),
        '--ciorne' => true,
    ])->assertSuccessful();

    expect(Question::query()->where('status', PublicationStatus::Draft->value)->count())->toBe(6)
        ->and(TestDefinition::query()->where('status', PublicationStatus::Draft->value)->count())->toBe(5);
});

test('a second run publishes what an earlier draft run left behind', function () {
    test()->artisan('sources:sync', [
        'sursa' => 'ancom-radioamator',
        '--fisier' => base_path('tests/Fixtures/ancom-radiotehnica-excerpt.txt'),
        '--ciorne' => true,
    ])->assertSuccessful();

    syncAncom()->assertSuccessful();

    expect(Question::query()->where('status', PublicationStatus::Published->value)->count())->toBe(6)
        ->and(TestDefinition::query()->where('status', PublicationStatus::Published->value)->count())->toBe(5);
});

test('a re-import never pulls published content back to draft', function () {
    syncAncom()->assertSuccessful();

    test()->artisan('sources:sync', [
        'sursa' => 'ancom-radioamator',
        '--fisier' => base_path('tests/Fixtures/ancom-radiotehnica-excerpt.txt'),
        '--forteaza' => true,
    ])->assertSuccessful();

    expect(Question::query()->where('status', PublicationStatus::Published->value)->count())->toBe(6)
        ->and(Question::query()->where('status', PublicationStatus::Draft->value)->count())->toBe(0);
});

test('a question keeps the answer, the source label and the code it came from', function () {
    syncAncom()->assertSuccessful();

    $question = Question::query()
        ->where('source_key', 'ancom-radio:01A11')
        ->with('options')
        ->firstOrFail();

    expect($question->options)->toHaveCount(4)
        ->and($question->options->where('is_correct', true))->toHaveCount(1)
        ->and($question->options->firstWhere('is_correct', true)?->content)->toBe('Un câmp electric mare')
        ->and($question->source_label)->toContain('2017-02-09')
        ->and($question->source_url)->toContain('ancom.ro')
        ->and($question->source_checked_at?->toDateString())->toBe(today()->toDateString())
        ->and(data_get($question->metadata, 'ancom_code'))->toBe('01A11')
        ->and(data_get($question->metadata, 'difficulty_letter'))->toBe('A');
});

test('the chapters of the document become sections under the exam', function () {
    syncAncom()->assertSuccessful();

    $parent = TaxonomyNode::query()->where('slug', 'radiotehnica-si-electronica')->firstOrFail();

    expect(TaxonomyNode::query()->where('parent_id', $parent->id)->count())->toBe(9);

    $question = Question::query()->where('source_key', 'ancom-radio:01D34')->firstOrFail();
    $chapter = TaxonomyNode::query()->findOrFail($question->taxonomy_node_id);

    expect($chapter->slug)->toBe('circuite')
        ->and($chapter->parent_id)->toBe($parent->id);
});

test('subjects the document itself got wrong are reported, not guessed', function () {
    syncAncom()->assertSuccessful();

    $import = ContentImport::query()->latest('id')->firstOrFail();

    expect($import->status)->toBe('completed_with_errors')
        ->and($import->total_rows)->toBe(7)
        ->and($import->created_rows)->toBe(6)
        ->and($import->failed_rows)->toBe(1)
        ->and(json_encode($import->errors, JSON_UNESCAPED_UNICODE))->toContain('01B44');
});

test('the import leaves behind practice tests, published', function () {
    syncAncom()->assertSuccessful();

    $tests = TestDefinition::query()->get();

    // Extrasul atinge trei capitole, plus cele două teste pe clasă de certificat.
    expect($tests)->toHaveCount(5)
        ->and($tests->where('status', PublicationStatus::Published)->count())->toBe(5);

    $classThree = TestDefinition::query()->where('slug', 'clasa-a-iii-a')->firstOrFail();

    expect($classThree->questions()->count())->toBe(3)
        ->and($classThree->questions()->where('difficulty', '>', 2)->count())->toBe(0)
        ->and($classThree->randomize_questions)->toBeTrue();

    $classTwo = TestDefinition::query()->where('slug', 'clasa-a-ii-a')->firstOrFail();

    expect($classTwo->questions()->count())->toBe(6);
});

test('the original document is kept with its fingerprint', function () {
    syncAncom()->assertSuccessful();

    $document = SourceDocument::query()->firstOrFail();

    expect($document->sha256)->toHaveLength(64)
        ->and($document->byte_size)->toBeGreaterThan(0)
        ->and($document->item_count)->toBe(7)
        ->and($document->imported_count)->toBe(6);

    Storage::disk('local')->assertExists($document->storage_path);
    Storage::disk('local')->assertExists($document->text_path);
});

test('running it again on an unchanged document does not import a second time', function () {
    syncAncom()->assertSuccessful();
    syncAncom()->expectsOutputToContain('Documentul nu s-a schimbat')->assertSuccessful();

    expect(Question::query()->count())->toBe(6)
        ->and(SourceDocument::query()->count())->toBe(1)
        ->and(ContentImport::query()->count())->toBe(1);
});

test('the sync refuses to run before the catalogue exists', function () {
    TaxonomyNode::query()->delete();

    syncAncom()->assertFailed();
});
