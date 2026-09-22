<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnswerOption extends Model
{
    protected $fillable = [
        'question_id',
        'content',
        'is_correct',
        'position',
        'feedback',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
