<?php

use App\Enums\PublicationStatus;
use App\Enums\TaxonomyNodeType;
use App\Models\ContentImport;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\User;
use App\Models\Vertical;
use App\Services\Content\ContentImportParser;
use App\Services\Content\QuestionImporter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

test('content parser reads csv json and xlsx rows', function () {
    $parser = app(ContentImportParser::class);
    $directory = sys_get_temp_dir().'/etest-import-'.uniqid();
    mkdir($directory);

    $csv = $directory.'/questions.csv';
    file_put_contents($csv, "source_key,type,prompt\nq-1,single_choice,CSV question\n");

    $json = $directory.'/questions.json';
    file_put_contents($json, json_encode([
        ['source_key' => 'q-2', 'type' => 'true_false', 'prompt' => 'JSON question'],
    ], JSON_THROW_ON_ERROR));

    $xlsx = $directory.'/questions.xlsx';
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['source_key', 'type', 'prompt'],
        ['q-3', 'numeric', 'XLSX question'],
    ]);
    (new Xlsx($spreadsheet))->save($xlsx);
    $spreadsheet->disconnectWorksheets();

    $csvRows = iterator_to_array($parser->rows($csv, 'csv'));
    $jsonRows = iterator_to_array($parser->rows($json, 'json'));
    $xlsxRows = iterator_to_array($parser->rows($xlsx, 'xlsx'));

    expect($csvRows[0]['source_key'])->toBe('q-1')
        ->and($jsonRows[0]['source_key'])->toBe('q-2')
        ->and($xlsxRows[0]['source_key'])->toBe('q-3');

    @unlink($csv);
    @unlink($json);
    @unlink($xlsx);
    @rmdir($directory);
});

test('question import creates drafts and updates by source key instead of duplicating', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $vertical = Vertical::factory()->create();

    $import = ContentImport::create([
        'user_id' => $admin->id,
        'vertical_id' => $vertical->id,
        'type' => 'questions',
        'format' => 'json',
        'original_name' => 'questions.json',
        'stored_path' => 'imports/questions.json',
        'status' => 'processing',
    ]);

    $importer = app(QuestionImporter::class);

    $first = $importer->import($import, [[
        'source_key' => 'law-001',
        'type' => 'single_choice',
        'prompt' => 'Prima formulare',
        'difficulty' => 2,
        'answer_config' => [],
        'options' => [
            ['content' => 'A', 'is_correct' => true],
            ['content' => 'B', 'is_correct' => false],
        ],
    ]]);

    $second = $importer->import($import, [[
        'source_key' => 'law-001',
        'type' => 'single_choice',
        'prompt' => 'Formulare actualizată',
        'difficulty' => 3,
        'answer_config' => [],
        'options' => [
            ['content' => 'A2', 'is_correct' => false],
            ['content' => 'B2', 'is_correct' => true],
        ],
    ]]);

    $question = Question::query()
        ->where('vertical_id', $vertical->id)
        ->where('source_key', 'law-001')
        ->with('options')
        ->firstOrFail();

    expect($first['created'])->toBe(1)
        ->and($second['updated'])->toBe(1)
        ->and(Question::query()->where('vertical_id', $vertical->id)->where('source_key', 'law-001')->count())->toBe(1)
        ->and($question->status)->toBe(PublicationStatus::Draft)
        ->and($question->prompt)->toBe('Formulare actualizată')
        ->and($question->options)->toHaveCount(2)
        ->and($question->options->firstWhere('is_correct', true)?->content)->toBe('B2');
});

test('invalid import rows are isolated and reported without stopping valid rows', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $vertical = Vertical::factory()->create();

    $import = ContentImport::create([
        'user_id' => $admin->id,
        'vertical_id' => $vertical->id,
        'type' => 'questions',
        'format' => 'json',
        'original_name' => 'mixed.json',
        'stored_path' => 'imports/mixed.json',
        'status' => 'processing',
    ]);

    $result = app(QuestionImporter::class)->import($import, [
        ['source_key' => 'ok-1', 'type' => 'true_false', 'prompt' => 'Valid', 'answer_config' => ['correct_boolean' => true]],
        ['source_key' => 'bad-1', 'type' => 'not-a-type', 'prompt' => 'Invalid'],
    ]);

    expect($result['created'])->toBe(1)
        ->and($result['failed'])->toBe(1)
        ->and($result['processed'])->toBe(2)
        ->and($result['errors'])->toHaveCount(1);
});

/**
 * Aceeași materie stă sub mai multe examene: „drept civil” e și la avocat
 * stagiar, și la avocat definitiv, și la INM. O căutare numai pe slug nimerea
 * prima secțiune găsită și punea întrebările sub examenul greșit, în tăcere.
 */
test('a section slug that repeats in the vertical needs its parent named', function () {
    $vertical = Vertical::factory()->create();

    $barou = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'type' => TaxonomyNodeType::Exam,
        'name' => 'Barou',
        'slug' => 'barou',
    ]);

    $inm = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'type' => TaxonomyNodeType::Exam,
        'name' => 'INM',
        'slug' => 'inm',
    ]);

    foreach ([$barou, $inm] as $parent) {
        TaxonomyNode::create([
            'vertical_id' => $vertical->id,
            'parent_id' => $parent->id,
            'type' => TaxonomyNodeType::Subject,
            'name' => 'Drept civil',
            'slug' => 'drept-civil',
        ]);
    }

    $import = ContentImport::create([
        'vertical_id' => $vertical->id,
        'type' => 'questions',
        'format' => 'csv',
        'original_name' => 'proba.csv',
        'stored_path' => 'imports/proba.csv',
        'status' => 'processing',
        'started_at' => now(),
    ]);

    $outcome = app(QuestionImporter::class)->import($import, [
        ['source_key' => 'ambiguu', 'type' => 'single_choice', 'prompt' => 'Fără părinte', 'taxonomy_slug' => 'drept-civil'],
        ['source_key' => 'limpede', 'type' => 'single_choice', 'prompt' => 'Cu părinte', 'taxonomy_slug' => 'drept-civil', 'taxonomy_parent_slug' => 'inm'],
    ]);

    expect($outcome['failed'])->toBe(1)
        ->and($outcome['created'])->toBe(1)
        ->and($outcome['errors'][0]['message'])->toContain('taxonomy_parent_slug')
        ->and(Question::query()->where('source_key', 'ambiguu')->exists())->toBeFalse();

    $question = Question::query()->where('source_key', 'limpede')->firstOrFail();

    expect($question->taxonomy_node_id)->toBe($inm->children()->first()?->id);
});
