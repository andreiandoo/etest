<?php

namespace App\Http\Controllers;

use App\Models\MonetizationClick;
use App\Models\SponsorPlacement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SponsoredClickController extends Controller
{
    public function __invoke(SponsorPlacement $placement): RedirectResponse
    {
        $placement->loadMissing(['campaign.sponsor']);
        $campaign = $placement->campaign;

        abort_unless(
            $placement->is_active
            && $campaign->is_active
            && $campaign->sponsor->is_active
            && ($campaign->starts_at === null || $campaign->starts_at->isPast())
            && ($campaign->ends_at === null || $campaign->ends_at->isFuture()),
            404,
        );

        MonetizationClick::create([
            'channel' => 'sponsor',
            'sponsor_placement_id' => $placement->id,
            'user_id' => Auth::id(),
            'source_url' => request()->headers->get('referer'),
            'created_at' => now(),
        ]);

        return redirect()->away($campaign->cta_url);
    }
}
