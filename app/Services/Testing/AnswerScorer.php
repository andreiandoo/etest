<?php

namespace App\Services\Testing;

use App\Data\ScoreResult;
use App\Enums\QuestionType;

final class AnswerScorer
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $answer
     */
    public function score(array $snapshot, array $answer, float $points): ScoreResult
    {
        $type = QuestionType::from((string) $snapshot['type']);
        $config = is_array($snapshot['answer_config'] ?? null) ? $snapshot['answer_config'] : [];

        return match ($type) {
            QuestionType::SingleChoice => $this->scoreChoice($snapshot, $answer, $points, multiple: false),
            QuestionType::MultipleChoice => $this->scoreChoice($snapshot, $answer, $points, multiple: true),
            QuestionType::TrueFalse => $this->scoreBoolean($config, $answer, $points),
            QuestionType::Numeric => $this->scoreNumeric($config, $answer, $points),
            QuestionType::ShortText => $this->scoreText($config, $answer, $points),
            QuestionType::Matching => $this->scoreMatching($config, $answer, $points),
            QuestionType::Ordering => $this->scoreOrdering($config, $answer, $points),
        };
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $answer
     */
    private function scoreChoice(array $snapshot, array $answer, float $points, bool $multiple): ScoreResult
    {
        $options = is_array($snapshot['options'] ?? null) ? $snapshot['options'] : [];
        $correct = [];
        $incorrect = [];

        foreach ($options as $option) {
            if (! is_array($option) || ! isset($option['id'])) {
                continue;
            }

            $id = (int) $option['id'];

            if ((bool) ($option['is_correct'] ?? false)) {
                $correct[] = $id;
            } else {
                $incorrect[] = $id;
            }
        }

        $selected = array_values(array_map('intval', is_array($answer['selected'] ?? null) ? $answer['selected'] : []));
        sort($correct, SORT_NUMERIC);
        sort($selected, SORT_NUMERIC);

        $exact = $selected === $correct;

        if ($exact) {
            return new ScoreResult($points, true);
        }

        $config = is_array($snapshot['answer_config'] ?? null) ? $snapshot['answer_config'] : [];

        if (! $multiple || ! (bool) ($config['partial_credit'] ?? false) || $correct === []) {
            return new ScoreResult(0.0, false);
        }

        $correctSelected = count(array_intersect($selected, $correct));
        $wrongSelected = count(array_intersect($selected, $incorrect));
        $base = $points * ($correctSelected / count($correct));
        $penalty = $wrongSelected * max(0.0, (float) ($config['penalty_per_wrong'] ?? 0.0));
        $awarded = max(0.0, min($points, $base - $penalty));

        return new ScoreResult($awarded, false);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $answer
     */
    private function scoreBoolean(array $config, array $answer, float $points): ScoreResult
    {
        $expected = (bool) ($config['correct_boolean'] ?? false);
        $correct = isset($answer['value']) && (bool) $answer['value'] === $expected;

        return new ScoreResult($correct ? $points : 0.0, $correct);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $answer
     */
    private function scoreNumeric(array $config, array $answer, float $points): ScoreResult
    {
        if (! isset($answer['value'], $config['correct_value'])) {
            return new ScoreResult(0.0, false);
        }

        $expected = (float) $config['correct_value'];
        $actual = (float) $answer['value'];
        $tolerance = max(0.0, (float) ($config['tolerance'] ?? 0.0));
        $correct = abs($actual - $expected) <= $tolerance;

        return new ScoreResult($correct ? $points : 0.0, $correct);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $answer
     */
    private function scoreText(array $config, array $answer, float $points): ScoreResult
    {
        $actual = trim((string) ($answer['value'] ?? ''));
        $accepted = is_array($config['accepted_answers'] ?? null) ? $config['accepted_answers'] : [];
        $caseSensitive = (bool) ($config['case_sensitive'] ?? false);

        $normalize = static function (string $value) use ($caseSensitive): string {
            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

            return $caseSensitive ? $value : mb_strtolower($value);
        };

        $actual = $normalize($actual);
        $correct = false;

        foreach ($accepted as $value) {
            if ($actual === $normalize((string) $value)) {
                $correct = true;
                break;
            }
        }

        return new ScoreResult($correct ? $points : 0.0, $correct);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $answer
     */
    private function scoreMatching(array $config, array $answer, float $points): ScoreResult
    {
        $expected = is_array($config['correct_pairs'] ?? null) ? $config['correct_pairs'] : [];
        $actual = is_array($answer['pairs'] ?? null) ? $answer['pairs'] : [];

        $expected = array_map('strval', $expected);
        $actual = array_map('strval', $actual);
        ksort($expected);
        ksort($actual);

        $exact = $expected !== [] && $expected === $actual;

        if ($exact) {
            return new ScoreResult($points, true);
        }

        if (! (bool) ($config['partial_credit'] ?? false) || $expected === []) {
            return new ScoreResult(0.0, false);
        }

        $matches = 0;

        foreach ($expected as $left => $right) {
            if (($actual[$left] ?? null) === $right) {
                $matches++;
            }
        }

        return new ScoreResult($points * ($matches / count($expected)), false);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $answer
     */
    private function scoreOrdering(array $config, array $answer, float $points): ScoreResult
    {
        $expected = array_values(array_map('strval', is_array($config['correct_order'] ?? null) ? $config['correct_order'] : []));
        $actual = array_values(array_map('strval', is_array($answer['items'] ?? null) ? $answer['items'] : []));
        $exact = $expected !== [] && $expected === $actual;

        if ($exact) {
            return new ScoreResult($points, true);
        }

        if (! (bool) ($config['partial_credit'] ?? false) || $expected === []) {
            return new ScoreResult(0.0, false);
        }

        $matches = 0;

        foreach ($expected as $index => $item) {
            if (($actual[$index] ?? null) === $item) {
                $matches++;
            }
        }

        return new ScoreResult($points * ($matches / count($expected)), false);
    }
}
