<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainText = $request->bearerToken() ?: $request->header('X-API-Key');

        if (! is_string($plainText) || $plainText === '') {
            return $this->unauthorized('API key missing.');
        }

        $key = ApiKey::query()
            ->with(['client.tenant.verticals'])
            ->where('key_hash', hash('sha256', $plainText))
            ->first();

        if ($key === null || ! hash_equals($key->key_hash, hash('sha256', $plainText)) || ! $key->isUsable()) {
            return $this->unauthorized('API key invalid, expired or revoked.');
        }

        $key->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_key', $key);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'unauthorized',
                'message' => $message,
            ],
        ], 401);
    }
}
