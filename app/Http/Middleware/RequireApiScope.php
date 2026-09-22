<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiScope
{
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        $key = $request->attributes->get('api_key');

        if (! $key instanceof ApiKey) {
            return $this->forbidden('API key context is unavailable.');
        }

        foreach ($requiredScopes as $scope) {
            if (! $key->hasScope($scope)) {
                return $this->forbidden('Missing required scope: '.$scope);
            }
        }

        return $next($request);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'forbidden',
                'message' => $message,
            ],
        ], 403);
    }
}
