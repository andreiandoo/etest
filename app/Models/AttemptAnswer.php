<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $answer
 */
class AttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_question_id',
        'answer',
        'is_correct',
        'awarded_points',
        'answered_at',
        'duration_ms',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'is_correct' => 'boolean',
            'awarded_points' => 'decimal:2',
            'answered_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<AttemptQuestion, $this> */
    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(AttemptQuestion::class);
    }
}
