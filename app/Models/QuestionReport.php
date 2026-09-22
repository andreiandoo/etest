<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property array<string, mixed>|null $context
 * @property Carbon|null $resolved_at
 */
class QuestionReport extends Model
{
    protected $fillable = [
        'user_id',
        'question_id',
        'attempt_question_id',
        'reason',
        'message',
        'status',
        'context',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return BelongsTo<AttemptQuestion, $this> */
    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(AttemptQuestion::class);
    }
}
