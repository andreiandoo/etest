<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $unlocked_at
 * @property array<string, mixed>|null $metadata
 */
class UserAchievement extends Model
{
    protected $fillable = [
        'user_id',
        'achievement_key',
        'unlocked_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
