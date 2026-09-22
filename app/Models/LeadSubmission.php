<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSubmission extends Model
{
    protected $fillable = [
        'lead_campaign_id',
        'user_id',
        'name',
        'email',
        'phone',
        'payload',
        'consented_at',
        'source_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'consented_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LeadCampaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(LeadCampaign::class, 'lead_campaign_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
