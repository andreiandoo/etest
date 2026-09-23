<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnforceApiQuota;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\RequireApiScope;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Fara prefixul /api si fara middleware-ul web: webhook-urile nu au
            // sesiune, nu au CSRF si nu trec prin rezolvarea de tenant.
            Route::middleware('api')->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ResolveTenant::class,
        ]);

        $middleware->alias([
            'noindex' => NoIndex::class,
            'api.key' => AuthenticateApiKey::class,
            'api.scope' => RequireApiScope::class,
            'api.quota' => EnforceApiQuota::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
