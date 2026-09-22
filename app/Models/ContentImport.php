<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property array<int, array<string, mixed>>|null $errors
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 */
class ContentImport extends Model
{
    protected $fillable = [
        'user_id',
        'vertical_id',
        'type',
        'format',
        'original_name',
        'stored_path',
        'status',
        'total_rows',
        'processed_rows',
        'created_rows',
        'updated_rows',
        'failed_rows',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Vertical, $this> */
    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
    }
}
