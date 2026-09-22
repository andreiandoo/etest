<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Contracts\View\View;

class NewsletterSubscriptionController extends Controller
{
    public function confirm(NewsletterSubscription $subscription): View
    {
        if ($subscription->status === 'unsubscribed') {
            return view('newsletter.status', [
                'title' => 'Abonarea nu a fost reactivată',
                'message' => 'Această preferință a fost deja dezabonată. Pentru reabonare, completează din nou formularul și confirmă noua solicitare.',
            ]);
        }

        $subscription->forceFill([
            'status' => 'active',
            'confirmed_at' => $subscription->confirmed_at ?? now(),
        ])->save();

        return view('newsletter.status', [
            'title' => 'Abonare confirmată',
            'message' => 'Preferința ta a fost confirmată. Vei primi doar comunicări relevante pentru interesul ales.',
        ]);
    }

    public function unsubscribe(NewsletterSubscription $subscription): View
    {
        $subscription->forceFill([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ])->save();

        return view('newsletter.status', [
            'title' => 'Dezabonare confirmată',
            'message' => 'Nu vei mai primi newsletter pentru acest interes.',
        ]);
    }
}
