<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Vertical;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
            try {
                $allowedVerticalIds = app(TenantContext::class)->allowedVerticalIds();

                $verticals = Vertical::query()
                    ->active()
                    ->when(
                        $allowedVerticalIds !== null,
                        fn ($query) => $query->whereIn('id', $allowedVerticalIds),
                    )
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug', 'metadata']);
            } catch (Throwable) {
                // Pagina de eroare foloseste acelasi layout. Daca baza de date e
                // chiar cauza erorii, navigatia se afiseaza goala in loc sa
                // transforme un 500 intr-o eroare de randare.
                $verticals = new Collection;
            }

            $view->with('navVerticals', $verticals);
        });
    }
}
