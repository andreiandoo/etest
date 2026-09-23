<?php

use App\Http\Controllers\BrevoWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Webhook-uri primite de la servicii externe.
|
| Stau separat de rutele web fiindcă nu au sesiune, nu au CSRF și nu trec prin
| rezolvarea de tenant: un serviciu extern nu are cum să trimită Host-ul
| potrivit, iar ResolveTenant ar răspunde 404.
*/

Route::post('/webhooks/brevo', BrevoWebhookController::class)->name('webhooks.brevo');
