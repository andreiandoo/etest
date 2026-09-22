<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property array<int, string>|null $requested_fields
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class LeadCampaign extends Model
{
    protected $fillable = [
        'vertical_id',
        'taxonomy_node_id',
        'test_id',
        'name',
        'title',
        'description',
        'cta_label',
        'requested_fields',
        'consent_text',
        'is_active',
        'starts_at',
        'ends_at',
        'priority',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requested_fields' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
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

    /** @return HasMany<LeadSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(LeadSubmission::class);
    }

    /**
     * @param  Builder<LeadCampaign>  $query
     * @return Builder<LeadCampaign>
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $window) => $window
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $window) => $window
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now()));
    }
}
