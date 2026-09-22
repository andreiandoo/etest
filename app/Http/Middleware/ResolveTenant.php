<?php

namespace App\Http\Middleware;

use App\Models\TenantDomain;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = mb_strtolower($request->getHost());

        $domain = TenantDomain::query()
            ->with('tenant.verticals')
            ->where('host', $host)
            ->first();

        if ($domain !== null) {
            abort_unless($domain->is_active && $domain->tenant->is_active, 404);
            $this->context->set($domain->tenant);
        } else {
            $primaryHost = mb_strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

            if (app()->isProduction() && $primaryHost !== '' && ! hash_equals($primaryHost, $host)) {
                abort(404);
            }

            $this->context->set(null);
        }

        View::share('tenantContext', $this->context);

        return $next($request);
    }
}
