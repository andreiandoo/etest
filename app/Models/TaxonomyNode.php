<?php

namespace App\Models;

use App\Enums\TaxonomyNodeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TaxonomyNodeType $type
 */
class TaxonomyNode extends Model
{
    protected $fillable = [
        'vertical_id',
        'parent_id',
        'type',
        'name',
        'slug',
        'description',
        'seo_title',
        'seo_description',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaxonomyNodeType::class,
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Vertical, $this> */
    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
    }

    /** @return BelongsTo<TaxonomyNode, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<TaxonomyNode, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<TestDefinition, $this> */
    public function tests(): HasMany
    {
        return $this->hasMany(TestDefinition::class, 'taxonomy_node_id');
    }

    /**
     * @param  Builder<TaxonomyNode>  $query
     * @return Builder<TaxonomyNode>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
