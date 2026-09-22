<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use App\Enums\TestMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property AttemptStatus $status
 * @property TestMode $mode
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property array<string, mixed>|null $configuration
 */
class TestAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'test_id',
        'status',
        'mode',
        'seed',
        'started_at',
        'completed_at',
        'expires_at',
        'score',
        'max_score',
        'percentage',
        'duration_seconds',
        'current_position',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'mode' => TestMode::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'configuration' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<TestDefinition, $this> */
    public function test(): BelongsTo
    {
        return $this->belongsTo(TestDefinition::class, 'test_id');
    }

    /** @return HasMany<AttemptQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(AttemptQuestion::class)->orderBy('position');
    }
}
