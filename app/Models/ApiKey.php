<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property array<int, string> $scopes
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 */
class ApiKey extends Model
{
    protected $fillable = [
        'api_client_id',
        'name',
        'key_prefix',
        'key_hash',
        'scopes',
        'daily_quota',
        'monthly_quota',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ApiClient, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }

    /** @return HasMany<ApiUsageDaily, $this> */
    public function usage(): HasMany
    {
        return $this->hasMany(ApiUsageDaily::class);
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && $this->client->is_active
            && ($this->client->tenant === null || $this->client->tenant->is_active);
    }

    /**
     * @param  Builder<ApiKey>  $query
     * @return Builder<ApiKey>
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(fn (Builder $window) => $window
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->whereHas('client', fn (Builder $client) => $client->where('is_active', true));
    }
}
