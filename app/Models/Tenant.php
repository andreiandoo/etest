<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed>|null $branding
 * @property array<string, mixed>|null $settings
 */
class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'branding',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'branding' => 'array',
            'settings' => 'array',
        ];
    }

    /** @return HasMany<TenantDomain, $this> */
    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /** @return BelongsToMany<Vertical, $this> */
    public function verticals(): BelongsToMany
    {
        return $this->belongsToMany(Vertical::class, 'tenant_vertical')->withTimestamps();
    }

    /** @return HasMany<ApiClient, $this> */
    public function apiClients(): HasMany
    {
        return $this->hasMany(ApiClient::class);
    }
}
