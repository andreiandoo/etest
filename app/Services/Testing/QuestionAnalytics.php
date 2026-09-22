<?php

namespace App\Services\Testing;

use App\Models\QuestionStatistic;
use Illuminate\Support\Facades\DB;

final class QuestionAnalytics
{
    public function rebuild(int $questionId): QuestionStatistic
    {
        $row = DB::table('attempt_answers')
            ->join('attempt_questions', 'attempt_questions.id', '=', 'attempt_answers.attempt_question_id')
            ->where('attempt_questions.question_id', $questionId)
            ->selectRaw('COUNT(DISTINCT attempt_questions.test_attempt_id) AS attempts_count')
            ->selectRaw('COUNT(attempt_answers.id) AS answered_count')
            ->selectRaw('SUM(CASE WHEN attempt_answers.is_correct = TRUE THEN 1 ELSE 0 END) AS correct_count')
            ->selectRaw('COALESCE(SUM(attempt_answers.awarded_points), 0) AS total_awarded_points')
            ->selectRaw('COALESCE(SUM(attempt_questions.points), 0) AS total_possible_points')
            ->selectRaw('COALESCE(AVG(attempt_answers.duration_ms), 0) AS avg_duration_ms')
            ->selectRaw('MAX(attempt_answers.answered_at) AS last_answered_at')
            ->first();

        $attempts = (int) ($row->attempts_count ?? 0);
        $answered = (int) ($row->answered_count ?? 0);
        $correct = (int) ($row->correct_count ?? 0);

        return QuestionStatistic::query()->updateOrCreate(
            ['question_id' => $questionId],
            [
                'attempts_count' => $attempts,
                'answered_count' => $answered,
                'correct_count' => $correct,
                'total_awarded_points' => (float) ($row->total_awarded_points ?? 0),
                'total_possible_points' => (float) ($row->total_possible_points ?? 0),
                'correct_rate' => $answered > 0 ? round($correct / $answered, 4) : 0,
                'avg_duration_ms' => (int) round((float) ($row->avg_duration_ms ?? 0)),
                'last_answered_at' => $row->last_answered_at,
            ],
        );
    }
}
