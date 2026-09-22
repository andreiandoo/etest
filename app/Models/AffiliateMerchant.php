<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateMerchant extends Model
{
    protected $fillable = [
        'name',
        'website_url',
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

    /** @return HasMany<AffiliateResource, $this> */
    public function resources(): HasMany
    {
        return $this->hasMany(AffiliateResource::class);
    }
}
