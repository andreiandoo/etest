<?php

namespace App\Services\Sources;

use App\Models\ContentImport;
use App\Models\Source;
use App\Models\SourceDocument;
use App\Models\TaxonomyNode;
use App\Models\Vertical;
use App\Services\Content\QuestionImporter;
use App\Services\Sources\Contracts\SourceConnector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Duce o sursă de la adresa oficială până la întrebări în coada de revizuire.
 *
 * Pașii sunt cei din §33 al dosarului: descarcă, păstrează originalul cu
 * amprentă, extrage textul, parsează, validează, importă ca ciorne. Nimic nu
 * se publică automat — între import și site stă întotdeauna un om.
 *
 * Documentul nu se suprascrie niciodată. Dacă amprenta e aceeași ca la ultima
 * sincronizare, sursa nu s-a schimbat și nu mai importăm o dată aceleași
 * rânduri; dacă e alta, autoritatea a modificat fișierul sub aceeași adresă și
 * asta se vede în istoric.
 */
final class SourceSynchronizer
{
    public function __construct(
        private readonly PdfTextExtractor $extractor,
        private readonly QuestionImporter $importer,
    ) {}

    public function sync(SourceConnector $connector, bool $force = false, ?string $localFile = null): SyncResult
    {
        $source = $this->register($connector);
        $connector->prepare($source);

        [$contents, $origin] = $this->fetch($source, $localFile);
        $sha256 = hash('sha256', $contents);
        $existing = $source->documents()->where('sha256', $sha256)->first();

        if ($existing !== null && ! $force) {
            $source->forceFill(['last_synced_at' => now()])->save();

            return new SyncResult('unchanged', $sha256, $source, $existing);
        }

        $extension = $localFile !== null ? strtolower(pathinfo($localFile, PATHINFO_EXTENSION)) : $source->file_format;
        $storagePath = $this->store($source, $contents, $sha256, $extension !== '' ? $extension : 'pdf');
        $text = $this->text($storagePath, $contents, $localFile);
        $textPath = $this->storeText($source, $sha256, $text);

        $result = $connector->parse($text);

        if ($result['questions'] === []) {
            throw new RuntimeException(
                'Nu am recunoscut nicio întrebare în document. Textul extras e la '.$textPath.'.'
            );
        }

        $import = $this->import($source, $connector, $storagePath, $result);

        $document = $existing ?? new SourceDocument;
        $document->fill([
            'source_id' => $source->id,
            'content_import_id' => $import->id,
            'storage_path' => $storagePath,
            'text_path' => $textPath,
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : 'text/plain',
            'sha256' => $sha256,
            'byte_size' => strlen($contents),
            'version_label' => $source->version_label,
            'item_count' => $result['total'],
            'imported_count' => (int) $import->created_rows + (int) $import->updated_rows,
            'rejected_count' => count($result['rejected']) + (int) $import->failed_rows,
            'rejected' => array_slice($result['rejected'], 0, 200),
            'retrieved_at' => now(),
        ])->save();

        $source->forceFill([
            'item_count_raw' => $result['total'],
            'review_status' => 'parsed',
            'last_synced_at' => now(),
        ])->save();

        return new SyncResult(
            $origin === 'local' ? 'imported_from_file' : 'imported',
            $sha256,
            $source,
            $document,
            $import,
            $result['total'],
            $result['rejected'],
        );
    }

    /**
     * Creează sau actualizează rândul din registru din definiția conectorului.
     */
    private function register(SourceConnector $connector): Source
    {
        $definition = $connector->definition();
        $verticalSlug = (string) ($definition['vertical_slug'] ?? '');
        $taxonomySlug = (string) ($definition['taxonomy_slug'] ?? '');

        unset($definition['vertical_slug'], $definition['taxonomy_slug']);

        $vertical = Vertical::query()->where('slug', $verticalSlug)->first();

        if ($vertical === null) {
            throw new RuntimeException(
                'Verticala „'.$verticalSlug.'” nu există. Rulează întâi: php artisan db:seed --class=CatalogSeeder'
            );
        }

        $node = TaxonomyNode::query()
            ->where('vertical_id', $vertical->id)
            ->where('slug', $taxonomySlug)
            ->first();

        if ($node === null) {
            throw new RuntimeException('Secțiunea „'.$taxonomySlug.'” nu există în verticala „'.$verticalSlug.'”.');
        }

        $source = Source::query()->firstOrNew(['key' => $definition['key']]);
        $isNew = ! $source->exists;

        $source->fill([
            ...$definition,
            'vertical_id' => $vertical->id,
            'taxonomy_node_id' => $node->id,
        ]);

        if ($isNew) {
            $source->review_status = 'discovered';
        }

        $source->save();

        return $source->refresh();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fetch(Source $source, ?string $localFile): array
    {
        if ($localFile !== null) {
            if (! is_file($localFile)) {
                throw new RuntimeException('Fișierul local nu există: '.$localFile);
            }

            return [(string) file_get_contents($localFile), 'local'];
        }

        $response = Http::timeout(180)
            ->withHeaders(['User-Agent' => 'e-test.ro content sync (+https://e-test.ro)'])
            ->get($source->document_url);

        if (! $response->successful()) {
            throw new RuntimeException('Descărcarea a eșuat cu codul '.$response->status().'.');
        }

        $body = $response->body();

        if (strlen($body) < 1024) {
            throw new RuntimeException('Documentul descărcat e suspect de mic: '.strlen($body).' octeți.');
        }

        return [$body, 'remote'];
    }

    private function store(Source $source, string $contents, string $sha256, string $extension): string
    {
        $path = 'sources/'.$source->key.'/'.now()->format('Y-m-d').'-'.substr($sha256, 0, 12).'.'.$extension;

        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    private function storeText(Source $source, string $sha256, string $text): string
    {
        $path = 'sources/'.$source->key.'/'.now()->format('Y-m-d').'-'.substr($sha256, 0, 12).'.txt';

        Storage::disk('local')->put($path, $text);

        return $path;
    }

    private function text(string $storagePath, string $contents, ?string $localFile): string
    {
        if ($localFile !== null && str_ends_with(strtolower($localFile), '.txt')) {
            return $contents;
        }

        return $this->extractor->extract(Storage::disk('local')->path($storagePath));
    }

    /**
     * @param  array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}  $result
     */
    private function import(Source $source, SourceConnector $connector, string $storagePath, array $result): ContentImport
    {
        $import = ContentImport::create([
            'user_id' => null,
            'vertical_id' => $source->vertical_id,
            'type' => 'questions',
            'format' => $source->file_format,
            'original_name' => basename((string) parse_url($source->document_url, PHP_URL_PATH)),
            'stored_path' => $storagePath,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $rows = $connector->rows($source, $result['questions']);
        $outcome = $this->importer->import($import, $rows);

        // Rândurile respinse de parser sunt tot rânduri respinse: apar în
        // aceeași listă ca erorile de import, ca omul să aibă un singur loc de
        // verificat după sincronizare.
        $errors = array_merge(
            array_map(static fn (array $row): array => [
                'cod' => $row['code'],
                'motiv' => $row['reason'],
                'text' => $row['text'],
            ], $result['rejected']),
            $outcome['errors'],
        );

        $import->update([
            'status' => $errors === [] ? 'completed' : 'completed_with_errors',
            'total_rows' => $result['total'],
            'processed_rows' => $outcome['processed'],
            'created_rows' => $outcome['created'],
            'updated_rows' => $outcome['updated'],
            'failed_rows' => count($errors),
            'errors' => array_slice($errors, 0, 200),
            'completed_at' => now(),
        ]);

        return $import->refresh();
    }
}
