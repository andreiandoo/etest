<?php

use App\Models\NewsletterSubscription;

beforeEach(function () {
    config(['services.brevo.webhook_secret' => 'secret-de-test']);
});

function brevoSubscription(string $email, string $status = 'active'): NewsletterSubscription
{
    return NewsletterSubscription::create([
        'email' => $email,
        'interest_key' => 'auto',
        'status' => $status,
        'consented_at' => now(),
        'confirmed_at' => $status === 'active' ? now() : null,
    ]);
}

test('the webhook refuses requests without the shared secret', function () {
    $subscription = brevoSubscription('cineva@example.com');

    $this->postJson('/webhooks/brevo', ['event' => 'spam', 'email' => 'cineva@example.com'])
        ->assertUnauthorized();

    $this->postJson('/webhooks/brevo?token=gresit', ['event' => 'spam', 'email' => 'cineva@example.com'])
        ->assertUnauthorized();

    expect($subscription->fresh()->status)->toBe('active');
});

test('a spam complaint stops the subscription immediately', function () {
    $subscription = brevoSubscription('reclamant@example.com');

    $this->postJson('/webhooks/brevo?token=secret-de-test', [
        'event' => 'spam',
        'email' => 'reclamant@example.com',
    ])->assertOk();

    $subscription->refresh();

    expect($subscription->status)->toBe('complained')
        ->and($subscription->unsubscribed_at)->not->toBeNull();
});

test('a hard bounce marks the address as bounced regardless of letter case', function () {
    $subscription = brevoSubscription('respins@example.com');

    $this->postJson('/webhooks/brevo?token=secret-de-test', [
        'event' => 'hard_bounce',
        'email' => 'RESPINS@Example.com',
    ])->assertOk();

    expect($subscription->fresh()->status)->toBe('bounced');
});

test('delivery and open events leave the subscription untouched', function () {
    $subscription = brevoSubscription('activ@example.com');

    foreach (['delivered', 'opened', 'click', 'soft_bounce'] as $event) {
        $this->postJson('/webhooks/brevo?token=secret-de-test', [
            'event' => $event,
            'email' => 'activ@example.com',
        ])->assertAccepted();
    }

    expect($subscription->fresh()->status)->toBe('active');
});

test('an already unsubscribed address is not overwritten by a later bounce', function () {
    $subscription = brevoSubscription('plecat@example.com', 'unsubscribed');

    $this->postJson('/webhooks/brevo?token=secret-de-test', [
        'event' => 'hard_bounce',
        'email' => 'plecat@example.com',
    ])->assertOk();

    expect($subscription->fresh()->status)->toBe('unsubscribed');
});
