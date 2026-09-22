<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonetizationClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'channel',
        'sponsor_placement_id',
        'affiliate_resource_id',
        'user_id',
        'source_url',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return BelongsTo<SponsorPlacement, $this> */
    public function sponsorPlacement(): BelongsTo
    {
        return $this->belongsTo(SponsorPlacement::class);
    }

    /** @return BelongsTo<AffiliateResource, $this> */
    public function affiliateResource(): BelongsTo
    {
        return $this->belongsTo(AffiliateResource::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
