<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $last_activity_date
 */
class UserStat extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'xp',
        'completed_attempts',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'leaderboard_opt_in',
        'leaderboard_display_name',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_date' => 'date',
            'leaderboard_opt_in' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
