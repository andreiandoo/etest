<?php

namespace App\Services\Content;

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Models\ContentImport;
use App\Models\Question;
use App\Models\TaxonomyNode;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

final class QuestionImporter
{
    /**
     * @param  iterable<int, array<string, mixed>>  $rows
     * @return array{total:int,processed:int,created:int,updated:int,failed:int,errors:array<int,array<string,mixed>>}
     */
    public function import(ContentImport $import, iterable $rows): array
    {
        $total = 0;
        $processed = 0;
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $total++;

            try {
                $wasCreated = $this->importRow($import, $row);
                $wasCreated ? $created++ : $updated++;
            } catch (Throwable $exception) {
                $failed++;
                $errors[] = [
                    'row' => $index + 2,
                    'message' => $exception->getMessage(),
                ];
            }

            $processed++;
        }

        return compact('total', 'processed', 'created', 'updated', 'failed', 'errors');
    }

    /**
     * Secțiunea în care intră întrebarea.
     *
     * Slug-urile de taxonomie sunt unice doar față de același părinte, nu în
     * toată verticala: în Drept există „drept civil” sub avocat stagiar, sub
     * avocat definitiv și sub INM. O căutare doar pe slug nimerea prima
     * secțiune găsită și punea liniștit opt sute de întrebări sub examenul
     * greșit, fără nicio eroare. De aceea rândul spune și părintele, iar un
     * slug care rămâne ambiguu oprește importul rândului în loc să ghicească.
     */
    private function nodeId(int $verticalId, string $slug, string $parentSlug): int
    {
        $query = TaxonomyNode::query()->where('vertical_id', $verticalId)->where('slug', $slug);

        if ($parentSlug !== '') {
            $parents = TaxonomyNode::query()
                ->where('vertical_id', $verticalId)
                ->where('slug', $parentSlug)
                ->pluck('id');

            if ($parents->count() !== 1) {
                throw new InvalidArgumentException('Secțiunea-părinte „'.$parentSlug.'” apare de '
                    .$parents->count().' ori în verticală.');
            }

            $query->where('parent_id', $parents->first());
        }

        $ids = $query->pluck('id');

        if ($ids->isEmpty()) {
            throw new InvalidArgumentException('Unknown taxonomy_slug: '.$slug);
        }

        if ($ids->count() > 1) {
            throw new InvalidArgumentException('Secțiunea „'.$slug.'” apare de '.$ids->count()
                .' ori în verticală. Rândul are nevoie de taxonomy_parent_slug ca să se știe care.');
        }

        return (int) $ids->first();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importRow(ContentImport $import, array $row): bool
    {
        $verticalId = $import->vertical_id;

        if ($verticalId === null) {
            throw new InvalidArgumentException('Import requires a vertical.');
        }

        $prompt = trim((string) ($row['prompt'] ?? ''));

        if ($prompt === '') {
            throw new InvalidArgumentException('prompt is required.');
        }

        $type = QuestionType::tryFrom(trim((string) ($row['type'] ?? '')));

        if ($type === null) {
            throw new InvalidArgumentException('Invalid question type.');
        }

        $sourceKey = trim((string) ($row['source_key'] ?? ''));
        $taxonomySlug = trim((string) ($row['taxonomy_slug'] ?? ''));

        $taxonomyNodeId = $taxonomySlug === '' ? null : $this->nodeId(
            $verticalId,
            $taxonomySlug,
            trim((string) ($row['taxonomy_parent_slug'] ?? '')),
        );

        $answerConfig = $this->jsonValue($row['answer_config'] ?? null, []);
        $options = $this->jsonValue($row['options'] ?? null, []);
        $metadata = $this->jsonValue($row['metadata'] ?? null, []);

        return DB::transaction(function () use (
            $import,
            $verticalId,
            $taxonomyNodeId,
            $sourceKey,
            $type,
            $prompt,
            $row,
            $answerConfig,
            $options,
            $metadata
        ): bool {
            $query = Question::query()->where('vertical_id', $verticalId);

            $question = $sourceKey !== ''
                ? $query->where('source_key', $sourceKey)->first()
                : null;

            $wasCreated = $question === null;

            $question ??= new Question;

            $question->fill([
                'vertical_id' => $verticalId,
                'taxonomy_node_id' => $taxonomyNodeId,
                'source_key' => $sourceKey !== '' ? $sourceKey : null,
                'type' => $type,
                // Un import nu retrogradează ce e deja pe site. Dacă sursa
                // s-a schimbat, conținutul se actualizează la locul lui; o
                // întrebare publicată nu dispare din teste pentru că a rulat
                // o sincronizare.
                'status' => $question->exists ? $question->status : PublicationStatus::Draft,
                'prompt' => $prompt,
                'explanation' => $this->nullableString($row['explanation'] ?? null),
                'difficulty' => max(1, min(5, (int) ($row['difficulty'] ?? 3))),
                'source_label' => $this->nullableString($row['source_label'] ?? null),
                'source_url' => $this->nullableString($row['source_url'] ?? null),
                'source_checked_at' => $this->nullableString($row['source_checked_at'] ?? null),
                'answer_config' => $answerConfig,
                // Metadata adusă de conector — codul din documentul sursă,
                // grupul de variante — se adaugă peste ce există, ca un import
                // parțial să nu șteargă ce a pus altcineva înainte.
                'metadata' => is_array($metadata) && $metadata !== []
                    ? array_replace($question->metadata ?? [], $metadata)
                    : $question->metadata,
                'created_by' => $question->exists ? $question->created_by : $import->user_id,
                'updated_by' => $import->user_id,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'published_by' => null,
            ]);
            $question->save();

            if (is_array($options) && $options !== []) {
                $question->options()->delete();

                foreach (array_values($options) as $position => $option) {
                    if (! is_array($option)) {
                        continue;
                    }

                    $question->options()->create([
                        'content' => (string) ($option['content'] ?? ''),
                        'is_correct' => (bool) ($option['is_correct'] ?? false),
                        'position' => $position,
                        'feedback' => $this->nullableString($option['feedback'] ?? null),
                    ]);
                }
            }

            return $wasCreated;
        });
    }

    private function jsonValue(mixed $value, mixed $default): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
