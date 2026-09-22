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
        $taxonomyNodeId = null;
        $taxonomySlug = trim((string) ($row['taxonomy_slug'] ?? ''));

        if ($taxonomySlug !== '') {
            $taxonomyNodeId = TaxonomyNode::query()
                ->where('vertical_id', $verticalId)
                ->where('slug', $taxonomySlug)
                ->value('id');

            if ($taxonomyNodeId === null) {
                throw new InvalidArgumentException('Unknown taxonomy_slug: '.$taxonomySlug);
            }
        }

        $answerConfig = $this->jsonValue($row['answer_config'] ?? null, []);
        $options = $this->jsonValue($row['options'] ?? null, []);

        return DB::transaction(function () use (
            $import,
            $verticalId,
            $taxonomyNodeId,
            $sourceKey,
            $type,
            $prompt,
            $row,
            $answerConfig,
            $options
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
                'status' => PublicationStatus::Draft,
                'prompt' => $prompt,
                'explanation' => $this->nullableString($row['explanation'] ?? null),
                'difficulty' => max(1, min(5, (int) ($row['difficulty'] ?? 3))),
                'source_label' => $this->nullableString($row['source_label'] ?? null),
                'source_url' => $this->nullableString($row['source_url'] ?? null),
                'source_checked_at' => $this->nullableString($row['source_checked_at'] ?? null),
                'answer_config' => $answerConfig,
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
