<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $usage_date
 */
class ApiUsageDaily extends Model
{
    protected $table = 'api_usage_daily';

    protected $fillable = [
        'api_key_id',
        'usage_date',
        'request_count',
        'response_bytes',
        'error_count',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
        ];
    }

    /** @return BelongsTo<ApiKey, $this> */
    public function key(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }
}
