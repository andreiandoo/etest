<?php

namespace App\Services\Sources;

use App\Models\ContentImport;
use App\Models\Source;
use App\Models\SourceDocument;
use App\Models\TaxonomyNode;
use App\Models\Vertical;
use App\Services\Content\ImportPublisher;
use App\Services\Content\QuestionImporter;
use App\Services\Sources\Contracts\MultiDocumentSource;
use App\Services\Sources\Contracts\SingleDocumentSource;
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
        private readonly PdfTextExtractor $pdf,
        private readonly DocTextExtractor $doc,
        private readonly QuestionImporter $importer,
        private readonly ImportPublisher $publisher,
    ) {}

    public function sync(
        SourceConnector $connector,
        bool $force = false,
        ?string $localFile = null,
        bool $publish = true,
    ): SyncResult {
        $source = $this->register($connector);
        $connector->prepare($source);

        $fetched = $connector instanceof MultiDocumentSource
            ? $this->fetchSet($connector)
            : $this->fetchOne($source, $localFile);

        $sha256 = (string) $fetched['sha256'];
        $existing = $source->documents()->where('sha256', $sha256)->first();

        if ($existing !== null && ! $force) {
            // Documentul e același, dar pot exista rânduri rămase ciorne de la
            // o sincronizare de dinainte. O rulare fără argumente trebuie să
            // le aducă pe site, nu să se oprească mulțumită.
            $published = $publish ? $this->publisher->publish($source) : ['questions' => 0, 'tests' => 0];

            $source->forceFill(['last_synced_at' => now()])->save();

            return new SyncResult(
                'unchanged',
                $sha256,
                $source,
                $existing,
                published: $published,
            );
        }

        [$storagePath, $textPath, $result] = $connector instanceof MultiDocumentSource
            ? $this->readSet($source, $connector, $fetched)
            : $this->readOne($source, $connector, $fetched, $localFile);

        if ($result['questions'] === []) {
            throw new RuntimeException(
                'Nu am recunoscut nicio întrebare. Ce s-a descărcat e păstrat la '.$storagePath.'.'
            );
        }

        $import = $this->import($source, $connector, $storagePath, $result);
        $tests = $connector->buildTests($source);
        $published = $publish ? $this->publisher->publish($source) : ['questions' => 0, 'tests' => 0];

        $document = $existing ?? new SourceDocument;
        $document->fill([
            'source_id' => $source->id,
            'content_import_id' => $import->id,
            'storage_path' => $storagePath,
            'text_path' => $textPath,
            'mime_type' => (string) $fetched['mime'],
            'sha256' => $sha256,
            'byte_size' => (int) $fetched['bytes'],
            'version_label' => $source->version_label,
            'item_count' => $result['total'],
            'imported_count' => (int) $import->created_rows + (int) $import->updated_rows,
            'rejected_count' => count($result['rejected']) + (int) $import->failed_rows,
            'rejected' => array_slice($result['rejected'], 0, 200),
            'retrieved_at' => now(),
        ])->save();

        $source->forceFill([
            'item_count_raw' => $result['total'],
            'review_status' => $publish ? 'publishable' : 'parsed',
            'last_synced_at' => now(),
        ])->save();

        return new SyncResult(
            $fetched['origin'] === 'local' ? 'imported_from_file' : 'imported',
            $sha256,
            $source,
            $document,
            $import,
            $result['total'],
            $result['rejected'],
            $tests,
            $published,
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
     * @return array<string, mixed>
     */
    private function fetchOne(Source $source, ?string $localFile): array
    {
        if ($localFile !== null) {
            if (! is_file($localFile)) {
                throw new RuntimeException('Fișierul local nu există: '.$localFile);
            }

            $contents = (string) file_get_contents($localFile);
            $extension = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));
            $origin = 'local';
        } else {
            $contents = $this->download($source->document_url);
            $extension = $source->file_format;
            $origin = 'remote';
        }

        $extension = $extension !== '' ? $extension : 'pdf';

        return [
            'sha256' => hash('sha256', $contents),
            'contents' => $contents,
            'extension' => $extension,
            'mime' => $this->mime($extension),
            'bytes' => strlen($contents),
            'origin' => $origin,
        ];
    }

    /**
     * Amprenta unui set e amprenta amprentelor, pe nume sortate.
     *
     * Așa, o sursă cu optsprezece fișiere are tot un singur identificator, iar
     * schimbarea oricăruia dintre ele se vede la sincronizarea următoare.
     *
     * @return array<string, mixed>
     */
    private function fetchSet(MultiDocumentSource $connector): array
    {
        $files = [];
        $bytes = 0;
        $manifest = [];

        foreach ($connector->documents() as $name => $url) {
            $contents = $this->download($url);
            $files[$name] = $contents;
            $bytes += strlen($contents);
            $manifest[$name] = $name.':'.hash('sha256', $contents);
        }

        if ($files === []) {
            throw new RuntimeException('Conectorul nu a indicat niciun fișier de descărcat.');
        }

        ksort($manifest);

        return [
            'sha256' => hash('sha256', implode("
", $manifest)),
            'files' => $files,
            'mime' => 'multipart/mixed',
            'bytes' => $bytes,
            'origin' => 'remote',
        ];
    }

    /**
     * @param  array<string, mixed>  $fetched
     * @return array{0: string, 1: ?string, 2: array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}}
     */
    private function readOne(Source $source, SourceConnector $connector, array $fetched, ?string $localFile): array
    {
        if (! $connector instanceof SingleDocumentSource) {
            throw new RuntimeException('Conectorul „'.$connector->key().'” nu știe să citească un singur fișier.');
        }

        $storagePath = $this->store($source, (string) $fetched['contents'], (string) $fetched['sha256'], (string) $fetched['extension']);
        $text = $this->text($source, $storagePath, (string) $fetched['contents'], $localFile);
        $textPath = $this->storeText($source, (string) $fetched['sha256'], $text);

        return [$storagePath, $textPath, $connector->parse($text)];
    }

    /**
     * @param  array<string, mixed>  $fetched
     * @return array{0: string, 1: ?string, 2: array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}}
     */
    private function readSet(Source $source, MultiDocumentSource $connector, array $fetched): array
    {
        $directory = 'sources/'.$source->key.'/'.now()->format('Y-m-d').'-'.substr((string) $fetched['sha256'], 0, 12);
        $paths = [];

        foreach ((array) $fetched['files'] as $name => $contents) {
            $path = $directory.'/'.$name;
            Storage::disk('local')->put($path, (string) $contents);
            $paths[$name] = Storage::disk('local')->path($path);
        }

        return [$directory, null, $connector->parseDocuments($paths)];
    }

    private function download(string $url): string
    {
        $response = Http::timeout(180)
            ->withHeaders(['User-Agent' => 'e-test.ro content sync (+https://e-test.ro)'])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Descărcarea a eșuat cu codul '.$response->status().' pentru '.$url);
        }

        $body = $response->body();

        if (strlen($body) < 1024) {
            throw new RuntimeException('Fișierul descărcat e suspect de mic ('.strlen($body).' octeți): '.$url);
        }

        return $body;
    }

    private function mime(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xml' => 'application/xml',
            default => 'text/plain',
        };
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

    /**
     * Fiecare format are extractorul lui, iar un fișier deja convertit — text
     * sau DocBook — se folosește așa cum e. Asta e portița pentru cazul în care
     * conversia trebuie făcută pe altă mașină.
     */
    private function text(Source $source, string $storagePath, string $contents, ?string $localFile): string
    {
        if ($localFile !== null && preg_match('/\.(txt|xml)$/i', $localFile) === 1) {
            return $contents;
        }

        $path = Storage::disk('local')->path($storagePath);

        return match ($source->file_format) {
            'doc', 'docx' => $this->doc->extract($path),
            default => $this->pdf->extract($path),
        };
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
