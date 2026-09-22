<?php

namespace App\Models;

use Database\Factories\VerticalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vertical extends Model
{
    /** @use HasFactory<VerticalFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
        'seo_title',
        'seo_description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<TaxonomyNode, $this> */
    public function taxonomyNodes(): HasMany
    {
        return $this->hasMany(TaxonomyNode::class);
    }

    /** @return HasMany<TestDefinition, $this> */
    public function tests(): HasMany
    {
        return $this->hasMany(TestDefinition::class, 'vertical_id');
    }

    /** @return HasMany<Question, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * @param  Builder<Vertical>  $query
     * @return Builder<Vertical>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
