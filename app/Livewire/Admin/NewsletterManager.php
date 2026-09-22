<?php

namespace App\Livewire\Admin;

use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscription;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class NewsletterManager extends Component
{
    use WithPagination;

    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function unsubscribe(int $id): void
    {
        NewsletterSubscription::query()->findOrFail($id)->forceFill([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ])->save();

        session()->flash('admin_message', 'Abonarea a fost dezactivată.');
    }

    public function resendConfirmation(int $id): void
    {
        $subscription = NewsletterSubscription::query()->findOrFail($id);

        if ($subscription->status !== 'pending') {
            return;
        }

        Mail::to($subscription->email)->queue(new ConfirmNewsletterSubscription($subscription));
        session()->flash('admin_message', 'Emailul de confirmare a fost retrimis.');
    }

    public function render(): View
    {
        $query = NewsletterSubscription::query()
            ->with(['vertical', 'taxonomyNode'])
            ->latest();

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('livewire.admin.newsletter-manager', [
            'subscriptions' => $query->paginate(50),
            'counts' => [
                'pending' => NewsletterSubscription::query()->where('status', 'pending')->count(),
                'active' => NewsletterSubscription::query()->where('status', 'active')->count(),
                'unsubscribed' => NewsletterSubscription::query()->where('status', 'unsubscribed')->count(),
            ],
            'interests' => NewsletterSubscription::query()
                ->selectRaw('interest_key, COUNT(*) as total')
                ->where('status', 'active')
                ->groupBy('interest_key')
                ->orderByDesc('total')
                ->limit(30)
                ->get(),
        ]);
    }
}
