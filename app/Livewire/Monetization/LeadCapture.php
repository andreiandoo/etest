<?php

namespace App\Livewire\Monetization;

use App\Models\LeadCampaign;
use App\Models\LeadSubmission;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LeadCapture extends Component
{
    public LeadCampaign $campaign;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public bool $consent = false;

    public string $companyWebsite = '';

    public bool $submitted = false;

    public function mount(LeadCampaign $campaign): void
    {
        $this->campaign = $campaign;

        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            $this->name = $user->name;
            $this->email = $user->email;
        }
    }

    public function submit(): void
    {
        if ($this->companyWebsite !== '') {
            $this->submitted = true;

            return;
        }

        abort_unless($this->isActive(), 404);

        $key = 'lead:'.$this->campaign->id.':'.sha1((string) request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Ai trimis prea multe solicitări. Încearcă din nou puțin mai târziu.',
            ]);
        }

        $requested = $this->campaign->requested_fields ?? [];

        $rules = [
            'email' => ['required', 'email:rfc', 'max:255'],
            'consent' => ['accepted'],
        ];

        if (in_array('name', $requested, true)) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        if (in_array('phone', $requested, true)) {
            $rules['phone'] = ['required', 'string', 'max:40'];
        }

        $validated = $this->validate($rules);

        /** @var User|null $user */
        $user = Auth::user();

        LeadSubmission::create([
            'lead_campaign_id' => $this->campaign->id,
            'user_id' => $user?->id,
            'name' => trim($this->name) !== '' ? trim($this->name) : null,
            'email' => mb_strtolower(trim((string) $validated['email'])),
            'phone' => trim($this->phone) !== '' ? trim($this->phone) : null,
            'payload' => ['requested_fields' => $requested],
            'consented_at' => now(),
            'source_url' => request()->headers->get('referer'),
            'status' => 'new',
        ]);

        RateLimiter::hit($key, 60);
        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.monetization.lead-capture', [
            'requestedFields' => $this->campaign->requested_fields ?? [],
        ]);
    }

    private function isActive(): bool
    {
        return $this->campaign->is_active
            && ($this->campaign->starts_at === null || $this->campaign->starts_at->isPast())
            && ($this->campaign->ends_at === null || $this->campaign->ends_at->isFuture());
    }
}
