<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sponsor extends Model
{
    protected $fillable = [
        'name',
        'website_url',
        'logo_url',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /** @return HasMany<SponsorCampaign, $this> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(SponsorCampaign::class);
    }

    /**
     * @param  Builder<Sponsor>  $query
     * @return Builder<Sponsor>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
