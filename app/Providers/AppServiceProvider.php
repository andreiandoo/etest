<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn (): TenantContext => new TenantContext);
    }

    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->is_admin);

        Model::shouldBeStrict(! app()->isProduction());

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
