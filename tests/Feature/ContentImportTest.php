<?php

use App\Enums\PublicationStatus;
use App\Models\ContentImport;
use App\Models\Question;
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
