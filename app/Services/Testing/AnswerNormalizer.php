<?php

namespace App\Services\Testing;

use App\Enums\QuestionType;
use InvalidArgumentException;

final class AnswerNormalizer
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function normalize(array $snapshot, mixed $rawAnswer): array
    {
        $type = QuestionType::from((string) $snapshot['type']);

        return match ($type) {
            QuestionType::SingleChoice => [
                'selected' => $this->normalizeIdList($this->value($rawAnswer, 'selected'), oneOnly: true),
            ],
            QuestionType::MultipleChoice => [
                'selected' => $this->normalizeIdList($this->value($rawAnswer, 'selected')),
            ],
            QuestionType::TrueFalse => [
                'value' => $this->normalizeBoolean($this->value($rawAnswer, 'value')),
            ],
            QuestionType::Numeric => [
                'value' => $this->normalizeNumber($this->value($rawAnswer, 'value')),
            ],
            QuestionType::ShortText => [
                'value' => trim((string) $this->value($rawAnswer, 'value')),
            ],
            QuestionType::Matching => [
                'pairs' => $this->normalizePairs($this->value($rawAnswer, 'pairs')),
            ],
            QuestionType::Ordering => [
                'items' => $this->normalizeStringList($this->value($rawAnswer, 'items')),
            ],
        };
    }

    private function value(mixed $answer, string $key): mixed
    {
        if (is_array($answer)) {
            return $answer[$key] ?? null;
        }

        return $answer;
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value, bool $oneOnly = false): array
    {
        $values = is_array($value) ? $value : [$value];

        $normalized = array_values(array_unique(array_map(
            static fn (mixed $item): int => (int) $item,
            array_filter($values, static fn (mixed $item): bool => $item !== null && $item !== '')
        )));

        sort($normalized, SORT_NUMERIC);

        if ($oneOnly && count($normalized) > 1) {
            $normalized = [$normalized[0]];
        }

        return $normalized;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'true', 'yes', 'da' => true,
            '0', 'false', 'no', 'nu' => false,
            default => throw new InvalidArgumentException('Invalid boolean answer.'),
        };
    }

    private function normalizeNumber(mixed $value): float
    {
        $normalized = str_replace(',', '.', trim((string) $value));

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException('Invalid numeric answer.');
        }

        return (float) $normalized;
    }

    /**
     * @return array<string, string>
     */
    private function normalizePairs(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $pairs = [];

        foreach ($value as $left => $right) {
            if ($right === null || $right === '') {
                continue;
            }

            $pairs[(string) $left] = (string) $right;
        }

        ksort($pairs);

        return $pairs;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $item): string => (string) $item,
            array_filter($value, static fn (mixed $item): bool => $item !== null && $item !== '')
        ));
    }
}
