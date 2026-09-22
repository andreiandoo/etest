<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class SponsorCampaign extends Model
{
    protected $fillable = [
        'sponsor_id',
        'name',
        'headline',
        'body',
        'cta_label',
        'cta_url',
        'disclosure_label',
        'is_active',
        'starts_at',
        'ends_at',
        'priority',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Sponsor, $this> */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /** @return HasMany<SponsorPlacement, $this> */
    public function placements(): HasMany
    {
        return $this->hasMany(SponsorPlacement::class);
    }

    /**
     * @param  Builder<SponsorCampaign>  $query
     * @return Builder<SponsorCampaign>
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('sponsor', fn (Builder $sponsor) => $sponsor->where('is_active', true))
            ->where(fn (Builder $window) => $window
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $window) => $window
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now()));
    }
}
