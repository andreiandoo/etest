<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property array<int, int>|null $option_order
 * @property array<string, mixed> $question_snapshot
 */
class AttemptQuestion extends Model
{
    protected $fillable = [
        'test_attempt_id',
        'question_id',
        'position',
        'points',
        'option_order',
        'question_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'option_order' => 'array',
            'question_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<TestAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    /** @return HasOne<AttemptAnswer, $this> */
    public function answer(): HasOne
    {
        return $this->hasOne(AttemptAnswer::class);
    }
}
