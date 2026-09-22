<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteTest extends Model
{
    protected $fillable = [
        'user_id',
        'test_id',
    ];

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
}
