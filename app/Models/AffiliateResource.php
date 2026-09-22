<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class AffiliateResource extends Model
{
    protected $fillable = [
        'affiliate_merchant_id',
        'vertical_id',
        'taxonomy_node_id',
        'test_id',
        'title',
        'description',
        'resource_type',
        'affiliate_url',
        'image_url',
        'price_label',
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

    /** @return BelongsTo<AffiliateMerchant, $this> */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(AffiliateMerchant::class, 'affiliate_merchant_id');
    }

    /** @return BelongsTo<Vertical, $this> */
    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
    }

    /** @return BelongsTo<TaxonomyNode, $this> */
    public function taxonomyNode(): BelongsTo
    {
        return $this->belongsTo(TaxonomyNode::class);
    }

    /** @return BelongsTo<TestDefinition, $this> */
    public function test(): BelongsTo
    {
        return $this->belongsTo(TestDefinition::class, 'test_id');
    }

    /**
     * @param  Builder<AffiliateResource>  $query
     * @return Builder<AffiliateResource>
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('merchant', fn (Builder $merchant) => $merchant->where('is_active', true))
            ->where(fn (Builder $window) => $window
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $window) => $window
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now()));
    }
}
