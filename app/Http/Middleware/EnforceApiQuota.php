<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\Api\ApiUsageMeter;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnforceApiQuota
{
    public function __construct(
        private readonly ApiUsageMeter $meter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->attributes->get('api_key');

        if (! $key instanceof ApiKey) {
            return response()->json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'API key context is unavailable.',
                ],
            ], 401);
        }

        $reservation = $this->meter->reserve($key);

        if (! $reservation['allowed']) {
            return $this->quotaExceeded(
                $key,
                $reservation['reason'],
                $reservation['daily_count'],
                $reservation['monthly_count'],
            );
        }

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->meter->complete($key, 0, true);

            throw $exception;
        }

        $this->meter->complete(
            $key,
            strlen((string) $response->getContent()),
            $response->getStatusCode() >= 400,
        );

        $this->applyUsageHeaders(
            $response,
            $key,
            $reservation['daily_count'],
            $reservation['monthly_count'],
        );

        return $response;
    }

    private function quotaExceeded(
        ApiKey $key,
        ?string $reason,
        int $dailyCount,
        int $monthlyCount,
    ): JsonResponse {
        $response = response()->json([
            'error' => [
                'code' => 'quota_exceeded',
                'message' => $reason === 'daily'
                    ? 'Daily API quota exceeded.'
                    : 'Monthly API quota exceeded.',
            ],
        ], 429);

        $this->applyUsageHeaders($response, $key, $dailyCount, $monthlyCount);

        return $response;
    }

    private function applyUsageHeaders(
        Response $response,
        ApiKey $key,
        int $dailyCount,
        int $monthlyCount,
    ): void {
        if ($key->daily_quota !== null) {
            $response->headers->set('X-RateLimit-Daily-Limit', (string) $key->daily_quota);
        }

        $response->headers->set('X-RateLimit-Daily-Used', (string) $dailyCount);

        if ($key->monthly_quota !== null) {
            $response->headers->set('X-RateLimit-Monthly-Limit', (string) $key->monthly_quota);
        }

        $response->headers->set('X-RateLimit-Monthly-Used', (string) $monthlyCount);
    }
}
