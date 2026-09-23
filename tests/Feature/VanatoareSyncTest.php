<?php

use App\Enums\PublicationStatus;
use App\Models\ContentImport;
use App\Models\Question;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(CatalogSeeder::class);
});

function syncVanatoare(): PendingCommand
{
    return test()->artisan('sources:sync', [
        'sursa' => 'vanatoare-permis',
        '--fisier' => base_path('tests/Fixtures/vanatoare-docbook-excerpt.xml'),
    ]);
}

test('the hunting source is registered with the law it is published under', function () {
    syncVanatoare()->assertSuccessful();

    $source = Source::query()->where('key', 'vanatoare-permis')->firstOrFail();

    expect($source->authority)->toContain('Ministerul Mediului')
        ->and($source->version_label)->toBe('Legea 171/2022')
        ->and($source->file_format)->toBe('doc')
        ->and($source->rights_status)->toBe('official_public_unclear')
        ->and($source->notes)->toContain('antiword');
});

test('questions land under the chapter they belong to', function () {
    syncVanatoare()->assertSuccessful();

    $permis = TaxonomyNode::query()->where('slug', 'permis-de-vanator')->firstOrFail();

    expect(TaxonomyNode::query()->where('parent_id', $permis->id)->count())->toBe(10)
        ->and(Question::query()->count())->toBe(5);

    $weapons = TaxonomyNode::query()
        ->where('parent_id', $permis->id)
        ->where('slug', 'arme-si-munitii')
        ->firstOrFail();

    expect(Question::query()->where('taxonomy_node_id', $weapons->id)->count())->toBe(1);
});

test('the answer marked only by bold survives the import', function () {
    syncVanatoare()->assertSuccessful();

    $question = Question::query()
        ->where('prompt', 'Durata creşterii coarnelor cerbului comun este de:')
        ->with('options')
        ->firstOrFail();

    expect($question->options)->toHaveCount(3)
        ->and($question->options->where('is_correct', true))->toHaveCount(1)
        ->and($question->options->firstWhere('is_correct', true)?->content)->toBe('120 – 130 zile')
        ->and($question->status)->toBe(PublicationStatus::Published)
        ->and($question->source_label)->toContain('171/2022');
});

test('picture questions are reported, not imported', function () {
    syncVanatoare()->assertSuccessful();

    $import = ContentImport::query()->latest('id')->firstOrFail();

    expect($import->total_rows)->toBe(6)
        ->and($import->created_rows)->toBe(5)
        ->and($import->failed_rows)->toBe(1)
        ->and(json_encode($import->errors, JSON_UNESCAPED_UNICODE))->toContain('imagini');
});

test('the same question keeps its key across imports, so nothing is duplicated', function () {
    syncVanatoare()->assertSuccessful();

    $keys = Question::query()->orderBy('id')->pluck('source_key');

    test()->artisan('sources:sync', [
        'sursa' => 'vanatoare-permis',
        '--fisier' => base_path('tests/Fixtures/vanatoare-docbook-excerpt.xml'),
        '--forteaza' => true,
    ])->assertSuccessful();

    expect(Question::query()->count())->toBe(5)
        ->and(Question::query()->orderBy('id')->pluck('source_key')->all())->toBe($keys->all());
});

test('practice tests are built per chapter and across all of them', function () {
    syncVanatoare()->assertSuccessful();

    // Doar două capitole au întrebări în extras, plus testul general.
    expect(TestDefinition::query()->count())->toBe(3)
        ->and(TestDefinition::query()->where('slug', 'toate-capitolele')->firstOrFail()->questions()->count())->toBe(5);
});
