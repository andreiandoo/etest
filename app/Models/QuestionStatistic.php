<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $last_answered_at
 */
class QuestionStatistic extends Model
{
    protected $fillable = [
        'question_id',
        'attempts_count',
        'answered_count',
        'correct_count',
        'total_awarded_points',
        'total_possible_points',
        'correct_rate',
        'avg_duration_ms',
        'last_answered_at',
    ];

    protected function casts(): array
    {
        return [
            'total_awarded_points' => 'decimal:2',
            'total_possible_points' => 'decimal:2',
            'correct_rate' => 'decimal:4',
            'last_answered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
