<?php

namespace App\Jobs;

use App\Models\ContentImport;
use App\Services\Content\ContentImportParser;
use App\Services\Content\QuestionImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessContentImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public int $importId,
    ) {}

    public function handle(ContentImportParser $parser, QuestionImporter $importer): void
    {
        $import = ContentImport::query()->findOrFail($this->importId);
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
            'errors' => null,
        ]);

        $path = Storage::disk('local')->path($import->stored_path);
        $result = $importer->import($import, $parser->rows($path, $import->format));

        $import->update([
            'status' => $result['failed'] > 0 ? 'completed_with_errors' : 'completed',
            'total_rows' => $result['total'],
            'processed_rows' => $result['processed'],
            'created_rows' => $result['created'],
            'updated_rows' => $result['updated'],
            'failed_rows' => $result['failed'],
            'errors' => array_slice($result['errors'], 0, 100),
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        ContentImport::query()->whereKey($this->importId)->update([
            'status' => 'failed',
            'errors' => [[
                'message' => $exception?->getMessage() ?? 'Import job failed.',
            ]],
            'completed_at' => now(),
        ]);
    }
}
