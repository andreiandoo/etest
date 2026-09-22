<?php

namespace App\Services\Api;

use App\Models\ApiKey;
use App\Models\ApiUsageDaily;
use Illuminate\Support\Facades\DB;

final class ApiUsageMeter
{
    /**
     * @return array{allowed:bool,reason:?string,daily_count:int,monthly_count:int}
     */
    public function reserve(ApiKey $key): array
    {
        return DB::transaction(function () use ($key): array {
            DB::statement('SELECT pg_advisory_xact_lock(?)', [$key->id]);

            $dailyCount = (int) ApiUsageDaily::query()
                ->where('api_key_id', $key->id)
                ->whereDate('usage_date', today())
                ->value('request_count');

            $monthlyCount = (int) ApiUsageDaily::query()
                ->where('api_key_id', $key->id)
                ->whereBetween('usage_date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ])
                ->sum('request_count');

            if ($key->daily_quota !== null && $dailyCount >= $key->daily_quota) {
                return [
                    'allowed' => false,
                    'reason' => 'daily',
                    'daily_count' => $dailyCount,
                    'monthly_count' => $monthlyCount,
                ];
            }

            if ($key->monthly_quota !== null && $monthlyCount >= $key->monthly_quota) {
                return [
                    'allowed' => false,
                    'reason' => 'monthly',
                    'daily_count' => $dailyCount,
                    'monthly_count' => $monthlyCount,
                ];
            }

            DB::statement(
                <<<'SQL'
                    INSERT INTO api_usage_daily (api_key_id, usage_date, request_count, response_bytes, error_count, created_at, updated_at)
                    VALUES (?, ?, 1, 0, 0, NOW(), NOW())
                    ON CONFLICT (api_key_id, usage_date)
                    DO UPDATE SET
                        request_count = api_usage_daily.request_count + 1,
                        updated_at = NOW()
                SQL,
                [$key->id, today()->toDateString()],
            );

            return [
                'allowed' => true,
                'reason' => null,
                'daily_count' => $dailyCount + 1,
                'monthly_count' => $monthlyCount + 1,
            ];
        });
    }

    public function complete(ApiKey $key, int $responseBytes, bool $isError): void
    {
        ApiUsageDaily::query()
            ->where('api_key_id', $key->id)
            ->whereDate('usage_date', today())
            ->update([
                'response_bytes' => DB::raw('response_bytes + '.max(0, $responseBytes)),
                'error_count' => DB::raw('error_count + '.($isError ? 1 : 0)),
                'updated_at' => now(),
            ]);
    }
}
