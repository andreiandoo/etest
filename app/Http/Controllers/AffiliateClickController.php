<?php

namespace App\Http\Controllers;

use App\Models\AffiliateResource;
use App\Models\MonetizationClick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AffiliateClickController extends Controller
{
    public function __invoke(AffiliateResource $resource): RedirectResponse
    {
        $resource->loadMissing('merchant');

        abort_unless(
            $resource->is_active
            && $resource->merchant->is_active
            && ($resource->starts_at === null || $resource->starts_at->isPast())
            && ($resource->ends_at === null || $resource->ends_at->isFuture()),
            404,
        );

        MonetizationClick::create([
            'channel' => 'affiliate',
            'affiliate_resource_id' => $resource->id,
            'user_id' => Auth::id(),
            'source_url' => request()->headers->get('referer'),
            'created_at' => now(),
        ]);

        return redirect()->away($resource->affiliate_url);
    }
}
