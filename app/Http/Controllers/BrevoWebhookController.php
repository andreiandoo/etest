<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Evenimente de livrare de la Brevo.
 *
 * Un abonat care marchează emailul ca spam sau a cărui adresă respinge
 * definitiv trebuie scos imediat. Dacă nu, continuăm să trimitem către adrese
 * moarte și către oameni care ne-au refuzat explicit, iar reputația de
 * expeditor se arde — moment în care nici emailurile legitime, ca cel de
 * confirmare, nu mai ajung.
 *
 * Brevo nu semnează payload-ul, așa că endpoint-ul se apără printr-un secret
 * pus în URL-ul configurat în consola lor.
 */
class BrevoWebhookController extends Controller
{
    /**
     * Evenimente după care o adresă nu mai trebuie contactată.
     */
    private const TERMINAL_EVENTS = [
        'hard_bounce' => 'bounced',
        'invalid_email' => 'bounced',
        'blocked' => 'bounced',
        'error' => 'bounced',
        'spam' => 'complained',
        'complaint' => 'complained',
        'unsubscribed' => 'unsubscribed',
        'unsubscribe' => 'unsubscribed',
        'list_addition' => null,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->hasValidSecret($request)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $event = strtolower(trim((string) $request->input('event', '')));
        $email = strtolower(trim((string) $request->input('email', '')));

        if ($event === '' || $email === '') {
            return response()->json(['message' => 'Ignored.'], 202);
        }

        $status = self::TERMINAL_EVENTS[$event] ?? null;

        if ($status === null) {
            // Livrările, deschiderile și soft bounce-urile nu schimbă starea
            // abonării. Răspundem 202 ca Brevo să nu reîncerce la nesfârșit.
            return response()->json(['message' => 'Ignored.'], 202);
        }

        $affected = NewsletterSubscription::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->whereNotIn('status', ['unsubscribed', 'bounced', 'complained'])
            ->update([
                'status' => $status,
                'unsubscribed_at' => now(),
            ]);

        if ($affected > 0) {
            Log::info('Abonare newsletter oprită de un eveniment Brevo.', [
                'event' => $event,
                'status' => $status,
                'subscriptions' => $affected,
            ]);
        }

        return response()->json(['message' => 'Processed.', 'subscriptions' => $affected]);
    }

    private function hasValidSecret(Request $request): bool
    {
        $expected = (string) config('services.brevo.webhook_secret');

        if ($expected === '') {
            return false;
        }

        $provided = (string) ($request->query('token') ?? $request->header('X-Webhook-Token', ''));

        return hash_equals($expected, $provided);
    }
}
