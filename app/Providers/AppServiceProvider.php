<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Vertical;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        $this->shareNavigationVerticals();
    }

    /**
     * Navigația și footer-ul listează verticalele pe fiecare pagină publică.
     * Le încărcăm o singură dată, prin composer, ca să nu repetăm interogarea
     * în fiecare controller, și respectăm filtrarea pe tenant.
     */
    private function shareNavigationVerticals(): void
    {
        View::composer('layouts.app', function (ViewContract $view): void {
            $allowedVerticalIds = app(TenantContext::class)->allowedVerticalIds();

            $view->with('navVerticals', Vertical::query()
                ->active()
                ->when(
                    $allowedVerticalIds !== null,
                    fn ($query) => $query->whereIn('id', $allowedVerticalIds),
                )
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'metadata']));
        });
    }
}
