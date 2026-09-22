<?php

namespace App\Livewire\Monetization;

use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class NewsletterSignup extends Component
{
    public string $interestKey;

    public int $verticalId;

    public ?int $taxonomyNodeId = null;

    public string $interestLabel;

    public string $email = '';

    public bool $consent = false;

    public string $companyWebsite = '';

    public string $state = 'form';

    public function mount(
        string $interestKey,
        int $verticalId,
        ?int $taxonomyNodeId,
        string $interestLabel,
    ): void {
        $this->interestKey = $interestKey;
        $this->verticalId = $verticalId;
        $this->taxonomyNodeId = $taxonomyNodeId;
        $this->interestLabel = $interestLabel;

        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            $this->email = $user->email;
        }
    }

    public function submit(): void
    {
        if ($this->companyWebsite !== '') {
            $this->state = 'pending';

            return;
        }

        $key = 'newsletter:'.sha1((string) request()->ip().':'.$this->interestKey);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Ai făcut prea multe solicitări. Încearcă din nou puțin mai târziu.',
            ]);
        }

        $validated = $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'consent' => ['accepted'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));

        /** @var User|null $user */
        $user = Auth::user();

        $subscription = NewsletterSubscription::query()->firstOrNew([
            'email' => $email,
            'interest_key' => $this->interestKey,
        ]);

        if ($subscription->exists && $subscription->status === 'active') {
            $this->state = 'active';

            return;
        }

        $subscription->forceFill([
            'user_id' => $user?->id,
            'vertical_id' => $this->verticalId,
            'taxonomy_node_id' => $this->taxonomyNodeId,
            'status' => 'pending',
            'consented_at' => now(),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
            'source_url' => request()->headers->get('referer'),
        ])->save();

        Mail::to($email)->queue(new ConfirmNewsletterSubscription($subscription));

        RateLimiter::hit($key, 60);
        $this->state = 'pending';
    }

    public function render(): View
    {
        return view('livewire.monetization.newsletter-signup');
    }
}
