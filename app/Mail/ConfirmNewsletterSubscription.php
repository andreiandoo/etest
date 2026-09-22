<?php

namespace App\Mail;

use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmNewsletterSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscription $subscription,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmă abonarea la e-test.ro',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.newsletter-confirmation',
            with: [
                'confirmationUrl' => URL::temporarySignedRoute(
                    'newsletter.confirm',
                    now()->addHours(48),
                    ['subscription' => $this->subscription->id],
                ),
                'unsubscribeUrl' => URL::signedRoute(
                    'newsletter.unsubscribe',
                    ['subscription' => $this->subscription->id],
                ),
            ],
        );
    }
}
