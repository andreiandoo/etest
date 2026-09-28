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

    /**
     * Secțiunea și tot ce crește sub ea.
     *
     * Coborârea se face nivel cu nivel, nu recursiv în SQL, ca să meargă la fel
     * pe orice bază; nodurile deja văzute nu se mai caută, deci o legătură
     * greșită în date nu învârte bucla la infinit.
     *
     * @return array<int, int>
     */
    public function subtreeIds(): array
    {
        $ids = [(int) $this->id];
        $level = $ids;

        while ($level !== []) {
            $children = static::query()
                ->whereIn('parent_id', $level)
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $level = array_values(array_diff($children, $ids));
            $ids = [...$ids, ...$level];
        }

        return $ids;
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
