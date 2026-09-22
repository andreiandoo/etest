<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorPlacement extends Model
{
    protected $fillable = [
        'sponsor_campaign_id',
        'vertical_id',
        'taxonomy_node_id',
        'test_id',
        'placement',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<SponsorCampaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SponsorCampaign::class, 'sponsor_campaign_id');
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
}
